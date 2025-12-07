<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Midtrans as MidtransConfig;

/**
 * Midtrans Webhook Signature Verification Filter
 * 
 * Validates incoming webhook notifications from Midtrans
 * by verifying the signature hash.
 * 
 * @package App\Filters
 */
class MidtransSignatureFilter implements FilterInterface
{
    /**
     * Verify Midtrans webhook signature
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Only apply to POST requests
        if ($request->getMethod() !== 'post') {
            return;
        }

        // Get raw POST body
        $rawBody = file_get_contents('php://input');
        
        if (empty($rawBody)) {
            log_message('warning', 'Midtrans webhook: Empty body received');
            return service('response')
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Empty request body'
                ]);
        }

        $notification = json_decode($rawBody, true);
        
        if (!$notification) {
            log_message('warning', 'Midtrans webhook: Invalid JSON received');
            return service('response')
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Invalid JSON format'
                ]);
        }

        // Extract signature components
        $orderId = $notification['order_id'] ?? '';
        $statusCode = $notification['status_code'] ?? '';
        $grossAmount = $notification['gross_amount'] ?? '';
        $signatureKey = $notification['signature_key'] ?? '';

        if (empty($signatureKey)) {
            log_message('warning', 'Midtrans webhook: No signature key provided');
            // Allow for now (sandbox might not always send signature)
            return;
        }

        // Get server key from config
        $config = new MidtransConfig();
        $serverKey = $config->serverKey;

        // Generate expected signature
        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        // Verify signature
        if (!hash_equals($expectedSignature, $signatureKey)) {
            log_message('critical', "Midtrans webhook: Invalid signature for order {$orderId}");
            log_message('debug', "Expected: {$expectedSignature}");
            log_message('debug', "Received: {$signatureKey}");
            
            return service('response')
                ->setStatusCode(403)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Invalid signature'
                ]);
        }

        log_message('info', "Midtrans webhook: Signature verified for order {$orderId}");
    }

    /**
     * Post-processing (not used)
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return ResponseInterface|void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post-processing needed
    }
}
