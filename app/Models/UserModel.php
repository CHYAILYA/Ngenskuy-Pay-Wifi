<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * User Model
 * Handles user data operations
 */
class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['name', 'email', 'password', 'role', 'card_number', 'created_at', 'updated_at'];
    protected $useTimestamps    = false;
    protected $skipValidation   = true;

    // Role constants
    public const ROLE_ADMIN    = 'admin';
    public const ROLE_USER     = 'user';
    public const ROLE_MERCHANT = 'merchant';

    /**
     * Get user's dashboard route based on role
     */
    public function getDashboardRoute(string $role): string
    {
        return match ($role) {
            self::ROLE_ADMIN    => 'admin/dashboard',
            self::ROLE_MERCHANT => 'merchant/dashboard',
            default             => 'user/dashboard',
        };
    }

    /**
     * Generate unique card number (16 digits)
     * Format: 4XXX XXXX XXXX XXXX (Visa-like)
     */
    public function generateCardNumber(): string
    {
        do {
            // Generate 16-digit card number starting with 4 (Visa-like)
            $cardNumber = '4' . str_pad(mt_rand(0, 999999999999999), 15, '0', STR_PAD_LEFT);
        } while ($this->where('card_number', $cardNumber)->first());
        
        return $cardNumber;
    }

    /**
     * Find user by card number
     */
    public function findByCardNumber(string $cardNumber): ?array
    {
        return $this->where('card_number', $cardNumber)->first();
    }
}
