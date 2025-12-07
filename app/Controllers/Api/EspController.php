<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\MerchantModel;
use App\Models\MerchantTransactionModel;
use App\Models\UserModel;
use App\Models\WalletModel;
use App\Models\WalletTransactionModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * ESP API Controller
 * Handle API requests from ESP8266/ESP32 devices
 * 
 * @property ResponseInterface $response
 */
class EspController extends BaseController
{
    /**
     * Constructor - Set headers for ESP compatibility
     */
    public function __construct()
    {
        // Log request for debugging
        log_message('info', 'ESP API Request: ' . service('request')->getPath());
        log_message('info', 'User-Agent: ' . service('request')->getUserAgent()->getAgentString());
    }

    /**
     * Return JSON response
     */
    private function json(array $data, int $status = 200): ResponseInterface
    {
        return $this->response
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, User-Agent')
            ->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->setHeader('X-Robots-Tag', 'noindex')
            ->setJSON($data)
            ->setStatusCode($status);
    }

    /**
     * Validate merchant by merchant_id
     * GET /api/esp/merchant/validate?merchant_id=MCH-XXXXXX
     */
    public function validateMerchant()
    {
        $merchantId = $this->request->getGet('merchant_id');
        
        if (!$merchantId) {
            return $this->json([
                'success' => false,
                'message' => 'Merchant ID required'
            ], 400);
        }
        
        $merchantModel = new MerchantModel();
        $merchant = $merchantModel->getByMerchantId($merchantId);
        
        if (!$merchant) {
            return $this->json([
                'success' => false,
                'message' => 'Merchant not found'
            ], 404);
        }
        
        if ($merchant['status'] !== 'active') {
            return $this->json([
                'success' => false,
                'message' => 'Merchant is not active'
            ], 403);
        }
        
        return $this->json([
            'success'       => true,
            'merchant_id'   => $merchant['merchant_id'],
            'business_name' => $merchant['business_name'],
            'business_type' => $merchant['business_type'],
            'balance'       => (float) $merchant['balance'],
        ]);
    }

    /**
     * Get merchant balance
     * GET /api/esp/merchant/balance?merchant_id=MCH-XXXXXX
     */
    public function getMerchantBalance()
    {
        $merchantId = $this->request->getGet('merchant_id');
        
        if (!$merchantId) {
            return $this->json([
                'success' => false,
                'message' => 'Merchant ID required'
            ], 400);
        }
        
        $merchantModel = new MerchantModel();
        $merchant = $merchantModel->getByMerchantId($merchantId);
        
        if (!$merchant) {
            return $this->json([
                'success' => false,
                'message' => 'Merchant not found'
            ], 404);
        }
        
        return $this->json([
            'success' => true,
            'balance' => (float) $merchant['balance'],
        ]);
    }

