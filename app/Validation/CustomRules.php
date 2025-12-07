<?php

namespace App\Validation;

/**
 * Custom Validation Rules
 * 
 * Additional validation rules for the UDARA payment system.
 * These rules extend CodeIgniter's built-in validation.
 * 
 * @package App\Validation
 */
class CustomRules
{
    /**
     * Validate Indonesian phone number
     *
     * @param string|null $value Phone number to validate
     * @return bool
     */
    public function valid_phone(?string $value): bool
    {
        if (empty($value)) {
            return false;
        }
        
        // Remove spaces, dashes, and parentheses
        $phone = preg_replace('/[\s\-\(\)]/', '', $value);
        
        // Indonesian phone format: +62xxx, 62xxx, or 08xxx (10-15 digits total)
        return preg_match('/^(\+62|62|0)[0-9]{9,13}$/', $phone) === 1;
    }

    /**
     * Validate monetary amount within range
     *
     * @param string|null $value Amount to validate
     * @param string $params Comma-separated min,max values
     * @param array $data All field data
     * @return bool
     */
    public function valid_amount(?string $value, string $params): bool
    {
        if (!is_numeric($value)) {
            return false;
        }
        
        $amount = (int) $value;
        
        $parts = explode(',', $params);
        $min = (int) ($parts[0] ?? 1);
        $max = (int) ($parts[1] ?? PHP_INT_MAX);
        
        return $amount >= $min && $amount <= $max;
    }

    /**
     * Validate NIM (Student ID) format
     *
     * @param string|null $value NIM to validate
     * @return bool
     */
    public function valid_nim(?string $value): bool
    {
        if (empty($value)) {
            return false;
        }
        
        // NIM format: 8-15 digits
        return preg_match('/^[0-9]{8,15}$/', $value) === 1;
    }

    /**
     * Validate RFID card number format
     *
     * @param string|null $value Card number to validate
     * @return bool
     */
    public function valid_card_number(?string $value): bool
    {
        if (empty($value)) {
            return false;
        }
        
        // RFID card: hexadecimal, 8-20 characters
        return preg_match('/^[0-9A-Fa-f]{8,20}$/', $value) === 1;
    }

    /**
     * Check if value doesn't contain SQL injection patterns
     *
     * @param string|null $value Value to check
     * @return bool
     */
    public function no_sql_injection(?string $value): bool
    {
        if (empty($value)) {
            return true;
        }
        
        $patterns = [
            '/union\s+select/i',
            '/insert\s+into/i',
            '/delete\s+from/i',
            '/drop\s+table/i',
            '/update\s+.*set/i',
            '/;\s*--/i',
            '/\/\*.*\*\//i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Check if value doesn't contain XSS patterns
     *
     * @param string|null $value Value to check
     * @return bool
     */
    public function no_xss(?string $value): bool
    {
        if (empty($value)) {
            return true;
        }
        
        $patterns = [
            '/<script\b[^>]*>/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/data:/i',
            '/vbscript:/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Validate strong password
     * Requires: 8+ chars, uppercase, lowercase, number
     *
     * @param string|null $value Password to validate
     * @return bool
     */
    public function strong_password(?string $value): bool
    {
        if (empty($value) || strlen($value) < 8) {
            return false;
        }
        
        // At least one uppercase
        if (!preg_match('/[A-Z]/', $value)) {
            return false;
        }
        
        // At least one lowercase
        if (!preg_match('/[a-z]/', $value)) {
            return false;
        }
        
        // At least one number
        if (!preg_match('/[0-9]/', $value)) {
            return false;
        }
        
        return true;
    }

    /**
     * Validate order ID format
     *
     * @param string|null $value Order ID to validate
     * @return bool
     */
    public function valid_order_id(?string $value): bool
    {
        if (empty($value)) {
            return false;
        }
        
        // Format: PREFIX-USERID-TIMESTAMP-RANDOM
        // Example: TOPUP-1-20241205123456-A1B2C3D4
        return preg_match('/^[A-Z]+-[0-9]+-[0-9]{14}-[A-Z0-9]{8}$/', $value) === 1;
    }
}
