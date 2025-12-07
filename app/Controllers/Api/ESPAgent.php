<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * ESP Agent Controller
 * 
 * Handles AI Agent features for ESP devices:
 * - Offline transaction storage & sync
 * - Debt/Credit (Hutang/Piutang) management
 * - Payment request to other users
 * - Smart payment suggestions
 */
class ESPAgent extends BaseController
{
    protected $db;
    
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
    
    /**
     * =============================================
     * OFFLINE TRANSACTION SYNC
     * =============================================
     */
    
    /**
     * Sync offline transactions from ESP device
     * GET /endpoin/esp/sync/upload?data=JSON_ENCODED_TRANSACTIONS
     */
    public function syncUpload()
    {
        $data = $this->request->getGet('data');
        
        if (empty($data)) {
            return $this->jsonResponse(false, 'No data provided');
        }
        
        $transactions = json_decode(urldecode($data), true);
        
        if (!$transactions || !is_array($transactions)) {
            return $this->jsonResponse(false, 'Invalid data format');
        }
        
        $synced = 0;
        $failed = 0;
        $results = [];
        
        foreach ($transactions as $tx) {
            $result = $this->processOfflineTransaction($tx);
            if ($result['success']) {
                $synced++;
            } else {
                $failed++;
            }
            $results[] = $result;
        }
        
        return $this->jsonResponse(true, "Synced: {$synced}, Failed: {$failed}", [
            'synced' => $synced,
            'failed' => $failed,
            'details' => $results
        ]);
    }
    
