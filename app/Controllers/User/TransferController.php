<?php

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\WalletModel;
use App\Models\WalletTransactionModel;
use App\Models\UserModel;
use App\Models\MerchantModel;
use App\Models\MerchantTransactionModel;
use App\Models\TransferModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Transfer Controller
 * Handle transfers between users and to merchants
 */
class TransferController extends BaseController
{
    protected $session;
    protected WalletModel $walletModel;
    protected UserModel $userModel;

    public function __construct()
    {
        $this->session = session();
        $this->walletModel = new WalletModel();
        $this->userModel = new UserModel();
    }

    /**
     * Return JSON response
     */
    private function json(array $data, int $status = 200): ResponseInterface
    {
        return $this->response->setJSON($data)->setStatusCode($status);
    }

    /**
     * Transfer page
     */
    public function index()
    {
        $user = $this->session->get('user');
        if (!$user) {
            return redirect()->to('/login');
        }

        $balance = $this->walletModel->getBalance($user['id']);

        return view('user/transfer', [
            'user' => $user,
            'balance' => $balance,
        ]);
    }

    /**
     * Process transfer
     */
    public function process()
    {
        $user = $this->session->get('user');
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Please login first'
            ]);
        }

        $destination = $this->request->getPost('destination'); // card_number or merchant_id
        $amount = (float) $this->request->getPost('amount');
        $note = $this->request->getPost('note') ?? '';

        if ($amount < 1000) {
            return $this->json([
                'success' => false,
                'message' => 'Minimum transfer is Rp 1,000'
            ]);
        }

        // Check balance
        $balance = $this->walletModel->getBalance($user['id']);
        if ($balance < $amount) {
            return $this->json([
                'success' => false,
                'message' => 'Insufficient balance'
            ]);
        }

        // Determine if destination is merchant or user
        $merchantModel = new MerchantModel();
        $merchant = $merchantModel->getByMerchantId($destination);

        if ($merchant) {
            // Transfer to merchant
            return $this->transferToMerchant($user, $merchant, $amount, $note);
        }

        // Check if it's a card number
        $recipient = $this->userModel->where('card_number', $destination)->first();
        if ($recipient) {
            // Transfer to user
            return $this->transferToUser($user, $recipient, $amount, $note);
        }

        return $this->json([
            'success' => false,
            'message' => 'Recipient not found. Please check the card number or merchant ID.'
        ]);
    }

    /**
     * Transfer to merchant
     */
    private function transferToMerchant($sender, $merchant, $amount, $note)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Deduct from sender wallet
            $this->walletModel->deductBalance($sender['id'], $amount);

            // Log sender transaction
            $walletTxModel = new WalletTransactionModel();
            $walletTxModel->addTransaction(
                $sender['id'],
                WalletTransactionModel::TYPE_DEBIT,
                $amount,
                "Payment to {$merchant['business_name']}" . ($note ? " - $note" : '')
            );

            // Calculate fee
            $feeRate = $merchant['commission_rate'] ?? 2.5;
            $fee = $amount * ($feeRate / 100);
            $netAmount = $amount - $fee;

            // Add to merchant balance
            $merchantModel = new MerchantModel();
            $merchantModel->updateBalance($merchant['id'], $netAmount);

            // Create merchant transaction
            $transactionId = 'TXN-' . strtoupper(uniqid()) . '-' . time();
            $merchantTxModel = new MerchantTransactionModel();
            $merchantTxModel->insert([
                'merchant_id'    => $merchant['id'],
                'customer_id'    => $sender['id'],
                'transaction_id' => $transactionId,
                'amount'         => $amount,
                'fee'            => $fee,
                'net_amount'     => $netAmount,
                'status'         => MerchantTransactionModel::STATUS_SUCCESS,
                'payment_method' => 'wallet',
                'description'    => $note ?: 'Transfer payment',
                'created_at'     => date('Y-m-d H:i:s'),
            ]);

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Transaction failed');
            }

            return $this->json([
                'success'        => true,
                'message'        => 'Payment successful!',
                'transaction_id' => $transactionId,
                'recipient'      => $merchant['business_name'],
                'amount'         => $amount,
                'type'           => 'merchant',
            ]);

        } catch (\Exception $e) {
            $db->transRollback();
            return $this->json([
                'success' => false,
                'message' => 'Transfer failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Transfer to user
     */
    private function transferToUser($sender, $recipient, $amount, $note)
    {
        // Cannot transfer to self
        if ($sender['id'] == $recipient['id']) {
            return $this->json([
                'success' => false,
                'message' => 'Cannot transfer to yourself'
            ]);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $walletTxModel = new WalletTransactionModel();

            // Deduct from sender
            $this->walletModel->deductBalance($sender['id'], $amount);
            $walletTxModel->addTransaction(
                $sender['id'],
                WalletTransactionModel::TYPE_DEBIT,
                $amount,
                "Transfer to {$recipient['name']}" . ($note ? " - $note" : '')
            );

            // Add to recipient
            $this->walletModel->addBalance($recipient['id'], $amount);
            $walletTxModel->addTransaction(
                $recipient['id'],
                WalletTransactionModel::TYPE_CREDIT,
                $amount,
                "Transfer from {$sender['name']}" . ($note ? " - $note" : '')
            );

            // Record transfer
            $db->table('transfers')->insert([
                'sender_id'   => $sender['id'],
                'receiver_id' => $recipient['id'],
                'amount'      => $amount,
                'fee'         => 0,
                'note'        => $note,
                'status'      => 'success',
                'created_at'  => date('Y-m-d H:i:s'),
            ]);

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Transaction failed');
            }

            return $this->json([
                'success'   => true,
                'message'   => 'Transfer successful!',
                'recipient' => $recipient['name'],
                'amount'    => $amount,
                'type'      => 'user',
            ]);

        } catch (\Exception $e) {
            $db->transRollback();
            return $this->json([
                'success' => false,
                'message' => 'Transfer failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Lookup recipient info
     */
    public function lookup()
    {
        $destination = $this->request->getGet('q');

        if (empty($destination)) {
            return $this->json([
                'success' => false,
                'message' => 'Please enter card number or merchant ID'
            ]);
        }

        // Check merchant first
        $merchantModel = new MerchantModel();
        $merchant = $merchantModel->getByMerchantId($destination);

        if ($merchant) {
            return $this->json([
                'success' => true,
                'type'    => 'merchant',
                'name'    => $merchant['business_name'],
                'id'      => $merchant['merchant_id'],
            ]);
        }

        // Check user by card number
        $user = $this->userModel->where('card_number', $destination)->first();
        if ($user) {
            return $this->json([
                'success' => true,
                'type'    => 'user',
                'name'    => $user['name'],
                'id'      => $user['card_number'],
            ]);
        }

        return $this->json([
            'success' => false,
            'message' => 'Not found'
        ]);
    }
}
