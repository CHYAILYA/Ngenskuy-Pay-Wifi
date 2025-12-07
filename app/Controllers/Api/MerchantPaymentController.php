<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\MerchantModel;
use App\Models\MerchantTransactionModel;
use App\Models\WalletModel;
use App\Models\WalletTransactionModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Merchant Payment API Controller
 * Handle payment to merchants from users
 * 
 * @property ResponseInterface $response
 */
class MerchantPaymentController extends BaseController
{
    /**
     * Return JSON response
     */
    private function json(array $data, int $status = 200): ResponseInterface
    {
        return $this->response->setJSON($data)->setStatusCode($status);
    }

    /**
     * Process payment to merchant
     */
    public function pay()
    {
        // Check if user is logged in
        $sessionUser = session()->get('user');
        if (!$sessionUser) {
            return $this->json([
                'success' => false,
                'message' => 'Please login first',
            ], 401);
        }
        
        $userId = $sessionUser['id'];
        $merchantId = $this->request->getPost('merchant_id');
        $amount = (float) $this->request->getPost('amount');
        $description = $this->request->getPost('description') ?? '';
        
        // Validate
        if (!$merchantId || $amount < 100) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid merchant or amount',
            ]);
        }
        
        $merchantModel = new MerchantModel();
        $walletModel = new WalletModel();
        $walletTxModel = new WalletTransactionModel();
        $merchantTxModel = new MerchantTransactionModel();
        
        // Get merchant
        $merchant = $merchantModel->getByMerchantId($merchantId);
        if (!$merchant || $merchant['status'] !== 'active') {
            return $this->json([
                'success' => false,
                'message' => 'Merchant not found or inactive',
            ]);
        }
        
        // Check user balance
        $balance = $walletModel->getBalance($userId);
        if ($balance < $amount) {
            return $this->json([
                'success' => false,
                'message' => 'Insufficient balance. Your balance: Rp ' . number_format($balance, 0, ',', '.'),
            ]);
        }
        
        // Calculate fee (commission)
        $commissionRate = $merchant['commission_rate'] ?? 2.5;
        $fee = round($amount * $commissionRate / 100);
        $netAmount = $amount - $fee;
        
        // Generate transaction ID
        $transactionId = $merchantTxModel->generateTransactionId();
        
        try {
            $db = \Config\Database::connect();
            $db->transStart();
            
            // Deduct from user wallet
            $walletModel->deductBalance($userId, $amount);
            
            // Record user transaction
            $walletTxModel->addTransaction(
                $userId,
                WalletTransactionModel::TYPE_DEBIT,
                $amount,
                "Payment to {$merchant['business_name']}"
            );
            
            // Add to merchant balance
            $merchantModel->updateBalance($merchant['id'], $netAmount);
            
            // Record merchant transaction
            $merchantTxModel->insert([
                'merchant_id'     => $merchant['id'],
                'customer_id'     => $userId,
                'transaction_id'  => $transactionId,
                'amount'          => $amount,
                'fee'             => $fee,
                'net_amount'      => $netAmount,
                'description'     => $description,
                'status'          => MerchantTransactionModel::STATUS_SUCCESS,
                'payment_method'  => 'wallet',
                'created_at'      => date('Y-m-d H:i:s'),
            ]);
            
            $db->transComplete();
            
            if ($db->transStatus() === false) {
                throw new \Exception('Transaction failed');
            }
            
            return $this->json([
                'success'        => true,
                'message'        => 'Payment successful',
                'transaction_id' => $transactionId,
                'amount'         => $amount,
                'merchant'       => $merchant['business_name'],
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Payment failed: ' . $e->getMessage(),
            ]);
        }
    }
}
