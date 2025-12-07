<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Bill Model
 */
class BillModel extends Model
{
    protected $table            = 'bills';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'user_id', 'bill_type', 'bill_number', 'description', 'amount', 
        'due_date', 'status', 'paid_at', 'created_at'
    ];
    protected $useTimestamps    = false;
    protected $skipValidation   = true;

    // Bill types
    public const TYPE_ELECTRICITY = 'electricity';
    public const TYPE_WATER       = 'water';
    public const TYPE_INTERNET    = 'internet';
    public const TYPE_PHONE       = 'phone';

    // Status
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID    = 'paid';
    public const STATUS_OVERDUE = 'overdue';

    /**
     * Get bills by user
     */
    public function getByUser(int $userId): array
    {
        return $this->where('user_id', $userId)
            ->orderBy('due_date', 'ASC')
            ->findAll();
    }

    /**
     * Get unpaid bills
     */
    public function getUnpaid(int $userId): array
    {
        return $this->where('user_id', $userId)
            ->where('status', 'pending')
            ->orderBy('due_date', 'ASC')
            ->findAll();
    }

    /**
     * Mark bill as paid
     */
    public function markPaid(int $billId): bool
    {
        return $this->update($billId, [
            'status' => 'paid',
            'paid_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