    /**
     * Process a single offline transaction
     */
    private function processOfflineTransaction(array $tx): array
    {
        // Validate required fields
        if (empty($tx['merchant_id']) || empty($tx['user_id']) || empty($tx['amount'])) {
            return ['success' => false, 'message' => 'Missing required fields', 'offline_id' => $tx['offline_id'] ?? null];
        }
        
        // Check if already synced (by offline_id)
        if (!empty($tx['offline_id'])) {
            $existing = $this->db->table('offline_transactions')
                ->where('offline_id', $tx['offline_id'])
                ->where('synced', 1)
                ->get()->getRowArray();
            
            if ($existing) {
                return ['success' => true, 'message' => 'Already synced', 'offline_id' => $tx['offline_id']];
            }
        }
        
        // Get merchant
        $merchant = $this->db->table('merchants')
            ->where('merchant_id', $tx['merchant_id'])
            ->get()->getRowArray();
        
        if (!$merchant) {
            return ['success' => false, 'message' => 'Merchant not found', 'offline_id' => $tx['offline_id'] ?? null];
        }
        
        // Get user wallet
        $wallet = $this->db->table('wallets')
            ->where('user_id', $tx['user_id'])
            ->get()->getRowArray();
        
        if (!$wallet || (float)$wallet['balance'] < (float)$tx['amount']) {
            // Record as debt if insufficient balance
            return $this->createDebt($tx, $merchant, 'Transaksi offline - saldo tidak cukup');
        }
        
        // Process payment
        $this->db->transStart();
        
        try {
            $amount = (float)$tx['amount'];
            $fee = $amount * 0.01; // 1% fee
            $netAmount = $amount - $fee;
            
            // Deduct from user wallet
            $this->db->table('wallets')
                ->where('user_id', $tx['user_id'])
                ->set('balance', 'balance - ' . $amount, false)
                ->update();
            
            // Add to merchant balance
            $this->db->table('merchants')
                ->where('id', $merchant['id'])
                ->set('balance', 'balance + ' . $netAmount, false)
                ->update();
            
            // Create transaction record
            $transactionId = 'TRX-SYNC-' . date('YmdHis') . '-' . rand(1000, 9999);
            
            $this->db->table('merchant_transactions')->insert([
                'merchant_id' => $merchant['id'],
                'customer_id' => $tx['user_id'],
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'fee' => $fee,
                'net_amount' => $netAmount,
                'description' => $tx['description'] ?? 'Synced offline transaction',
                'status' => 'success',
                'payment_method' => 'offline_sync',
                'reference' => $tx['offline_id'] ?? null,
                'created_at' => $tx['timestamp'] ?? date('Y-m-d H:i:s')
            ]);
            
            // Record wallet transaction
            $newBalance = (float)$wallet['balance'] - $amount;
            $this->db->table('wallet_transactions')->insert([
                'user_id' => $tx['user_id'],
                'type' => 'debit',
                'amount' => $amount,
                'description' => 'Pembayaran ke ' . ($merchant['business_name'] ?? 'Merchant'),
                'reference_type' => 'merchant_payment',
                'reference_id' => $transactionId,
                'balance_before' => $wallet['balance'],
                'balance_after' => $newBalance,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Mark offline transaction as synced
            if (!empty($tx['offline_id'])) {
                $this->db->table('offline_transactions')->insert([
                    'offline_id' => $tx['offline_id'],
                    'merchant_id' => $merchant['id'],
                    'user_id' => $tx['user_id'],
                    'amount' => $amount,
                    'transaction_id' => $transactionId,
                    'synced' => 1,
                    'synced_at' => date('Y-m-d H:i:s'),
                    'created_at' => $tx['timestamp'] ?? date('Y-m-d H:i:s')
                ]);
            }
            
            $this->db->transComplete();
            
            if ($this->db->transStatus()) {
                return [
                    'success' => true,
                    'message' => 'Transaction synced',
                    'offline_id' => $tx['offline_id'] ?? null,
                    'transaction_id' => $transactionId,
                    'new_balance' => $newBalance
                ];
            }
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Offline sync error: ' . $e->getMessage());
        }
        
        return ['success' => false, 'message' => 'Sync failed', 'offline_id' => $tx['offline_id'] ?? null];
    }
    
    /**
     * Get pending offline transactions for a device
     * GET /endpoin/esp/sync/pending?merchant_id=XXX
     */
    public function syncPending()
    {
        $merchantId = $this->request->getGet('merchant_id');
        
        if (empty($merchantId)) {
            return $this->jsonResponse(false, 'Merchant ID required');
        }
        
        $merchant = $this->db->table('merchants')
            ->where('merchant_id', $merchantId)
            ->get()->getRowArray();
        
        if (!$merchant) {
            return $this->jsonResponse(false, 'Merchant not found');
        }
        
        // Get unsynced offline transactions
        $pending = $this->db->table('offline_transactions')
            ->where('merchant_id', $merchant['id'])
            ->where('synced', 0)
            ->orderBy('created_at', 'ASC')
            ->get()->getResultArray();
        
        return $this->jsonResponse(true, 'Pending transactions', [
            'count' => count($pending),
            'transactions' => $pending
        ]);
    }
    
    /**
     * =============================================
     * DEBT/CREDIT (HUTANG/PIUTANG) SYSTEM
     * =============================================
     */
    
    /**
     * Create a debt record (user owes merchant)
     * GET /endpoin/esp/debt/create?merchant_id=XXX&user_id=XXX&amount=XXX&description=XXX
     */
    public function createDebtEndpoint()
    {
        $merchantId = $this->request->getGet('merchant_id');
        $userId = $this->request->getGet('user_id');
        $amount = (float)$this->request->getGet('amount');
        $description = $this->request->getGet('description') ?? 'Hutang dari transaksi';
        
        if (empty($merchantId) || empty($userId) || $amount <= 0) {
            return $this->jsonResponse(false, 'Invalid parameters');
        }
        
        $merchant = $this->db->table('merchants')
            ->where('merchant_id', $merchantId)
            ->get()->getRowArray();
        
        if (!$merchant) {
            return $this->jsonResponse(false, 'Merchant not found');
        }
        
        $user = $this->db->table('users')->where('id', $userId)->get()->getRowArray();
        if (!$user) {
            return $this->jsonResponse(false, 'User not found');
        }
        
        $result = $this->createDebt([
            'merchant_id' => $merchantId,
            'user_id' => $userId,
            'amount' => $amount,
            'description' => $description
        ], $merchant, $description);
        
        return $this->jsonResponse($result['success'], $result['message'], $result);
    }
    
    /**
     * Internal function to create debt
     */
    private function createDebt(array $tx, array $merchant, string $description): array
    {
        $debtId = 'DBT-' . date('YmdHis') . '-' . rand(1000, 9999);
        
        try {
            $this->db->table('debts')->insert([
                'debt_id' => $debtId,
                'debtor_id' => $tx['user_id'], // User yang berhutang
                'creditor_type' => 'merchant',
                'creditor_id' => $merchant['id'],
                'amount' => $tx['amount'],
                'description' => $description,
                'status' => 'pending', // pending, partial, paid
                'due_date' => date('Y-m-d', strtotime('+7 days')),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Create notification for user
            $this->createNotification(
                $tx['user_id'],
                'Hutang Baru',
                'Anda memiliki hutang Rp ' . number_format($tx['amount'], 0, ',', '.') . ' ke ' . ($merchant['business_name'] ?? 'Merchant'),
                'debt'
            );
            
            return [
                'success' => true,
                'message' => 'Debt created - payment pending',
                'debt_id' => $debtId,
                'amount' => $tx['amount'],
                'offline_id' => $tx['offline_id'] ?? null
            ];
        } catch (\Exception $e) {
            log_message('error', 'Create debt error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create debt'];
        }
    }
    
    /**
     * Get user's debts
     * GET /endpoin/esp/debt/list?user_id=XXX
     */
    public function listDebts()
    {
        $userId = $this->request->getGet('user_id');
        
        if (empty($userId)) {
            return $this->jsonResponse(false, 'User ID required');
        }
        
        $debts = $this->db->table('debts d')
            ->select('d.*, m.business_name as creditor_name, m.merchant_id as creditor_merchant_id')
            ->join('merchants m', 'm.id = d.creditor_id AND d.creditor_type = "merchant"', 'left')
            ->where('d.debtor_id', $userId)
            ->where('d.status !=', 'paid')
            ->orderBy('d.created_at', 'DESC')
            ->get()->getResultArray();
        
        $totalDebt = array_sum(array_column($debts, 'amount'));
        
        return $this->jsonResponse(true, 'User debts', [
            'count' => count($debts),
            'total_debt' => $totalDebt,
            'total_debt_formatted' => 'Rp ' . number_format($totalDebt, 0, ',', '.'),
            'debts' => $debts
        ]);
    }
    
    /**
     * Pay a debt
     * GET /endpoin/esp/debt/pay?debt_id=XXX&user_id=XXX&amount=XXX
     */
    public function payDebt()
    {
        $debtId = $this->request->getGet('debt_id');
        $userId = $this->request->getGet('user_id');
        $payAmount = (float)$this->request->getGet('amount');
        
        if (empty($debtId) || empty($userId)) {
            return $this->jsonResponse(false, 'Invalid parameters');
        }
        
        $debt = $this->db->table('debts')
            ->where('debt_id', $debtId)
            ->where('debtor_id', $userId)
            ->where('status !=', 'paid')
            ->get()->getRowArray();
        
        if (!$debt) {
            return $this->jsonResponse(false, 'Debt not found');
        }
        
        // Get user wallet
        $wallet = $this->db->table('wallets')
            ->where('user_id', $userId)
            ->get()->getRowArray();
        
        if (!$wallet) {
            return $this->jsonResponse(false, 'Wallet not found');
        }
        
        // Calculate payment amount (if 0, pay full amount)
        $amountToPay = $payAmount > 0 ? $payAmount : (float)$debt['amount'];
        
        if ((float)$wallet['balance'] < $amountToPay) {
            return $this->jsonResponse(false, 'Insufficient balance', [
                'balance' => (float)$wallet['balance'],
                'required' => $amountToPay
            ]);
        }
        
        $this->db->transStart();
        
        try {
            // Deduct from user wallet
            $this->db->table('wallets')
                ->where('user_id', $userId)
                ->set('balance', 'balance - ' . $amountToPay, false)
                ->update();
            
            // Add to creditor (merchant)
            if ($debt['creditor_type'] === 'merchant') {
                $this->db->table('merchants')
                    ->where('id', $debt['creditor_id'])
                    ->set('balance', 'balance + ' . $amountToPay, false)
                    ->update();
            } else {
                // User creditor
                $this->db->table('wallets')
                    ->where('user_id', $debt['creditor_id'])
                    ->set('balance', 'balance + ' . $amountToPay, false)
                    ->update();
            }
            
            // Update debt status
            $newDebtAmount = (float)$debt['amount'] - $amountToPay;
            $newStatus = $newDebtAmount <= 0 ? 'paid' : 'partial';
            
            $this->db->table('debts')
                ->where('id', $debt['id'])
                ->update([
                    'amount' => max(0, $newDebtAmount),
                    'status' => $newStatus,
                    'paid_at' => $newStatus === 'paid' ? date('Y-m-d H:i:s') : null
                ]);
            
            // Record debt payment
            $this->db->table('debt_payments')->insert([
                'debt_id' => $debt['id'],
                'amount' => $amountToPay,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Record wallet transaction
            $newBalance = (float)$wallet['balance'] - $amountToPay;
            $this->db->table('wallet_transactions')->insert([
                'user_id' => $userId,
                'type' => 'debit',
                'amount' => $amountToPay,
                'description' => 'Pembayaran hutang: ' . $debtId,
                'reference_type' => 'debt_payment',
                'reference_id' => $debtId,
                'balance_before' => $wallet['balance'],
                'balance_after' => $newBalance,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->db->transComplete();
            
            if ($this->db->transStatus()) {
                return $this->jsonResponse(true, $newStatus === 'paid' ? 'Debt fully paid' : 'Partial payment recorded', [
                    'paid_amount' => $amountToPay,
                    'remaining' => max(0, $newDebtAmount),
                    'status' => $newStatus,
                    'new_balance' => $newBalance
                ]);
            }
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Pay debt error: ' . $e->getMessage());
        }
        
        return $this->jsonResponse(false, 'Payment failed');
    }
    
    /**
     * =============================================
     * PAYMENT REQUEST SYSTEM
     * =============================================
     */
    
    /**
     * Create payment request (minta bayar ke orang lain)
     * GET /endpoin/esp/request/create?from_user=XXX&to_user=XXX&amount=XXX&description=XXX
     */
    public function createPaymentRequest()
    {
        $fromUserId = $this->request->getGet('from_user'); // Yang minta dibayarin
        $toUserId = $this->request->getGet('to_user');     // Yang diminta bayar
        $amount = (float)$this->request->getGet('amount');
        $description = $this->request->getGet('description') ?? 'Permintaan pembayaran';
        
        // Support by email/card_number
        $toEmail = $this->request->getGet('to_email');
        $toCard = $this->request->getGet('to_card');
        
        if (empty($fromUserId) || $amount <= 0) {
            return $this->jsonResponse(false, 'Invalid parameters');
        }
        
        // Find target user
        if (!empty($toUserId)) {
            $toUser = $this->db->table('users')->where('id', $toUserId)->get()->getRowArray();
        } elseif (!empty($toEmail)) {
            $toUser = $this->db->table('users')->where('email', $toEmail)->get()->getRowArray();
        } elseif (!empty($toCard)) {
            $toUser = $this->db->table('users')->where('card_number', $toCard)->get()->getRowArray();
        } else {
            return $this->jsonResponse(false, 'Target user not specified');
        }
        
        if (!$toUser) {
            return $this->jsonResponse(false, 'Target user not found');
        }
        
        $fromUser = $this->db->table('users')->where('id', $fromUserId)->get()->getRowArray();
        if (!$fromUser) {
            return $this->jsonResponse(false, 'Requester not found');
        }
        
        $requestId = 'REQ-' . date('YmdHis') . '-' . rand(1000, 9999);
        
        try {
            $this->db->table('payment_requests')->insert([
                'request_id' => $requestId,
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUser['id'],
                'amount' => $amount,
                'description' => $description,
                'status' => 'pending', // pending, approved, rejected, expired
                'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Notify target user
            $this->createNotification(
                $toUser['id'],
                'Permintaan Pembayaran',
                $fromUser['name'] . ' meminta Anda membayar Rp ' . number_format($amount, 0, ',', '.') . ' - ' . $description,
                'payment_request'
            );
            
            return $this->jsonResponse(true, 'Payment request created', [
                'request_id' => $requestId,
                'to_user' => $toUser['name'],
                'amount' => $amount
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Create payment request error: ' . $e->getMessage());
            return $this->jsonResponse(false, 'Failed to create request');
        }
    }
    
    /**
     * Get pending payment requests for a user
     * GET /endpoin/esp/request/pending?user_id=XXX
     */
    public function getPendingRequests()
    {
        $userId = $this->request->getGet('user_id');
        
        if (empty($userId)) {
            return $this->jsonResponse(false, 'User ID required');
        }
        
        // Requests TO this user (need to pay)
        $incoming = $this->db->table('payment_requests pr')
            ->select('pr.*, u.name as from_name, u.email as from_email')
            ->join('users u', 'u.id = pr.from_user_id')
            ->where('pr.to_user_id', $userId)
            ->where('pr.status', 'pending')
            ->where('pr.expires_at >', date('Y-m-d H:i:s'))
            ->orderBy('pr.created_at', 'DESC')
            ->get()->getResultArray();
        
        // Requests FROM this user (waiting for payment)
        $outgoing = $this->db->table('payment_requests pr')
            ->select('pr.*, u.name as to_name, u.email as to_email')
            ->join('users u', 'u.id = pr.to_user_id')
            ->where('pr.from_user_id', $userId)
            ->where('pr.status', 'pending')
            ->where('pr.expires_at >', date('Y-m-d H:i:s'))
            ->orderBy('pr.created_at', 'DESC')
            ->get()->getResultArray();
        
        return $this->jsonResponse(true, 'Payment requests', [
            'incoming' => [
                'count' => count($incoming),
                'total' => array_sum(array_column($incoming, 'amount')),
                'requests' => $incoming
            ],
            'outgoing' => [
                'count' => count($outgoing),
                'total' => array_sum(array_column($outgoing, 'amount')),
                'requests' => $outgoing
            ]
        ]);
    }
    
    /**
     * Approve/Reject payment request
     * GET /endpoin/esp/request/respond?request_id=XXX&user_id=XXX&action=approve|reject
     */
    public function respondToRequest()
    {
        $requestId = $this->request->getGet('request_id');
        $userId = $this->request->getGet('user_id');
        $action = $this->request->getGet('action'); // approve or reject
        
        if (empty($requestId) || empty($userId) || !in_array($action, ['approve', 'reject'])) {
            return $this->jsonResponse(false, 'Invalid parameters');
        }
        
        $request = $this->db->table('payment_requests')
            ->where('request_id', $requestId)
            ->where('to_user_id', $userId)
            ->where('status', 'pending')
            ->get()->getRowArray();
        
        if (!$request) {
            return $this->jsonResponse(false, 'Request not found or already processed');
        }
        
        if ($action === 'reject') {
            $this->db->table('payment_requests')
                ->where('id', $request['id'])
                ->update(['status' => 'rejected', 'responded_at' => date('Y-m-d H:i:s')]);
            
            // Notify requester
            $this->createNotification(
                $request['from_user_id'],
                'Permintaan Ditolak',
                'Permintaan pembayaran Anda sebesar Rp ' . number_format($request['amount'], 0, ',', '.') . ' ditolak',
                'payment_request'
            );
            
            return $this->jsonResponse(true, 'Request rejected');
        }
        
        // Approve - process payment
        $wallet = $this->db->table('wallets')
            ->where('user_id', $userId)
            ->get()->getRowArray();
        
        if (!$wallet || (float)$wallet['balance'] < (float)$request['amount']) {
            return $this->jsonResponse(false, 'Insufficient balance', [
                'balance' => (float)($wallet['balance'] ?? 0),
                'required' => (float)$request['amount']
            ]);
        }
        
        $this->db->transStart();
        
        try {
            $amount = (float)$request['amount'];
            
            // Deduct from payer
            $this->db->table('wallets')
                ->where('user_id', $userId)
                ->set('balance', 'balance - ' . $amount, false)
                ->update();
            
            // Add to requester
            $this->db->table('wallets')
                ->where('user_id', $request['from_user_id'])
                ->set('balance', 'balance + ' . $amount, false)
                ->update();
            
            // Update request status
            $this->db->table('payment_requests')
                ->where('id', $request['id'])
                ->update(['status' => 'approved', 'responded_at' => date('Y-m-d H:i:s')]);
            
            // Record transactions
            $txId = 'TRX-REQ-' . date('YmdHis') . '-' . rand(1000, 9999);
            $newBalance = (float)$wallet['balance'] - $amount;
            
            // Payer transaction
            $this->db->table('wallet_transactions')->insert([
                'user_id' => $userId,
                'type' => 'debit',
                'amount' => $amount,
                'description' => 'Pembayaran untuk: ' . $request['description'],
                'reference_type' => 'payment_request',
                'reference_id' => $requestId,
                'balance_before' => $wallet['balance'],
                'balance_after' => $newBalance,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Requester transaction
            $requesterWallet = $this->db->table('wallets')
                ->where('user_id', $request['from_user_id'])
                ->get()->getRowArray();
            
            $this->db->table('wallet_transactions')->insert([
                'user_id' => $request['from_user_id'],
                'type' => 'credit',
                'amount' => $amount,
                'description' => 'Diterima dari permintaan pembayaran',
                'reference_type' => 'payment_request',
                'reference_id' => $requestId,
                'balance_before' => $requesterWallet['balance'] ?? 0,
                'balance_after' => ($requesterWallet['balance'] ?? 0) + $amount,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Notify requester
            $this->createNotification(
                $request['from_user_id'],
                'Pembayaran Diterima',
                'Permintaan pembayaran Rp ' . number_format($amount, 0, ',', '.') . ' telah dibayar!',
                'payment_request'
            );
            
            $this->db->transComplete();
            
            if ($this->db->transStatus()) {
                return $this->jsonResponse(true, 'Payment approved and processed', [
                    'transaction_id' => $txId,
                    'amount' => $amount,
                    'new_balance' => $newBalance
                ]);
            }
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Approve request error: ' . $e->getMessage());
        }
        
        return $this->jsonResponse(false, 'Payment processing failed');
    }
    
    /**
     * =============================================
     * AI ASSISTANT FOR ESP
     * =============================================
     */
    
    /**
     * AI Chat for ESP device
     * GET /endpoin/esp/ai/chat?user_id=XXX&message=XXX
     */
    public function aiChat()
    {
        $userId = $this->request->getGet('user_id');
        $message = urldecode($this->request->getGet('message') ?? '');
        
        if (empty($message)) {
            return $this->jsonResponse(false, 'Message required');
        }
        
        // Build context
        $context = $this->buildESPContext($userId);
        
        // Detect intent and generate response
        $response = $this->processESPAIMessage($message, $context, $userId);
        
        return $this->jsonResponse(true, 'AI Response', [
            'response' => $response['message'],
            'action' => $response['action'] ?? null,
            'data' => $response['data'] ?? null
        ]);
    }
    
    /**
     * Build ESP context for AI
     */
    private function buildESPContext(?int $userId): array
    {
        $context = ['user_id' => $userId];
        
        if ($userId) {
            // User info
            $user = $this->db->table('users')->where('id', $userId)->get()->getRowArray();
            if ($user) {
                $context['user_name'] = $user['name'];
            }
            
            // Wallet
            $wallet = $this->db->table('wallets')->where('user_id', $userId)->get()->getRowArray();
            if ($wallet) {
                $context['balance'] = (float)$wallet['balance'];
                $context['balance_formatted'] = 'Rp ' . number_format($wallet['balance'], 0, ',', '.');
            }
            
            // Debts
            $debts = $this->db->table('debts')
                ->where('debtor_id', $userId)
                ->where('status !=', 'paid')
                ->get()->getResultArray();
            $context['total_debt'] = array_sum(array_column($debts, 'amount'));
            $context['debt_count'] = count($debts);
            
            // Pending requests
            $requests = $this->db->table('payment_requests')
                ->where('to_user_id', $userId)
                ->where('status', 'pending')
                ->get()->getResultArray();
            $context['pending_requests'] = count($requests);
            $context['pending_requests_amount'] = array_sum(array_column($requests, 'amount'));
        }
        
        return $context;
    }
    
    /**
     * Process AI message for ESP
     */
    private function processESPAIMessage(string $message, array $context, ?int $userId): array
    {
        $messageLower = strtolower($message);
        
        // Intent detection
        if (preg_match('/\b(saldo|balance|uang)\b/', $messageLower)) {
            return [
                'message' => "💰 Saldo Anda: " . ($context['balance_formatted'] ?? 'Tidak tersedia'),
                'action' => 'show_balance',
                'data' => ['balance' => $context['balance'] ?? 0]
            ];
        }
        
        if (preg_match('/\b(hutang|debt|utang)\b/', $messageLower)) {
            if ($context['debt_count'] > 0) {
                return [
                    'message' => "📋 Anda memiliki {$context['debt_count']} hutang dengan total Rp " . number_format($context['total_debt'], 0, ',', '.'),
                    'action' => 'show_debts',
                    'data' => ['count' => $context['debt_count'], 'total' => $context['total_debt']]
                ];
            }
            return ['message' => '✅ Anda tidak memiliki hutang'];
        }
        
        if (preg_match('/\b(minta|request|tagih)\b.*\b(bayar|uang|transfer)\b/', $messageLower)) {
            return [
                'message' => '💸 Untuk meminta pembayaran, gunakan menu "Minta Bayar" di ESP',
                'action' => 'create_request_hint'
            ];
        }
        
        if (preg_match('/\b(pending|tertunda|menunggu)\b/', $messageLower)) {
            if ($context['pending_requests'] > 0) {
                return [
                    'message' => "⏳ Ada {$context['pending_requests']} permintaan pembayaran senilai Rp " . 
                                number_format($context['pending_requests_amount'], 0, ',', '.') . ' menunggu respon Anda',
                    'action' => 'show_pending_requests',
                    'data' => ['count' => $context['pending_requests'], 'total' => $context['pending_requests_amount']]
                ];
            }
            return ['message' => '✅ Tidak ada permintaan pembayaran yang tertunda'];
        }
        
        if (preg_match('/\b(bayar|pay)\b.*\b(hutang|debt)\b/', $messageLower)) {
            return [
                'message' => '💳 Untuk membayar hutang, pilih hutang dari daftar dan klik Bayar',
                'action' => 'pay_debt_hint'
            ];
        }
        
        // Default response
        return [
            'message' => "👋 Halo" . (isset($context['user_name']) ? " {$context['user_name']}" : "") . "! Saya AI Assistant ESP. " .
                        "Saya bisa membantu:\n" .
                        "• Cek saldo\n" .
                        "• Lihat hutang\n" .
                        "• Permintaan pembayaran\n" .
                        "• Transaksi offline\n\n" .
                        "Saldo: " . ($context['balance_formatted'] ?? 'Login untuk melihat'),
            'action' => 'help'
        ];
    }
    
    /**
     * Get AI suggestions for ESP
     * GET /endpoin/esp/ai/suggestions?user_id=XXX
     */
    public function aiSuggestions()
    {
        $userId = $this->request->getGet('user_id');
        $context = $this->buildESPContext($userId);
        
        $suggestions = [];
        
        // Smart suggestions based on context
        if (isset($context['balance']) && $context['balance'] < 50000) {
            $suggestions[] = [
                'type' => 'warning',
                'message' => 'Saldo Anda rendah (Rp ' . number_format($context['balance'], 0, ',', '.') . '). Pertimbangkan top up.',
                'action' => 'topup'
            ];
        }
        
        if ($context['debt_count'] > 0) {
            $suggestions[] = [
                'type' => 'reminder',
                'message' => 'Anda memiliki ' . $context['debt_count'] . ' hutang. Total: Rp ' . number_format($context['total_debt'], 0, ',', '.'),
                'action' => 'view_debts'
            ];
        }
        
        if ($context['pending_requests'] > 0) {
            $suggestions[] = [
                'type' => 'action',
                'message' => 'Ada ' . $context['pending_requests'] . ' permintaan pembayaran menunggu',
                'action' => 'view_requests'
            ];
        }
        
        // Default suggestion
        if (empty($suggestions)) {
            $suggestions[] = [
                'type' => 'tip',
                'message' => 'Semua baik! Gunakan ESP untuk transaksi cepat tanpa internet.',
                'action' => 'none'
            ];
        }
        
        return $this->jsonResponse(true, 'AI Suggestions', [
            'suggestions' => $suggestions
        ]);
    }
    
    /**
     * =============================================
     * HELPER FUNCTIONS
     * =============================================
     */
    
    /**
     * Create notification
     */
    private function createNotification(int $userId, string $title, string $message, string $type = 'info'): bool
    {
        try {
            $this->db->table('notifications')->insert([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * JSON response helper
     */
    private function jsonResponse(bool $success, string $message, array $data = []): ResponseInterface
    {
        $response = array_merge([
            'success' => $success,
            'message' => $message
        ], $data);
        
        return $this->response->setJSON($response);
    }
}
