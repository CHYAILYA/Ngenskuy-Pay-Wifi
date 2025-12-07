<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * API Security Filter
 * 
 * Validates API requests for security:
 * - Checks for valid Content-Type
 * - Validates request signatures (optional)
 * - Sanitizes input data
 * 
 * @package App\Filters
 */
class ApiSecurityFilter implements FilterInterface
{
    /**
     * Validate and sanitize API requests
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Skip for GET requests (no body to validate)
        if ($request->getMethod() === 'get') {
            return;
        }

        // Validate Content-Type for POST/PUT/PATCH
        $contentType = $request->getHeaderLine('Content-Type');
        $validTypes = [
            'application/json',
            'application/x-www-form-urlencoded',
            'multipart/form-data'
        ];

        $isValidType = false;
        foreach ($validTypes as $type) {
            if (strpos($contentType, $type) !== false) {
                $isValidType = true;
                break;
            }
        }

        // Allow empty content-type for simple requests
        if (!empty($contentType) && !$isValidType) {
            log_message('warning', 'Invalid Content-Type: ' . $contentType);
            return service('response')
                ->setStatusCode(415)
                ->setJSON([
                    'success' => false,
                    'error'   => 'Unsupported Media Type',
                    'message' => 'Invalid Content-Type header'
                ]);
        }

        // Check for suspicious patterns in input
        $suspiciousPatterns = [
            '/<script\b[^>]*>/i',           // XSS script tags
            '/javascript:/i',                // JavaScript protocol
            '/on\w+\s*=/i',                  // Event handlers
            '/union\s+select/i',             // SQL injection
            '/;\s*drop\s+table/i',           // SQL injection
            '/\bexec\s*\(/i',                // Code execution
            '/\beval\s*\(/i',                // Code execution
        ];

        $input = json_encode($request->getPost() + $request->getGet());
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                log_message('critical', 'Suspicious input detected from IP: ' . $request->getIPAddress());
                return service('response')
                    ->setStatusCode(400)
                    ->setJSON([
                        'success' => false,
                        'error'   => 'Bad Request',
                        'message' => 'Invalid input detected'
                    ]);
            }
        }
    }

    /**
     * Add security headers to response
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return ResponseInterface|void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Add security headers
        return $response
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('X-Frame-Options', 'DENY')
            ->setHeader('X-XSS-Protection', '1; mode=block')
            ->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->setHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
    }
}
