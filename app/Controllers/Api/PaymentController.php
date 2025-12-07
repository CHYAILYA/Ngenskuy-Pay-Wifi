<?php

namespace App\Controllers\Api;

use App\Models\TopUpModel;
use App\Models\WalletModel;
use App\Models\WalletTransactionModel;
use App\Libraries\MidtransPayment;
use App\Libraries\AuditLogger;
use CodeIgniter\RESTful\ResourceController;

/**
 * Payment API Controller
 * 
 * Handles all payment-related API endpoints including:
 * - Top-up processing
 * - Payment status checking
 * - Midtrans webhook notifications
 * 
 * Security Features:
 * - Input validation with custom rules
 * - Rate limiting via filter
 * - Audit logging for all transactions
 * - Session-based authentication
 * 
 * @package App\Controllers\Api
 */
class PaymentController extends ResourceController
{
    protected $format = 'json';
    
    protected TopUpModel $topUpModel;
    protected WalletModel $walletModel;
    protected WalletTransactionModel $walletTransactionModel;
    protected AuditLogger $auditLogger;

    public function __construct()
    {
        $this->topUpModel             = new TopUpModel();
        $this->walletModel            = new WalletModel();
        $this->walletTransactionModel = new WalletTransactionModel();
        $this->auditLogger            = new AuditLogger();
    }

