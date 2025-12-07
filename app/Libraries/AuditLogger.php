<?php

namespace App\Libraries;

/**
 * Audit Logger Library
 * 
 * Provides comprehensive audit logging for security-sensitive operations.
 * Logs are stored in the writable/logs directory with daily rotation.
 * 
 * @package App\Libraries
 */
class AuditLogger
{
    /**
     * Log directory path
     */
    protected string $logPath;

    /**
     * Current request instance
     */
    protected $request;

    public function __construct()
    {
        $this->logPath = WRITEPATH . 'logs/';
        $this->request = service('request');
    }

    /**
     * Log authentication events
     *
     * @param string $action Action performed (login, logout, failed_login)
     * @param int|null $userId User ID if available
     * @param array $extra Additional data to log
     * @return void
     */
    public function logAuth(string $action, ?int $userId = null, array $extra = []): void
    {
        $this->log('auth', $action, $userId, $extra);
    }

    /**
     * Log payment/transaction events
     *
     * @param string $action Action performed (topup, transfer, payment)
     * @param int|null $userId User ID
     * @param array $extra Additional data (amount, order_id, etc.)
     * @return void
     */
    public function logTransaction(string $action, ?int $userId = null, array $extra = []): void
    {
        $this->log('transaction', $action, $userId, $extra);
    }

    /**
     * Log security events
     *
     * @param string $action Action (rate_limit, invalid_signature, suspicious_activity)
     * @param int|null $userId User ID if available
     * @param array $extra Additional data
     * @return void
     */
    public function logSecurity(string $action, ?int $userId = null, array $extra = []): void
    {
        $this->log('security', $action, $userId, $extra);
    }

    /**
     * Log API access
     *
     * @param string $endpoint Endpoint accessed
     * @param int|null $userId User ID if authenticated
     * @param array $extra Additional data
     * @return void
     */
    public function logApiAccess(string $endpoint, ?int $userId = null, array $extra = []): void
    {
        $extra['endpoint'] = $endpoint;
        $this->log('api', 'access', $userId, $extra);
    }

    /**
     * Core logging method
     *
     * @param string $category Log category
     * @param string $action Action performed
     * @param int|null $userId User ID
     * @param array $extra Additional data
     * @return void
     */
    protected function log(string $category, string $action, ?int $userId, array $extra): void
    {
        $logData = [
            'timestamp'  => date('Y-m-d H:i:s'),
            'category'   => $category,
            'action'     => $action,
            'user_id'    => $userId,
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->getAgentString(),
            'uri'        => current_url(),
            'method'     => $this->request->getMethod(),
            'data'       => $extra,
        ];

        // Mask sensitive data
        if (isset($logData['data']['password'])) {
            $logData['data']['password'] = '***MASKED***';
        }
        if (isset($logData['data']['card_number'])) {
            $logData['data']['card_number'] = $this->maskString($logData['data']['card_number']);
        }

        // Write to file
        $filename = $this->logPath . "audit-{$category}-" . date('Y-m-d') . '.log';
        $line = json_encode($logData) . PHP_EOL;
        
        file_put_contents($filename, $line, FILE_APPEND | LOCK_EX);

        // Also log to CodeIgniter logger
        log_message('info', "AUDIT [{$category}] {$action}: " . json_encode($extra));
    }

    /**
     * Mask sensitive string data
     *
     * @param string $value Value to mask
     * @param int $visibleChars Characters to show at start/end
     * @return string Masked value
     */
    protected function maskString(string $value, int $visibleChars = 4): string
    {
        $length = strlen($value);
        
        if ($length <= $visibleChars * 2) {
            return str_repeat('*', $length);
        }
        
        return substr($value, 0, $visibleChars) 
             . str_repeat('*', $length - $visibleChars * 2) 
             . substr($value, -$visibleChars);
    }

    /**
     * Get audit logs for a specific date and category
     *
     * @param string $category Log category
     * @param string $date Date in Y-m-d format
     * @return array Array of log entries
     */
    public function getLogs(string $category, string $date): array
    {
        $filename = $this->logPath . "audit-{$category}-{$date}.log";
        
        if (!file_exists($filename)) {
            return [];
        }

        $logs = [];
        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            $entry = json_decode($line, true);
            if ($entry) {
                $logs[] = $entry;
            }
        }

        return $logs;
    }
}
