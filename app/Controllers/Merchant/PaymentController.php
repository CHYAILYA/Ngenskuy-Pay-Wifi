<?php

namespace App\Controllers\Merchant;

use App\Models\MerchantModel;
use App\Models\MerchantTransactionModel;
use App\Models\WalletModel;
use App\Models\WalletTransactionModel;
use App\Models\UserModel;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Merchant Payment Controller
 * Handle incoming payments from users
 */
class PaymentController extends BaseMerchantController
{
    /**
     * Return JSON response
     */
    private function json(array $data, int $status = 200): ResponseInterface
    {
        return $this->response->setJSON($data)->setStatusCode($status);
    }
    /**
     * Generate QR Code for payment
     */
    public function qrcode()
    {
        $data = array_merge($this->getViewData(), [
            'qr_data' => json_encode([
                'type'        => 'merchant_payment',
                'merchant_id' => $this->merchant['merchant_id'],
                'name'        => $this->merchant['business_name'],
            ]),
        ]);
        
        return view('merchant/qrcode', $data);
    }

    /**
     * Request payment form
     */
    public function request()
    {
        $data = $this->getViewData();
        return view('merchant/payment_request', $data);
    }

    /**
     * Create payment request
     */
    public function create()
    {
        $amount = (float) $this->request->getPost('amount');
        $description = $this->request->getPost('description');
        
        if ($amount < 1000) {
            return redirect()->back()->with('error', 'Minimum amount is Rp 1.000');
        }
        
        // Generate payment link data
        $paymentData = [
            'merchant_id' => $this->merchant['merchant_id'],
            'amount'      => $amount,
            'description' => $description,
            'timestamp'   => time(),
        ];
        
        $paymentCode = base64_encode(json_encode($paymentData));
        $paymentUrl = base_url('pay/' . $this->merchant['merchant_id'] . '?amount=' . $amount . '&desc=' . urlencode($description));
        
        $data = array_merge($this->getViewData(), [
            'payment_url'  => $paymentUrl,
            'payment_code' => $paymentCode,
            'amount'       => $amount,
            'description'  => $description,
        ]);
        
        return view('merchant/payment_link', $data);
    }

    /**
     * Public payment page - anyone can access
     */
    public function publicPay($merchantId)
    {
        $merchantModel = new MerchantModel();
        $merchant = $merchantModel->getByMerchantId($merchantId);
        
        if (!$merchant) {
            return view('errors/html/error_404', ['message' => 'Merchant not found']);
        }
        
        $amount = $this->request->getGet('amount');
        $description = $this->request->getGet('desc');
        
        // Check if user is logged in
        $sessionUser = session()->get('user');
        $userId = $sessionUser['id'] ?? null;
        $user = null;
        $walletBalance = 0;
        
        if ($userId) {
            $userModel = new UserModel();
            $user = $userModel->find($userId);
            
            $walletModel = new WalletModel();
            $walletBalance = $walletModel->getBalance($userId);
        }
        
        return view('merchant/public_pay', [
            'merchant'      => $merchant,
            'amount'        => $amount,
            'description'   => $description,
            'user'          => $user,
            'walletBalance' => $walletBalance,
            'isLoggedIn'    => $userId ? true : false,
        ]);
    }

    /**
     * Process public payment
     */
    public function processPublicPay($merchantId)
    {
        $sessionUser = session()->get('user');
        $userId = $sessionUser['id'] ?? null;
        
        if (!$userId) {
            return redirect()->to('/login?redirect=' . urlencode(current_url()));
        }
        
        $merchantModel = new MerchantModel();
        $merchant = $merchantModel->getByMerchantId($merchantId);
        
        if (!$merchant) {
            return $this->json([
                'success' => false,
                'message' => 'Merchant not found'
            ]);
        }
        
        $amount = (float) $this->request->getPost('amount');
        
        if ($amount < 1000) {
            return $this->json([
                'success' => false,
                'message' => 'Minimum payment is Rp 1,000'
            ]);
        }
        
        $walletModel = new WalletModel();
        $balance = $walletModel->getBalance($userId);
        
        if ($balance < $amount) {
            return $this->json([
                'success' => false,
                'message' => 'Insufficient wallet balance. Please top up first.'
            ]);
        }
        
        // Process the payment
        $db = \Config\Database::connect();
        $db->transStart();
        
        try {
            // Deduct from customer wallet
            $walletModel->deductBalance($userId, $amount);
            
            // Log customer transaction
            $walletTxModel = new WalletTransactionModel();
            $walletTxModel->addTransaction(
                $userId,
                WalletTransactionModel::TYPE_DEBIT,
                $amount,
                "Payment to {$merchant['business_name']}"
            );
            
            // Calculate fee (2.5%)
            $feeRate = $merchant['commission_rate'] ?? 2.5;
            $fee = $amount * ($feeRate / 100);
            $netAmount = $amount - $fee;
            
            // Add to merchant balance
            $merchantModel->updateBalance($merchant['id'], $netAmount);
            
            // Create transaction record
            $transactionId = 'TXN-' . strtoupper(uniqid()) . '-' . time();
            $merchantTxModel = new MerchantTransactionModel();
            $merchantTxModel->createTransaction([
                'merchant_id'    => $merchant['id'],
                'customer_id'    => $userId,
                'transaction_id' => $transactionId,
                'amount'         => $amount,
                'fee'            => $fee,
                'net_amount'     => $netAmount,
                'status'         => MerchantTransactionModel::STATUS_SUCCESS,
                'payment_method' => 'wallet',
                'description'    => $this->request->getPost('description') ?? 'Payment',
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
                'amount'         => $amount,
                'merchant'       => $merchant['business_name'],
            ]);
            
        } catch (\Exception $e) {
            $db->transRollback();
            return $this->json([
                'success' => false,
                'message' => 'Payment failed: ' . $e->getMessage()
            ]);
        }
    }
}