    /**
     * Customer auth by email - renamed endpoint for Cloudflare bypass
     * GET /endpoin/esp/customer/auth?e=email&p=password
     */
    public function customerAuth()
    {
        $email = $this->request->getGet('e') ?? $this->request->getGet('email');
        $password = $this->request->getGet('p') ?? $this->request->getGet('password');
        
        if (!$email || !$password) {
            return $this->json([
                'success' => false,
                'message' => 'Parameters required'
            ], 400);
        }
        
        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();
        
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Not found'
            ], 404);
        }
        
        if (!password_verify($password, $user['password'])) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid'
            ], 401);
        }
        
        $walletModel = new WalletModel();
        $balance = $walletModel->getBalance($user['id']);
        
        return $this->json([
            'success'     => true,
            'user_id'     => (int) $user['id'],
            'name'        => $user['name'],
            'email'       => $user['email'],
            'card_number' => $user['card_number'] ?? '',
            'balance'     => (float) $balance,
        ]);
    }

    /**
     * Customer card auth - renamed endpoint for Cloudflare bypass
     * GET /endpoin/esp/customer/card?c=card_number
     */
    public function customerCard()
    {
        $cardNumber = $this->request->getGet('c') ?? $this->request->getGet('card_number');
        
        if (!$cardNumber) {
            return $this->json([
                'success' => false,
                'message' => 'Parameter required'
            ], 400);
        }
        
        $cardNumber = str_replace(' ', '', $cardNumber);
        
        $userModel = new UserModel();
        $user = $userModel->findByCardNumber($cardNumber);
        
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Not found'
            ], 404);
        }
        
        $walletModel = new WalletModel();
        $balance = $walletModel->getBalance($user['id']);
        
        return $this->json([
            'success'     => true,
            'user_id'     => (int) $user['id'],
            'name'        => $user['name'],
            'email'       => $user['email'],
            'card_number' => $user['card_number'],
            'balance'     => (float) $balance,
        ]);
    }

    /**
     * Transaction create - renamed endpoint for Cloudflare bypass
     * GET /endpoin/esp/transaction/create?m=merchant_id&u=user_id&a=amount&d=description
     */
    public function transactionCreate()
    {
        $merchantId = $this->request->getGet('m') ?? $this->request->getGet('merchant_id');
        $userId = (int) ($this->request->getGet('u') ?? $this->request->getGet('user_id'));
        $amount = (float) ($this->request->getGet('a') ?? $this->request->getGet('amount'));
        $description = $this->request->getGet('d') ?? $this->request->getGet('description') ?? '';
        
        // Use processPayment logic
        return $this->doProcessPayment($merchantId, $userId, $amount, $description);
    }

    /**
     * Get user balance
     * GET /api/esp/user/balance?user_id=1
     */
    public function getUserBalance()
    {
        $userId = $this->request->getGet('user_id');
        
        if (!$userId) {
            return $this->json([
                'success' => false,
                'message' => 'User ID required'
            ], 400);
        }
        
        $walletModel = new WalletModel();
        $balance = $walletModel->getBalance((int) $userId);
        
        return $this->json([
            'success' => true,
            'balance' => (float) $balance,
        ]);
    }

    /**
     * Process payment from user to merchant
     * GET/POST /api/esp/payment/process
     * Params: merchant_id, user_id, amount, description
     */
    public function processPayment()
    {
        // Support both GET and POST for Cloudflare bypass
        $merchantId = $this->request->getGet('merchant_id') ?? $this->request->getPost('merchant_id');
        $userId = (int) ($this->request->getGet('user_id') ?? $this->request->getPost('user_id'));
        $amount = (float) ($this->request->getGet('amount') ?? $this->request->getPost('amount'));
        $description = $this->request->getGet('description') ?? $this->request->getPost('description') ?? '';
        
        return $this->doProcessPayment($merchantId, $userId, $amount, $description);
    }

    /**
     * Shared payment processing logic
     */
    private function doProcessPayment($merchantId, $userId, $amount, $description)
    {
        // Validate inputs
        if (!$merchantId || !$userId || $amount < 100) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid parameters'
            ], 400);
        }
        
        // Get merchant
        $merchantModel = new MerchantModel();
        $merchant = $merchantModel->getByMerchantId($merchantId);
        
        if (!$merchant) {
            return $this->json([
                'success' => false,
                'message' => 'Merchant not found'
            ], 404);
        }
        
        // Get user
        $userModel = new UserModel();
        $user = $userModel->find($userId);
        
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        
        // Check user balance
        $walletModel = new WalletModel();
        $userBalance = $walletModel->getBalance($userId);
        
        if ($userBalance < $amount) {
            return $this->json([
                'success' => false,
                'message' => 'Insufficient balance',
                'balance' => $userBalance,
            ], 400);
        }
        
        // Calculate fee
        $feeRate = $merchant['commission_rate'] ?? 2.5;
        $fee = round($amount * $feeRate / 100);
        $netAmount = $amount - $fee;
        
        // Process transaction
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
                'description'    => $description ?: 'ESP Payment to ' . $merchant['business_name'],
                'status'         => 'success',
                'payment_method' => 'esp_device',
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
            
            // Record wallet transaction for user
            $walletTxModel = new WalletTransactionModel();
            $newUserBalance = $walletModel->getBalance($userId);
            
            $walletTxModel->insert([
                'user_id'        => $userId,
                'type'           => 'debit',
                'amount'         => $amount,
                'description'    => 'Payment to ' . $merchant['business_name'] . ($description ? ': ' . $description : ''),
                'reference_type' => 'merchant_payment',
                'reference_id'   => $txModel->getInsertID(),
                'balance_before' => $userBalance,
                'balance_after'  => $newUserBalance,
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
            
            $db->transComplete();
            
            if ($db->transStatus() === false) {
                throw new \Exception('Transaction failed');
            }
            
            // Get new merchant balance
            $newMerchantBalance = $merchantModel->getByMerchantId($merchantId)['balance'];
            
            return $this->json([
                'success'          => true,
                'message'          => 'Payment successful',
                'transaction_id'   => $transactionId,
                'amount'           => $amount,
                'fee'              => $fee,
                'net_amount'       => $netAmount,
                'user_new_balance' => $newUserBalance,
                'merchant_balance' => (float) $newMerchantBalance,
            ]);
            
        } catch (\Exception $e) {
            $db->transRollback();
            return $this->json([
                'success' => false,
                'message' => 'Payment failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent transactions for merchant
     * GET /api/esp/merchant/transactions?merchant_id=MCH-XXXXXX&limit=10
     */
    public function getMerchantTransactions()
    {
        $merchantId = $this->request->getGet('merchant_id');
        $limit = (int) ($this->request->getGet('limit') ?? 10);
        
        if (!$merchantId) {
            return $this->json([
                'success' => false,
                'message' => 'Merchant ID required'
            ], 400);
        }
        
        $merchantModel = new MerchantModel();
        $merchant = $merchantModel->getByMerchantId($merchantId);
        
        if (!$merchant) {
            return $this->json([
                'success' => false,
                'message' => 'Merchant not found'
            ], 404);
        }
        
        $txModel = new MerchantTransactionModel();
        $transactions = $txModel->where('merchant_id', $merchant['id'])
                                ->orderBy('created_at', 'DESC')
                                ->limit($limit)
                                ->find();
        
        $result = [];
        foreach ($transactions as $tx) {
            $result[] = [
                'transaction_id' => $tx['transaction_id'],
                'amount'         => (float) $tx['amount'],
                'net_amount'     => (float) $tx['net_amount'],
                'description'    => $tx['description'],
                'status'         => $tx['status'],
                'created_at'     => $tx['created_at'],
            ];
        }
        
        return $this->json([
            'success'      => true,
            'transactions' => $result,
        ]);
    }
    
    /**
     * Handle OPTIONS preflight requests
     */
    public function options()
    {
        return $this->response
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type')
            ->setStatusCode(200);
    }
}
