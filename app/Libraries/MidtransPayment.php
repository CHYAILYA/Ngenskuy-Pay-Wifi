<?php

namespace App\Libraries;

use Config\Midtrans as MidtransConfig;

/**
 * Midtrans Payment Library
 * 
 * Handles all Midtrans Snap API interactions
 * for secure payment processing
 */
class MidtransPayment
{
    private MidtransConfig $config;

    public function __construct()
    {
        $this->config = new MidtransConfig();
    }

    /**
     * Create Snap Token for payment
     * 
     * @param string $orderId Unique order ID
     * @param float $amount Amount in IDR
     * @param array $user User data (id, name, email)
     * @return string|null Snap token or null on error
     */
    public function createSnapToken(string $orderId, float $amount, array $user): ?string
    {
        // Log config values for debugging
        log_message('debug', 'Midtrans Config - ServerKey: ' . substr($this->config->serverKey, 0, 20) . '...');
        log_message('debug', 'Midtrans Config - isProduction: ' . ($this->config->isProduction ? 'true' : 'false'));
        log_message('debug', 'Midtrans API URL: ' . $this->config->snapApiUrl);
        
        // Build callback URLs
        $baseUrl = rtrim(site_url(), '/');
        
        $params = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => (int) $amount,
            ],
            'item_details' => [
                [
                    'id'       => 'TOPUP',
                    'price'    => (int) $amount,
                    'quantity' => 1,
                    'name'     => 'Top Up Saldo Wallet',
                ],
            ],
            'customer_details' => [
                'first_name' => $user['name'] ?? 'Customer',
                'email'      => !empty($user['email']) ? $user['email'] : 'customer@example.com',
            ],
            'callbacks' => [
                'finish' => $baseUrl . '/user/topup/finish',
            ],
        ];

        $payload = json_encode($params);
        log_message('debug', 'Midtrans Request Payload: ' . $payload);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->config->snapApiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: ' . $this->config->getAuthHeader(),
            ],
            CURLOPT_SSL_VERIFYPEER => false, // Disable SSL verification for testing
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        $errno    = curl_errno($ch);
        curl_close($ch);

        log_message('debug', 'Midtrans Response HTTP Code: ' . $httpCode);
        log_message('debug', 'Midtrans Response: ' . $response);

        if ($error) {
            log_message('error', 'Midtrans CURL Error (' . $errno . '): ' . $error);
            return null;
        }

        $result = json_decode($response, true);

        if ($httpCode === 201 && isset($result['token'])) {
            log_message('info', "Midtrans Snap Token created for order: {$orderId}");
            return $result['token'];
        }

        log_message('error', 'Midtrans API Error (HTTP ' . $httpCode . '): ' . $response);
        return null;
    }

    /**
     * Verify notification signature (for webhook)
     * 
     * @param string $orderId Order ID
     * @param string $statusCode Status code
     * @param string $grossAmount Gross amount
     * @param string $signatureKey Signature from Midtrans
     * @return bool Is signature valid
     */
    public function verifySignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool
    {
        $expectedSignature = hash(
            'sha512',
            $orderId . $statusCode . $grossAmount . $this->config->serverKey
        );

        return $signatureKey === $expectedSignature;
    }

    /**
     * Get transaction status from Midtrans API
     * 
     * @param string $orderId Order ID
     * @return array|null Transaction status or null on error
     */
    public function getTransactionStatus(string $orderId): ?array
    {
        $apiUrl = $this->config->isProduction 
            ? 'https://api.midtrans.com/v2/' . $orderId . '/status'
            : 'https://api.sandbox.midtrans.com/v2/' . $orderId . '/status';
        
        log_message('debug', 'Checking transaction status for: ' . $orderId);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: ' . $this->config->getAuthHeader(),
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        log_message('debug', 'Midtrans Status Response: ' . $response);

        if ($error) {
            log_message('error', 'Midtrans Status CURL Error: ' . $error);
            return null;
        }

        $result = json_decode($response, true);
        
        if ($httpCode === 200 && $result) {
            return $result;
        }

        log_message('error', 'Midtrans Status API Error (HTTP ' . $httpCode . '): ' . $response);
        return null;
    }

    /**
     * Check if transaction is successful based on status response
     */
    public function isTransactionSuccess(array $status): bool
    {
        $transactionStatus = $status['transaction_status'] ?? '';
        $fraudStatus = $status['fraud_status'] ?? 'accept';
        
        log_message('info', "isTransactionSuccess check: transaction_status={$transactionStatus}, fraud_status={$fraudStatus}");
        
        // Credit card payment: capture status with accepted fraud
        if ($transactionStatus === 'capture') {
            $result = $fraudStatus === 'accept';
            log_message('info', "Credit card capture - fraud_status={$fraudStatus}, result=" . ($result ? 'true' : 'false'));
            return $result;
        }
        
        // Bank transfer, e-wallet, QRIS, etc: settlement status
        if ($transactionStatus === 'settlement') {
            log_message('info', "Settlement status detected - returning true");
            return true;
        }
        
        log_message('info', "Transaction not successful: status={$transactionStatus}");
        return false;
    }

    /**
     * Get Snap client key for frontend
     */
    public function getClientKey(): string
    {
        return $this->config->clientKey;
    }

    /**
     * Get Snap JS URL
     */
    public function getSnapUrl(): string
    {
        return $this->config->snapUrl;
    }

    /**
     * Handle notification status
     * 
     * @param array $notification Notification data
     * @return array Parsed notification with status
     */
    public function handleNotification(array $notification): array
    {
        $transactionStatus = $notification['transaction_status'] ?? '';
        $fraudStatus       = $notification['fraud_status'] ?? 'accept';

        $isSuccess = false;
        $isFailed  = false;
        $isPending = false;

        if ($transactionStatus === 'capture') {
            $isSuccess = ($fraudStatus === 'accept');
        } elseif ($transactionStatus === 'settlement') {
            $isSuccess = true;
        } elseif ($transactionStatus === 'pending') {
            $isPending = true;
        } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire', 'failure'])) {
            $isFailed = true;
        }

        return [
            'order_id'     => $notification['order_id'] ?? '',
            'payment_type' => $notification['payment_type'] ?? '',
            'is_success'   => $isSuccess,
            'is_failed'    => $isFailed,
            'is_pending'   => $isPending,
            'status'       => $transactionStatus,
        ];
    }
}
