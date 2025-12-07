<?php

/**
 * Security Helper Functions
 * 
 * Provides common security utilities for input validation,
 * sanitization, and security checks.
 * 
 * @package App\Helpers
 */

if (!function_exists('sanitize_input')) {
    /**
     * Sanitize user input to prevent XSS attacks
     *
     * @param string|array $input Input to sanitize
     * @return string|array Sanitized input
     */
    function sanitize_input(string|array $input): string|array
    {
        if (is_array($input)) {
            return array_map('sanitize_input', $input);
        }
        
        // Remove null bytes
        $input = str_replace(chr(0), '', $input);
        
        // Convert special characters to HTML entities
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return trim($input);
    }
}

if (!function_exists('validate_email')) {
    /**
     * Validate email address format
     *
     * @param string $email Email to validate
     * @return bool True if valid
     */
    function validate_email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('validate_phone')) {
    /**
     * Validate Indonesian phone number format
     *
     * @param string $phone Phone number to validate
     * @return bool True if valid
     */
    function validate_phone(string $phone): bool
    {
        // Remove spaces and dashes
        $phone = preg_replace('/[\s\-]/', '', $phone);
        
        // Indonesian phone format: +62xxx or 08xxx (10-15 digits)
        return preg_match('/^(\+62|62|0)[0-9]{9,13}$/', $phone) === 1;
    }
}

if (!function_exists('generate_secure_token')) {
    /**
     * Generate a cryptographically secure random token
     *
     * @param int $length Token length in bytes (will be hex encoded = 2x length)
     * @return string Hex-encoded token
     */
    function generate_secure_token(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }
}

if (!function_exists('hash_password')) {
    /**
     * Hash password using secure algorithm
     *
     * @param string $password Plain text password
     * @return string Hashed password
     */
    function hash_password(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost'   => 4,
            'threads'     => 3,
        ]);
    }
}

if (!function_exists('verify_password')) {
    /**
     * Verify password against hash
     *
     * @param string $password Plain text password
     * @param string $hash Password hash
     * @return bool True if password matches
     */
    function verify_password(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}

if (!function_exists('mask_sensitive_data')) {
    /**
     * Mask sensitive data for logging
     *
     * @param string $data Data to mask
     * @param int $visibleChars Number of characters to show at start and end
     * @return string Masked data
     */
    function mask_sensitive_data(string $data, int $visibleChars = 4): string
    {
        $length = strlen($data);
        
        if ($length <= $visibleChars * 2) {
            return str_repeat('*', $length);
        }
        
        $start = substr($data, 0, $visibleChars);
        $end = substr($data, -$visibleChars);
        $middle = str_repeat('*', $length - ($visibleChars * 2));
        
        return $start . $middle . $end;
    }
}

if (!function_exists('validate_amount')) {
    /**
     * Validate monetary amount
     *
     * @param mixed $amount Amount to validate
     * @param int $min Minimum amount
     * @param int $max Maximum amount
     * @return bool True if valid
     */
    function validate_amount(mixed $amount, int $min = 1, int $max = PHP_INT_MAX): bool
    {
        if (!is_numeric($amount)) {
            return false;
        }
        
        $amount = (int) $amount;
        
        return $amount >= $min && $amount <= $max;
    }
}

if (!function_exists('is_valid_uuid')) {
    /**
     * Check if string is a valid UUID v4
     *
     * @param string $uuid String to check
     * @return bool True if valid UUID
     */
    function is_valid_uuid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid) === 1;
    }
}

if (!function_exists('generate_order_id')) {
    /**
     * Generate unique order ID with prefix
     *
     * @param string $prefix Order ID prefix
     * @param int $userId User ID for uniqueness
     * @return string Unique order ID
     */
    function generate_order_id(string $prefix = 'TXN', int $userId = 0): string
    {
        $timestamp = date('YmdHis');
        $random = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        
        return "{$prefix}-{$userId}-{$timestamp}-{$random}";
    }
}

if (!function_exists('log_security_event')) {
    /**
     * Log security-related events
     *
     * @param string $event Event type
     * @param array $data Event data
     * @param string $level Log level
     * @return void
     */
    function log_security_event(string $event, array $data = [], string $level = 'warning'): void
    {
        $request = service('request');
        
        $logData = array_merge([
            'event'      => $event,
            'ip'         => $request->getIPAddress(),
            'user_agent' => $request->getUserAgent()->getAgentString(),
            'timestamp'  => date('Y-m-d H:i:s'),
        ], $data);
        
        log_message($level, 'SECURITY: ' . json_encode($logData));
    }
}

if (!function_exists('prevent_timing_attack')) {
    /**
     * Compare two strings in constant time to prevent timing attacks
     *
     * @param string $known Known string
     * @param string $user User-provided string
     * @return bool True if strings match
     */
    function prevent_timing_attack(string $known, string $user): bool
    {
        return hash_equals($known, $user);
    }
}
