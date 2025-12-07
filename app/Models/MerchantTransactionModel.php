<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Merchant Transaction Model
 * Handles payment transactions for merchants
 */
class MerchantTransactionModel extends Model
{
    protected $table            = 'merchant_transactions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'merchant_id', 'customer_id', 'transaction_id', 'amount', 'fee',
        'net_amount', 'description', 'status', 'payment_method',
        'reference', 'created_at'
    ];
    protected $useTimestamps    = false;
    protected $skipValidation   = true;

    // Status constants
    public const STATUS_PENDING  = 'pending';
    public const STATUS_SUCCESS  = 'success';
    public const STATUS_FAILED   = 'failed';
    public const STATUS_REFUNDED = 'refunded';

    /**
     * Generate unique transaction ID
     * Format: TRX-YYYYMMDD-XXXXXX
     */
    public function generateTransactionId(): string
    {
        $date = date('Ymd');
        do {
            $random = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $transactionId = 'TRX-' . $date . '-' . $random;
        } while ($this->where('transaction_id', $transactionId)->first());
        
        return $transactionId;
    }

    /**
     * Get transactions by merchant ID
     */
    public function getByMerchantId(int $merchantId, int $limit = 50): array
    {
        return $this->where('merchant_id', $merchantId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Create a new transaction
     */
    public function createTransaction(array $data): int|false
    {
        return $this->insert($data);
    }

    /**
     * Get transactions with user info
     */
    public function getTransactionsWithUser(int $merchantId, int $limit = 50): array
    {
        return $this->select('merchant_transactions.*, users.name as customer_name, users.email as customer_email')
            ->join('users', 'users.id = merchant_transactions.customer_id', 'left')
            ->where('merchant_transactions.merchant_id', $merchantId)
            ->orderBy('merchant_transactions.created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get merchant statistics
     */
    public function getMerchantStats(int $merchantId): array
    {
        $db = \Config\Database::connect();
        
        // Total revenue
        $total = $db->query("
            SELECT COALESCE(SUM(net_amount), 0) as total 
            FROM merchant_transactions 
            WHERE merchant_id = ? AND status = 'success'
        ", [$merchantId])->getRow();
        
        // Today's revenue
        $today = $db->query("
            SELECT COALESCE(SUM(net_amount), 0) as total 
            FROM merchant_transactions 
            WHERE merchant_id = ? AND status = 'success' AND DATE(created_at) = CURDATE()
        ", [$merchantId])->getRow();
        
        // This month's revenue
        $month = $db->query("
            SELECT COALESCE(SUM(net_amount), 0) as total 
            FROM merchant_transactions 
            WHERE merchant_id = ? AND status = 'success' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())
        ", [$merchantId])->getRow();
        
        // Transaction count
        $count = $db->query("
            SELECT COUNT(*) as total 
            FROM merchant_transactions 
            WHERE merchant_id = ? AND status = 'success'
        ", [$merchantId])->getRow();
        
        return [
            'total_revenue'   => (float) $total->total,
            'today_revenue'   => (float) $today->total,
            'month_revenue'   => (float) $month->total,
            'total_transactions' => (int) $count->total,
        ];
    }

    /**
     * Get daily revenue for chart
     */
    public function getDailyRevenue(int $merchantId, int $days = 7): array
    {
        $db = \Config\Database::connect();
        
        return $db->query("
            SELECT DATE(created_at) as date, SUM(net_amount) as total
            FROM merchant_transactions
            WHERE merchant_id = ? AND status = 'success' AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", [$merchantId, $days])->getResultArray();
    }
}