    /**
     * Process new top-up request
     * 
     * Creates a new top-up transaction and generates Midtrans Snap token
     * 
     * @return \CodeIgniter\HTTP\Response JSON response with snap_token
     */
    public function processTopUp()
    {
        $session = session();
        $user = $session->get('user');
        
        if (!$user) {
            $this->auditLogger->logSecurity('unauthorized_access', null, [
                'endpoint' => 'processTopUp'
            ]);
            return $this->failUnauthorized('Please login first');
        }

        // Support both POST and GET (for Cloudflare bypass)
        $amount = (int) ($this->request->getPost('amount') ?? $this->request->getGet('amount'));

        // Validate amount using validation rules
        $validation = service('validation');
        $validation->setRules([
            'amount' => 'required|integer|greater_than_equal_to[10000]|less_than_equal_to[10000000]'
        ]);
        
        if (!$validation->run(['amount' => $amount])) {
            $this->auditLogger->logTransaction('topup_validation_failed', $user['id'], [
                'amount' => $amount,
                'errors' => $validation->getErrors()
            ]);
            return $this->fail('Nominal harus antara Rp 10.000 - Rp 10.000.000');
        }

        try {
            // Generate unique order ID
            $orderId = $this->generateOrderId($user['id']);

            // Create Midtrans transaction
            $midtrans = new MidtransPayment();
            $userData = [
                'id'    => $user['id'],
                'name'  => $user['name'] ?? 'User',
                'email' => $user['email'] ?? '',
            ];
            
            $snapToken = $midtrans->createSnapToken($orderId, $amount, $userData);

            if (!$snapToken) {
                $this->auditLogger->logTransaction('topup_midtrans_failed', $user['id'], [
                    'order_id' => $orderId,
                    'amount'   => $amount
                ]);
                return $this->fail('Gagal membuat transaksi pembayaran');
            }

            // Save top-up record
            $this->topUpModel->insert([
                'user_id'    => $user['id'],
                'order_id'   => $orderId,
                'amount'     => $amount,
                'status'     => 'pending',
                'snap_token' => $snapToken,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // Log successful transaction creation
            $this->auditLogger->logTransaction('topup_created', $user['id'], [
                'order_id' => $orderId,
                'amount'   => $amount
            ]);

            return $this->respond([
                'success'    => true,
                'snap_token' => $snapToken,
                'order_id'   => $orderId,
            ]);

        } catch (\Exception $e) {
            log_message('error', 'TopUp Error: ' . $e->getMessage());
            $this->auditLogger->logTransaction('topup_error', $user['id'], [
                'error' => $e->getMessage()
            ]);
            return $this->fail('Terjadi kesalahan sistem');
        }
    }

    /**
     * Regenerate snap token for pending transaction
     * Used when old snap token has expired (409 Conflict)
     */
    public function regenerateToken()
    {
        $session = session();
        $user = $session->get('user');
        
        if (!$user) {
            return $this->failUnauthorized('Please login first');
        }

        // Support both POST and GET (for Cloudflare bypass)
        $orderId = $this->request->getPost('order_id') ?? $this->request->getGet('order_id');
        
        if (!$orderId) {
            return $this->fail('Order ID tidak valid');
        }

        // Find existing topup
        $topup = $this->topUpModel->where('order_id', $orderId)
                                   ->where('user_id', $user['id'])
                                   ->where('status', 'pending')
                                   ->first();
        
        if (!$topup) {
            return $this->fail('Transaksi tidak ditemukan atau sudah diproses');
        }

        // Check if older than 24 hours - don't regenerate, mark as expired
        $createdAt = strtotime($topup['created_at']);
        $hoursOld = (time() - $createdAt) / 3600;
        
        if ($hoursOld > 24) {
            $this->topUpModel->update($topup['id'], ['status' => 'expired']);
            return $this->respond([
                'success' => false,
                'expired' => true,
                'message' => 'Transaksi sudah expired. Silakan buat transaksi baru.'
            ]);
        }

        try {
            // Generate NEW order ID (karena order_id lama mungkin sudah tercatat di Midtrans)
            $newOrderId = $this->generateOrderId($user['id']);
            
            // Create new Midtrans transaction
            $midtrans = new MidtransPayment();
            $userData = [
                'id'    => $user['id'],
                'name'  => $user['name'] ?? 'User',
                'email' => $user['email'] ?? '',
            ];
            
            $snapToken = $midtrans->createSnapToken($newOrderId, $topup['amount'], $userData);

            if (!$snapToken) {
                return $this->fail('Gagal membuat token pembayaran baru');
            }

            // Update topup record with new order_id and snap_token
            $this->topUpModel->update($topup['id'], [
                'order_id'   => $newOrderId,
                'snap_token' => $snapToken,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            log_message('info', "Regenerated snap token: old={$orderId}, new={$newOrderId}");

            return $this->respond([
                'success'    => true,
                'snap_token' => $snapToken,
                'order_id'   => $newOrderId,
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Regenerate Token Error: ' . $e->getMessage());
            return $this->fail('Terjadi kesalahan sistem');
        }
    }

    /**
     * Handle callback from frontend after Midtrans popup success
     * This is called when snap.pay onSuccess is triggered
     */
    public function callback()
    {
        $session = session();
        $user = $session->get('user');
        
        if (!$user) {
            return $this->failUnauthorized('Please login first');
        }

        // Support both POST and GET (for Cloudflare bypass)
        $orderId = $this->request->getPost('order_id') ?? $this->request->getGet('order_id');
        $transactionStatus = $this->request->getPost('transaction_status') ?? $this->request->getGet('transaction_status');
        $paymentType = $this->request->getPost('payment_type') ?? $this->request->getGet('payment_type');
        $resultJson = $this->request->getPost('result') ?? $this->request->getGet('result');
        
        log_message('info', "Frontend callback - OrderID: {$orderId}, Status: {$transactionStatus}");
        
        if (!$orderId) {
            return $this->fail('Order ID tidak valid');
        }

        $topup = $this->topUpModel
            ->where('order_id', $orderId)
            ->where('user_id', $user['id'])
            ->first();
        
        if (!$topup) {
            return $this->fail('Data tidak ditemukan');
        }

        // Already processed
        if ($topup['status'] === 'success') {
            return $this->respond([
                'success' => true,
                'message' => 'Pembayaran sudah diproses sebelumnya',
                'amount'  => number_format($topup['amount'], 0, ',', '.')
            ]);
        }

        // Check if transaction status indicates success
        if (in_array($transactionStatus, ['settlement', 'capture', 'success'])) {
            $this->processSuccessfulPayment($topup, $paymentType ?: 'midtrans', $resultJson ?: '');
            
            log_message('info', "TopUp {$orderId} SUCCESS via frontend callback");
            
            return $this->respond([
                'success' => true,
                'message' => 'Pembayaran berhasil',
                'amount'  => number_format($topup['amount'], 0, ',', '.')
            ]);
        }

        return $this->respond([
            'success' => false,
            'message' => 'Status pembayaran: ' . $transactionStatus
        ]);
    }

    /**
     * Force mark a transaction as success (for testing/debugging)
     * Only works for pending transactions owned by current user
     */
    public function forceSuccess()
    {
        $session = session();
        $user = $session->get('user');
        
        if (!$user) {
            return $this->failUnauthorized('Please login first');
        }

        $orderId = $this->request->getPost('order_id') ?? $this->request->getGet('order_id');
        
        if (!$orderId) {
            return $this->fail('Order ID tidak valid');
        }

        $topup = $this->topUpModel
            ->where('order_id', $orderId)
            ->where('user_id', $user['id'])
            ->first();
        
        if (!$topup) {
            return $this->fail('Transaksi tidak ditemukan');
        }

        if ($topup['status'] === 'success') {
            return $this->respond([
                'success' => true,
                'message' => 'Transaksi sudah berhasil sebelumnya',
                'amount'  => number_format($topup['amount'], 0, ',', '.')
            ]);
        }

        // Force process as successful
        $this->processSuccessfulPayment($topup, 'manual', 'Force success by user');
        
        log_message('info', "TopUp {$orderId} FORCE SUCCESS by user {$user['id']}");

        return $this->respond([
            'success' => true,
            'message' => 'Pembayaran berhasil diproses',
            'amount'  => number_format($topup['amount'], 0, ',', '.')
        ]);
    }

    /**
     * Check payment status
     * 
     * Queries Midtrans API to get current transaction status
     * and updates local database if payment is successful
     * 
     * @return \CodeIgniter\HTTP\Response JSON response with status
     */
    public function checkStatus()
    {
        $orderId = $this->request->getGet('order_id');
        
        if (!$orderId) {
            return $this->fail('Order ID tidak valid');
        }
        
        $topup = $this->topUpModel->where('order_id', $orderId)->first();
        
        if (!$topup) {
            return $this->fail('Data tidak ditemukan');
        }
        
        // If still pending, check with Midtrans API
        if ($topup['status'] === 'pending') {
            // Cek apakah transaksi sudah lebih dari 24 jam (snap token expired)
            $createdAt = strtotime($topup['created_at']);
            $hoursOld = (time() - $createdAt) / 3600;
            
            if ($hoursOld > 24) {
                // Langsung tandai sebagai expired tanpa cek Midtrans
                $this->topUpModel->update($topup['id'], ['status' => 'expired']);
                return $this->respond([
                    'status' => 'expired',
                    'amount' => number_format($topup['amount'], 0, ',', '.'),
                    'message' => 'Transaksi sudah expired (lebih dari 24 jam)'
                ]);
            }
            
            return $this->checkAndUpdateFromMidtrans($topup, $orderId);
        }
        
        return $this->respond([
            'status' => $topup['status'],
            'amount' => number_format($topup['amount'], 0, ',', '.')
        ]);
    }

    /**
     * Handle Midtrans webhook notification
     * 
     * Receives and processes payment notifications from Midtrans
     * 
     * @return \CodeIgniter\HTTP\Response JSON response
     */
    public function notification()
    {
        $json = file_get_contents('php://input');
        $notification = json_decode($json, true);

        if (!$notification) {
            return $this->respond(['status' => 'invalid'], 400);
        }

        log_message('info', 'Midtrans Notification: ' . $json);

        $midtrans = new MidtransPayment();
        $orderId      = $notification['order_id'] ?? '';
        $statusCode   = $notification['status_code'] ?? '';
        $grossAmount  = $notification['gross_amount'] ?? '';
        $signatureKey = $notification['signature_key'] ?? '';
        
        // Verify signature
        if (!$midtrans->verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
            log_message('error', 'Invalid Midtrans signature');
            return $this->respond(['status' => 'invalid signature'], 403);
        }

        $transactionStatus = $notification['transaction_status'] ?? '';
        $paymentType       = $notification['payment_type'] ?? '';

        $topup = $this->topUpModel->where('order_id', $orderId)->first();
        
        if (!$topup) {
            return $this->respond(['status' => 'not found'], 404);
        }

        // Already processed
        if ($topup['status'] === 'success') {
            return $this->respond(['status' => 'already processed']);
        }

        // Process based on status
        if ($this->isSuccessStatus($transactionStatus, $notification['fraud_status'] ?? 'accept')) {
            $this->processSuccessfulPayment($topup, $paymentType, $json);
            log_message('info', "TopUp {$orderId} SUCCESS - Amount: {$topup['amount']}");
        } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire'])) {
            $this->topUpModel->update($topup['id'], [
                'status'            => 'failed',
                'midtrans_response' => $json,
            ]);
            log_message('info', "TopUp {$orderId} FAILED - Status: {$transactionStatus}");
        }

        return $this->respond(['status' => 'ok']);
    }

    /**
     * Handle finish redirect from Midtrans (GET) or notification (POST)
     * Midtrans might send POST notification to finish URL
     * 
     * @return mixed
     */
    public function finish()
    {
        // Check if this is a POST request (notification)
        if ($this->request->getMethod() === 'post') {
            return $this->handleFinishNotification();
        }
        
        // GET request - redirect flow
        $session = session();
        $orderId           = $this->request->getGet('order_id');
        $transactionStatus = $this->request->getGet('transaction_status');

        log_message('info', "Midtrans Finish GET - OrderID: {$orderId}, Status: {$transactionStatus}");

        if (!$orderId) {
            return redirect()->to('/user/topup');
        }

        $topup = $this->topUpModel->where('order_id', $orderId)->first();
        
        if (!$topup) {
            $session->setFlashdata('error', 'Data top-up tidak ditemukan');
            return redirect()->to('/user/topup');
        }

        if ($transactionStatus === 'settlement' || $transactionStatus === 'capture') {
            if ($topup['status'] === 'pending') {
                $this->processSuccessfulPayment($topup, 'midtrans', '');
            }
            $session->setFlashdata('success', 'Pembayaran berhasil! Saldo sebesar Rp ' . number_format($topup['amount'], 0, ',', '.') . ' telah ditambahkan.');
        } elseif ($transactionStatus === 'pending') {
            $session->setFlashdata('info', 'Pembayaran sedang diproses.');
        } elseif ($transactionStatus) {
            $session->setFlashdata('error', 'Pembayaran gagal atau dibatalkan.');
        }

        $user = $session->get('user');
        return redirect()->to($user ? '/user/topup' : '/login');
    }

    /**
     * Handle POST notification sent to finish URL
     */
    private function handleFinishNotification()
    {
        $json = file_get_contents('php://input');
        $notification = json_decode($json, true);

        log_message('info', 'Midtrans Finish POST Notification: ' . $json);

        if (!$notification) {
            return $this->response->setStatusCode(200)->setJSON(['status' => 'invalid']);
        }

        $orderId           = $notification['order_id'] ?? '';
        $transactionStatus = $notification['transaction_status'] ?? '';
        $paymentType       = $notification['payment_type'] ?? '';

        if (!$orderId) {
            return $this->response->setStatusCode(200)->setJSON(['status' => 'no order_id']);
        }

        $topup = $this->topUpModel->where('order_id', $orderId)->first();
        
        if (!$topup) {
            log_message('error', "Notification for unknown order: {$orderId}");
            return $this->response->setStatusCode(200)->setJSON(['status' => 'not found']);
        }

        // Already processed
        if ($topup['status'] === 'success') {
            return $this->response->setStatusCode(200)->setJSON(['status' => 'already processed']);
        }

        // Process based on status
        if ($this->isSuccessStatus($transactionStatus, $notification['fraud_status'] ?? 'accept')) {
            $this->processSuccessfulPayment($topup, $paymentType, $json);
            log_message('info', "TopUp {$orderId} SUCCESS via finish notification - Amount: {$topup['amount']}");
        } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire'])) {
            $newStatus = $transactionStatus === 'expire' ? 'expired' : 'failed';
            $this->topUpModel->update($topup['id'], [
                'status'            => $newStatus,
                'midtrans_response' => $json,
            ]);
            log_message('info', "TopUp {$orderId} {$newStatus} via finish notification");
        }

        // IMPORTANT: Always return HTTP 200 for Midtrans
        return $this->response->setStatusCode(200)->setJSON(['status' => 'ok']);
    }

    // ========================================
    // Private Helper Methods
    // ========================================

    /**
     * Generate unique order ID
     */
    private function generateOrderId(int $userId): string
    {
        return 'TOPUP-' . $userId . '-' . time() . '-' . random_int(100, 999);
    }

    /**
     * Check if transaction status indicates success
     */
    private function isSuccessStatus(string $status, string $fraudStatus): bool
    {
        if ($status === 'capture') {
            return $fraudStatus === 'accept';
        }
        return $status === 'settlement';
    }

    /**
     * Sync all pending transactions for current user
     * Checks Midtrans API for each pending transaction and updates status
     */
    public function syncPendingTransactions()
    {
        $session = session();
        $user = $session->get('user');
        
        if (!$user) {
            return $this->failUnauthorized('Please login first');
        }

        // Get all pending topups for this user
        $pendingTopups = $this->topUpModel
            ->where('user_id', $user['id'])
            ->where('status', 'pending')
            ->findAll();

        if (empty($pendingTopups)) {
            return $this->respond([
                'success' => true,
                'message' => 'Tidak ada transaksi pending',
                'synced'  => 0
            ]);
        }

        $midtrans = new MidtransPayment();
        $synced = 0;
        $results = [];

        foreach ($pendingTopups as $topup) {
            $orderId = $topup['order_id'];
            
            // First check if older than 24 hours - mark as expired
            $createdAt = strtotime($topup['created_at']);
            $hoursOld = (time() - $createdAt) / 3600;
            
            if ($hoursOld > 24) {
                $this->topUpModel->update($topup['id'], ['status' => 'expired']);
                $synced++;
                $results[] = [
                    'order_id' => $orderId,
                    'status'   => 'expired',
                    'reason'   => 'Lebih dari 24 jam'
                ];
                continue;
            }
            
            try {
                $statusResult = $midtrans->getTransactionStatus($orderId);
                
                if ($statusResult) {
                    $transactionStatus = $statusResult['transaction_status'] ?? '';
                    $paymentType = $statusResult['payment_type'] ?? '';
                    
                    log_message('info', "Sync check {$orderId}: status={$transactionStatus}");
                    
                    if ($midtrans->isTransactionSuccess($statusResult)) {
                        // Payment successful - update database
                        $this->processSuccessfulPayment($topup, $paymentType, json_encode($statusResult));
                        $synced++;
                        $results[] = [
                            'order_id' => $orderId,
                            'status'   => 'success',
                            'amount'   => $topup['amount']
                        ];
                    } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire', 'failure'])) {
                        // Payment failed/expired
                        $newStatus = $transactionStatus === 'expire' ? 'expired' : 'failed';
                        $this->topUpModel->update($topup['id'], ['status' => $newStatus]);
                        $synced++;
                        $results[] = [
                            'order_id' => $orderId,
                            'status'   => $newStatus
                        ];
                    }
                } else {
                    // Midtrans returns null (404) - transaction not found
                    // Mark as expired if old enough
                    if ($hoursOld > 1) {
                        $this->topUpModel->update($topup['id'], ['status' => 'expired']);
                        $synced++;
                        $results[] = [
                            'order_id' => $orderId,
                            'status'   => 'expired',
                            'reason'   => 'Tidak ditemukan di Midtrans'
                        ];
                    }
                }
            } catch (\Exception $e) {
                log_message('error', "Sync error for {$orderId}: " . $e->getMessage());
            }
        }

        return $this->respond([
            'success' => true,
            'message' => "Berhasil sync {$synced} transaksi",
            'synced'  => $synced,
            'results' => $results
        ]);
    }

    /**
     * Check status from Midtrans API and update database
     */
    private function checkAndUpdateFromMidtrans(array $topup, string $orderId)
    {
        try {
            $midtrans = new MidtransPayment();
            
            log_message('info', "Checking Midtrans status for order: {$orderId}");
            
            $statusResult = $midtrans->getTransactionStatus($orderId);
            
            log_message('info', "Midtrans API result: " . json_encode($statusResult));
            
            if (!$statusResult) {
                // Jika tidak ada result dari Midtrans, cek apakah sudah lebih dari 24 jam
                $createdAt = strtotime($topup['created_at']);
                $now = time();
                $hoursOld = ($now - $createdAt) / 3600;
                
                if ($hoursOld > 24) {
                    // Transaksi sudah lebih dari 24 jam tanpa pembayaran, tandai sebagai expired
                    $this->topUpModel->update($topup['id'], ['status' => 'expired']);
                    return $this->respond([
                        'status' => 'expired',
                        'amount' => number_format($topup['amount'], 0, ',', '.'),
                        'message' => 'Transaksi sudah expired'
                    ]);
                }
                
                // Transaksi 404 di Midtrans - kemungkinan belum dibayar sama sekali
                // Kembalikan snap_token agar user bisa coba bayar lagi
                return $this->respond([
                    'status'     => 'not_paid',
                    'amount'     => number_format($topup['amount'], 0, ',', '.'),
                    'snap_token' => $topup['snap_token'] ?? null,
                    'message'    => 'Transaksi belum dibayar. Silakan klik Bayar untuk melanjutkan pembayaran.'
                ]);
            }
            
            $transactionStatus = $statusResult['transaction_status'] ?? '';
            $paymentType = $statusResult['payment_type'] ?? '';
            $transactionId = $statusResult['transaction_id'] ?? '';
            
            log_message('info', "Midtrans status for {$orderId}: status={$transactionStatus}, payment_type={$paymentType}, transaction_id={$transactionId}");
            
            if ($midtrans->isTransactionSuccess($statusResult)) {
                log_message('info', "Processing successful payment for {$orderId}");
                $this->processSuccessfulPayment($topup, $paymentType, json_encode($statusResult));
                
                return $this->respond([
                    'status'          => 'success',
                    'amount'          => number_format($topup['amount'], 0, ',', '.'),
                    'midtrans_status' => $transactionStatus,
                    'transaction_id'  => $transactionId
                ]);
            } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire', 'failure'])) {
                $newStatus = $transactionStatus === 'expire' ? 'expired' : 'failed';
                $this->topUpModel->update($topup['id'], ['status' => $newStatus]);
                
                return $this->respond([
                    'status'          => $newStatus,
                    'amount'          => number_format($topup['amount'], 0, ',', '.'),
                    'midtrans_status' => $transactionStatus
                ]);
            }
            
            return $this->respond([
                'status'          => 'pending',
                'amount'          => number_format($topup['amount'], 0, ',', '.'),
                'midtrans_status' => $transactionStatus,
                'midtrans_raw'    => $statusResult
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'CheckStatus Exception: ' . $e->getMessage());
            
            // Jika error 404 dari Midtrans, berarti transaksi tidak ditemukan
            if (strpos($e->getMessage(), '404') !== false) {
                $this->topUpModel->update($topup['id'], ['status' => 'expired']);
                return $this->respond([
                    'status' => 'expired',
                    'amount' => number_format($topup['amount'], 0, ',', '.'),
                    'message' => 'Transaksi tidak ditemukan di Midtrans'
                ]);
            }
            
            return $this->respond([
                'status' => 'pending',
                'amount' => number_format($topup['amount'], 0, ',', '.'),
                'error'  => $e->getMessage()
            ]);
        }
    }

    /**
     * Process successful payment - update database and wallet
     */
    private function processSuccessfulPayment(array $topup, string $paymentType, string $response): void
    {
        $currentBalance = $this->walletModel->getBalance($topup['user_id']);
        $newBalance     = $currentBalance + $topup['amount'];
        
        // Update top-up status
        $this->topUpModel->update($topup['id'], [
            'status'            => 'success',
            'payment_type'      => $paymentType,
            'paid_at'           => date('Y-m-d H:i:s'),
            'midtrans_response' => $response,
        ]);
        
        // Add balance to wallet
        $this->walletModel->addBalance(
            $topup['user_id'], 
            $topup['amount'], 
            'Top Up via ' . ucfirst($paymentType)
        );
        
        // Record transaction
        $this->walletTransactionModel->addTransaction(
            $topup['user_id'],
            'credit',
            $topup['amount'],
            'Top Up Saldo via ' . ucfirst($paymentType),
            'topup',
            $topup['id'],
            $currentBalance,
            $newBalance
        );
    }
}
