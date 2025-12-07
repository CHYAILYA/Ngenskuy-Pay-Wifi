<?php

namespace App\Controllers;

use App\Models\TopUpModel;
use App\Models\WalletModel;
use App\Models\WalletTransactionModel;
use CodeIgniter\Controller;

/**
 * Midtrans Webhook Controller
 * 
 * Dedicated controller for handling Midtrans payment notifications.
 * This controller does NOT extend RESTful controller to avoid any redirect issues.
 */
class MidtransWebhook extends Controller
{
    protected TopUpModel $topUpModel;
    protected WalletModel $walletModel;
    protected WalletTransactionModel $walletTransactionModel;

    public function __construct()
    {
        $this->topUpModel             = new TopUpModel();
        $this->walletModel            = new WalletModel();
        $this->walletTransactionModel = new WalletTransactionModel();
    }

    /**
     * Simple test endpoint to verify webhook is accessible
     */
    public function test()
    {
        return $this->response
            ->setStatusCode(200)
            ->setContentType('application/json')
            ->setJSON([
                'status' => 'ok',
                'message' => 'Webhook endpoint is accessible',
                'time' => date('Y-m-d H:i:s')
            ]);
    }

    /**
     * Handle Midtrans notification webhook
     * This endpoint MUST return HTTP 200 with JSON response
     */
    public function notify()
    {
        $request = service('request');
        
        // Check if this is a GET request (redirect from Midtrans popup)
        if ($request->getMethod() === 'get') {
            return $this->handleFinishRedirect();
        }
        
        // Get raw POST data
        $json = file_get_contents('php://input');
        
        log_message('info', '=== MIDTRANS WEBHOOK RECEIVED ===');
        log_message('info', 'Raw body: ' . $json);
        
        // Always return 200 OK first to acknowledge receipt
        $response = service('response');
        $response->setStatusCode(200);
        $response->setContentType('application/json');
        
        if (empty($json)) {
            log_message('error', 'Midtrans webhook: Empty body');
            return $response->setJSON(['status' => 'error', 'message' => 'Empty body']);
        }

        $notification = json_decode($json, true);
        
        if (!$notification) {
            log_message('error', 'Midtrans webhook: Invalid JSON');
            return $response->setJSON(['status' => 'error', 'message' => 'Invalid JSON']);
        }

        $orderId           = $notification['order_id'] ?? '';
        $transactionStatus = $notification['transaction_status'] ?? '';
        $paymentType       = $notification['payment_type'] ?? '';
        $fraudStatus       = $notification['fraud_status'] ?? 'accept';
        $signatureKey      = $notification['signature_key'] ?? '';
        $statusCode        = $notification['status_code'] ?? '';
        $grossAmount       = $notification['gross_amount'] ?? '';

        log_message('info', "Midtrans webhook: order_id={$orderId}, status={$transactionStatus}, payment_type={$paymentType}");

        if (empty($orderId)) {
            log_message('error', 'Midtrans webhook: No order_id');
            return $response->setJSON(['status' => 'error', 'message' => 'No order_id']);
        }

        // Find topup record
        $topup = $this->topUpModel->where('order_id', $orderId)->first();
        
        if (!$topup) {
            log_message('error', "Midtrans webhook: Order not found - {$orderId}");
            return $response->setJSON(['status' => 'error', 'message' => 'Order not found']);
        }

        // Verify signature (optional but recommended)
        $serverKey = env('midtrans.serverKey', '');
        if ($serverKey && $signatureKey) {
            $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
            if ($signatureKey !== $expectedSignature) {
                log_message('error', "Midtrans webhook: Invalid signature for {$orderId}");
                // Still return 200 to prevent retry spam
                return $response->setJSON(['status' => 'error', 'message' => 'Invalid signature']);
            }
        }

        // Already processed
        if ($topup['status'] === 'success') {
            log_message('info', "Midtrans webhook: Order {$orderId} already processed");
            return $response->setJSON(['status' => 'ok', 'message' => 'Already processed']);
        }

        // Process based on transaction status
        if ($this->isSuccessStatus($transactionStatus, $fraudStatus)) {
            $this->processSuccessfulPayment($topup, $paymentType, $json);
            log_message('info', "Midtrans webhook: Order {$orderId} marked as SUCCESS");
            return $response->setJSON(['status' => 'ok', 'message' => 'Payment success']);
        } 
        
        if (in_array($transactionStatus, ['deny', 'cancel', 'expire', 'failure'])) {
            $newStatus = $transactionStatus === 'expire' ? 'expired' : 'failed';
            $this->topUpModel->update($topup['id'], [
                'status'            => $newStatus,
                'midtrans_response' => $json,
            ]);
            log_message('info', "Midtrans webhook: Order {$orderId} marked as {$newStatus}");
            return $response->setJSON(['status' => 'ok', 'message' => 'Status updated to ' . $newStatus]);
        }

        // Pending or other status
        log_message('info', "Midtrans webhook: Order {$orderId} status is {$transactionStatus} - no action taken");
        return $response->setJSON(['status' => 'ok', 'message' => 'Status: ' . $transactionStatus]);
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
     * Process successful payment
     */
    private function processSuccessfulPayment(array $topup, string $paymentType, string $responseJson): void
    {
        $currentBalance = $this->walletModel->getBalance($topup['user_id']);
        $newBalance     = $currentBalance + $topup['amount'];
        
        // Update top-up status
        $this->topUpModel->update($topup['id'], [
            'status'            => 'success',
            'payment_type'      => $paymentType,
            'paid_at'           => date('Y-m-d H:i:s'),
            'midtrans_response' => $responseJson,
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
        
        log_message('info', "Payment processed: user={$topup['user_id']}, amount={$topup['amount']}, new_balance={$newBalance}");
    }

    /**
     * Handle GET redirect from Midtrans popup (finish URL)
     */
    public function handleFinishRedirect()
    {
        $request = service('request');
        $session = session();
        
        $orderId           = $request->getGet('order_id');
        $transactionStatus = $request->getGet('transaction_status');
        $statusCode        = $request->getGet('status_code');

        log_message('info', "Midtrans finish redirect: order_id={$orderId}, status={$transactionStatus}, status_code={$statusCode}");

        // Jika tidak ada order_id dari query string, coba ambil dari session atau recent topup
        if (!$orderId) {
            // Coba cari topup pending terbaru dari user yang login
            $user = $session->get('user');
            if ($user) {
                $topup = $this->topUpModel
                    ->where('user_id', $user['id'])
                    ->where('status', 'pending')
                    ->orderBy('created_at', 'DESC')
                    ->first();
                
                if ($topup) {
                    $orderId = $topup['order_id'];
                }
            }
        }

        if (!$orderId) {
            $session->setFlashdata('info', 'Silakan cek status pembayaran Anda.');
            return redirect()->to('/user/topup');
        }

        $topup = $this->topUpModel->where('order_id', $orderId)->first();
        
        if (!$topup) {
            $session->setFlashdata('error', 'Data top-up tidak ditemukan');
            return redirect()->to('/user/topup');
        }

        // Jika status dari Midtrans adalah success
        if ($transactionStatus === 'settlement' || $transactionStatus === 'capture' || $statusCode === '200') {
            if ($topup['status'] === 'pending') {
                $this->processSuccessfulPayment($topup, 'midtrans_redirect', '');
            }
            $session->setFlashdata('success', 'Pembayaran berhasil! Saldo sebesar Rp ' . number_format($topup['amount'], 0, ',', '.') . ' telah ditambahkan.');
        } elseif ($transactionStatus === 'pending') {
            // Coba cek ke Midtrans API
            $session->setFlashdata('info', 'Pembayaran sedang diproses. Silakan klik tombol Cek untuk update status.');
        } elseif ($transactionStatus === 'deny' || $transactionStatus === 'cancel' || $transactionStatus === 'expire') {
            $session->setFlashdata('error', 'Pembayaran gagal atau dibatalkan.');
        } else {
            // Tidak ada status, set flag untuk auto-check di frontend
            $session->setFlashdata('auto_check_order', $orderId);
            $session->setFlashdata('info', 'Mengecek status pembayaran...');
        }

        return redirect()->to('/user/topup');
    }
}
