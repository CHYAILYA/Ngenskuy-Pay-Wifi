<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Rate Limiting Filter
 * 
 * Prevents abuse by limiting the number of requests per time window.
 * Uses cache to track request counts per IP address.
 * 
 * @package App\Filters
 */
class RateLimitFilter implements FilterInterface
{
    /**
     * Maximum requests allowed per window
     */
    protected int $maxRequests = 60;

    /**
     * Time window in seconds
     */
    protected int $windowSeconds = 60;

    /**
     * Check rate limit before processing request
     *
     * @param RequestInterface $request
     * @param array|null       $arguments Format: ['maxRequests', 'windowSeconds']
     *
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Parse arguments if provided
        if ($arguments !== null && count($arguments) >= 2) {
            $this->maxRequests = (int) $arguments[0];
            $this->windowSeconds = (int) $arguments[1];
        }

        $cache = service('cache');
        $ip = $request->getIPAddress();
        $path = $request->getPath();
        
        // Create unique key for this IP and path
        $key = 'ratelimit_' . md5($ip . '_' . $path);
        
        // Get current request count
        $data = $cache->get($key);
        
        if ($data === null) {
            // First request in this window
            $data = [
                'count'   => 1,
                'expires' => time() + $this->windowSeconds
            ];
            $cache->save($key, $data, $this->windowSeconds);
        } else {
            // Check if window has expired
            if (time() > $data['expires']) {
                // Reset counter
                $data = [
                    'count'   => 1,
                    'expires' => time() + $this->windowSeconds
                ];
                $cache->save($key, $data, $this->windowSeconds);
            } else {
                // Increment counter
                $data['count']++;
                $cache->save($key, $data, $data['expires'] - time());
                
                // Check if limit exceeded
                if ($data['count'] > $this->maxRequests) {
                    $retryAfter = $data['expires'] - time();
                    
                    log_message('warning', "Rate limit exceeded for IP: {$ip}, Path: {$path}");
                    
                    return service('response')
                        ->setStatusCode(429)
                        ->setHeader('Retry-After', (string) $retryAfter)
                        ->setHeader('X-RateLimit-Limit', (string) $this->maxRequests)
                        ->setHeader('X-RateLimit-Remaining', '0')
                        ->setHeader('X-RateLimit-Reset', (string) $data['expires'])
                        ->setJSON([
                            'success' => false,
                            'error'   => 'Too Many Requests',
                            'message' => "Rate limit exceeded. Please try again in {$retryAfter} seconds.",
                            'retry_after' => $retryAfter
                        ]);
                }
            }
        }

        // Add rate limit headers to response
        service('response')
            ->setHeader('X-RateLimit-Limit', (string) $this->maxRequests)
            ->setHeader('X-RateLimit-Remaining', (string) max(0, $this->maxRequests - $data['count']))
            ->setHeader('X-RateLimit-Reset', (string) $data['expires']);
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
