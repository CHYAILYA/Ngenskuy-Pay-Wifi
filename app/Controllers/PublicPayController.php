<?php

namespace App\Controllers;

use App\Models\MerchantModel;
use App\Models\MerchantTransactionModel;
use App\Models\WalletModel;
use App\Models\WalletTransactionModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Public Payment Controller
 * Handle public payment pages accessible by anyone
 */
class PublicPayController extends BaseController
{
    /**
     * Return JSON response
     */
    private function json(array $data, int $status = 200): ResponseInterface
    {
        return $this->response->setJSON($data)->setStatusCode($status);
    }

    /**
     * Public payment page - anyone can access
     */
    public function pay($merchantId)
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
    public function processPay($merchantId)
    {
        $sessionUser = session()->get('user');
        $userId = $sessionUser['id'] ?? null;
        
        if (!$userId) {
            return redirect()->to('/login?redirect=' . urlencode(current_url()));
        }
        
        $merchantModel = new MerchantModel();
        $merchant = $merchantModel->getByMerchantId($merchantId);
        
        if (!$merchant) {
            return $this->json(['success' => false, 'message' => 'Merchant not found'], 404);
        }
        
        $amount = (float) $this->request->getPost('amount');
        $description = $this->request->getPost('description') ?? '';
        
        if ($amount < 100) {
            return $this->json(['success' => false, 'message' => 'Minimum amount Rp 100']);
        }
        
        $walletModel = new WalletModel();
        $balance = $walletModel->getBalance($userId);
        
        if ($balance < $amount) {
            return $this->json([
                'success' => false, 
                'message' => 'Saldo tidak cukup. Saldo Anda: Rp ' . number_format($balance, 0, ',', '.')
            ]);
        }
        
        // Calculate fee and net amount
        $feeRate = $merchant['commission_rate'] ?? 2.5;
        $fee = round($amount * $feeRate / 100);
        $netAmount = $amount - $fee;
        
        // Start transaction
        $db = \Config\Database::connect();
        $db->transStart();
        
        try {
            // Deduct from user wallet
            $walletModel->deductBalance($userId, $amount);
            
            // Add to merchant balance
            $merchantModel->updateBalance($merchant['id'], $netAmount);
            
            // Create transaction record
            $txModel = new MerchantTransactionModel();
            $transactionId = $txModel->generateTransactionId();
            
            $txModel->insert([
                'merchant_id'    => $merchant['id'],
                'customer_id'    => $userId,
                'transaction_id' => $transactionId,
                'amount'         => $amount,
                'fee'            => $fee,
                'net_amount'     => $netAmount,
                'description'    => $description ?: 'Payment to ' . $merchant['business_name'],
                'status'         => 'success',
                'payment_method' => 'wallet',
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
            
            // Record wallet transaction for user
            $walletTxModel = new WalletTransactionModel();
            $newBalance = $walletModel->getBalance($userId);
            
            $walletTxModel->insert([
                'user_id'        => $userId,
                'type'           => 'debit',
                'amount'         => $amount,
                'description'    => 'Payment to ' . $merchant['business_name'] . ($description ? ': ' . $description : ''),
                'reference_type' => 'merchant_payment',
                'reference_id'   => $txModel->getInsertID(),
                'balance_before' => $balance,
                'balance_after'  => $newBalance,
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
                'new_balance'    => $newBalance,
            ]);
            
        } catch (\Exception $e) {
            $db->transRollback();
            return $this->json([
                'success' => false,
                'message' => 'Payment failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
