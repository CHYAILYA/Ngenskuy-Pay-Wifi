<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * TopUp Model
 * 
 * Tracks all top-up transactions via Midtrans
 */
class TopUpModel extends Model
{
    protected $table            = 'topups';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'user_id', 'order_id', 'amount', 'status',
        'payment_type', 'snap_token', 'midtrans_response',
        'paid_at', 'created_at', 'updated_at'
    ];
    protected $useTimestamps    = false;

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED  = 'failed';
    public const STATUS_EXPIRED = 'expired';

    /**
     * Generate unique order ID
     * Format: TOPUP-{userId}-{timestamp}-{random}-{microtime}
     */
    public function generateOrderId(int $userId): string
    {
        // More unique: add microtime to prevent conflicts
        $micro = substr(str_replace('.', '', microtime(true)), -6);
        return 'TOPUP-' . $userId . '-' . time() . '-' . random_int(1000, 9999) . '-' . $micro;
    }

    /**
     * Create new top-up record
     */
    public function createTopUp(int $userId, float $amount, string $orderId, string $snapToken): int
    {
        $this->insert([
            'user_id'    => $userId,
            'order_id'   => $orderId,
            'amount'     => $amount,
            'status'     => self::STATUS_PENDING,
            'snap_token' => $snapToken,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->getInsertID();
    }

    /**
     * Mark top-up as success
     */
    public function markSuccess(string $orderId, ?string $paymentType = null): bool
    {
        return $this->where('order_id', $orderId)->set([
            'status'       => self::STATUS_SUCCESS,
            'payment_type' => $paymentType,
            'paid_at'      => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ])->update();
    }

    /**
     * Mark top-up as failed
     */
    public function markFailed(string $orderId): bool
    {
        return $this->where('order_id', $orderId)->set([
            'status'     => self::STATUS_FAILED,
            'updated_at' => date('Y-m-d H:i:s'),
        ])->update();
    }

    /**
     * Get top-up by order ID
     */
    public function getByOrderId(string $orderId): ?array
    {
        return $this->where('order_id', $orderId)->first();
    }

    /**
     * Get user's top-up history
     */
    public function getHistory(int $userId, int $limit = 20): array
    {
        return $this->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
