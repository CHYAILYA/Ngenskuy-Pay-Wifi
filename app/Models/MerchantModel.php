<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Merchant Model
 * Handles merchant data operations
 */
class MerchantModel extends Model
{
    protected $table            = 'merchants';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'user_id', 'merchant_id', 'business_name', 'business_type',
        'address', 'phone', 'logo', 'status', 'commission_rate',
        'balance', 'created_at', 'updated_at'
    ];
    protected $useTimestamps    = false;
    protected $skipValidation   = true;

    // Status constants
    public const STATUS_PENDING  = 'pending';
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_BANNED   = 'banned';

    // Business types
    public const TYPE_RETAIL     = 'retail';
    public const TYPE_FOOD       = 'food';
    public const TYPE_SERVICE    = 'service';
    public const TYPE_ONLINE     = 'online';
    public const TYPE_OTHER      = 'other';

    /**
     * Generate unique merchant ID
     * Format: MCH-XXXXXX (6 random alphanumeric)
     */
    public function generateMerchantId(): string
    {
        do {
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $randomPart = '';
            for ($i = 0; $i < 6; $i++) {
                $randomPart .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $merchantId = 'MCH-' . $randomPart;
        } while ($this->where('merchant_id', $merchantId)->first());
        
        return $merchantId;
    }

    /**
     * Get merchant by user ID
     */
    public function getByUserId(int $userId): ?array
    {
        return $this->where('user_id', $userId)->first();
    }

    /**
     * Get merchant by merchant ID
     */
    public function getByMerchantId(string $merchantId): ?array
    {
        return $this->where('merchant_id', $merchantId)->first();
    }

    /**
     * Update merchant balance
     */
    public function updateBalance(int $merchantId, float $amount): bool
    {
        $merchant = $this->find($merchantId);
        if (!$merchant) {
            return false;
        }

        $newBalance = ($merchant['balance'] ?? 0) + $amount;
        
        return $this->update($merchantId, [
            'balance' => max(0, $newBalance),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
