<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Transaction Model
 */
class TransactionModel extends Model
{
    protected $table            = 'transactions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'user_id', 'type', 'description', 'amount', 
        'status', 'reference', 'created_at'
    ];
    protected $useTimestamps    = false;
    protected $skipValidation   = true;

    // Transaction types
    public const TYPE_ELECTRICITY = 'electricity';
    public const TYPE_WATER       = 'water';
    public const TYPE_INTERNET    = 'internet';
    public const TYPE_PHONE       = 'phone';
    public const TYPE_TOPUP       = 'topup';
    public const TYPE_TRANSFER    = 'transfer';

    // Status
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED  = 'failed';

    /**
     * Get transactions by user
     */
    public function getByUser(int $userId, int $limit = 10): array
    {
        return $this->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get transaction statistics
     */
    public function getStats(): array
    {
        return [
            'total' => $this->countAllResults(),
            'success' => $this->where('status', 'success')->countAllResults(),
            'pending' => $this->where('status', 'pending')->countAllResults(),
            'failed' => $this->where('status', 'failed')->countAllResults(),
        ];
    }
}
