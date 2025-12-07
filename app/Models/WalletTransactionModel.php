<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Wallet Transaction Model
 * Tracks all wallet balance changes for history
 */
class WalletTransactionModel extends Model
{
    protected $table            = 'wallet_transactions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'user_id', 'type', 'amount', 'description',
        'reference_type', 'reference_id', 
        'balance_before', 'balance_after', 'created_at'
    ];
    protected $useTimestamps    = false;
    protected $skipValidation   = true;

    // Transaction types
    public const TYPE_CREDIT = 'credit';
    public const TYPE_DEBIT  = 'debit';

    // Reference types
    public const REF_TOPUP        = 'topup';
    public const REF_BILL_PAYMENT = 'bill_payment';
    public const REF_TRANSFER     = 'transfer';

    /**
     * Get transactions by user
     */
    public function getByUser(int $userId, ?string $type = null): array
    {
        $builder = $this->where('user_id', $userId);
        
        if ($type !== null) {
            $builder->where('type', $type);
        }
        
        return $builder->orderBy('created_at', 'DESC')->findAll();
    }

    /**
     * Add transaction
     */
    public function addTransaction(
        int $userId,
        string $type,
        float $amount,
        string $description,
        ?string $refType = null,
        ?int $refId = null,
        float $balanceBefore = 0,
        float $balanceAfter = 0
    ): bool {
        return $this->insert([
            'user_id'        => $userId,
            'type'           => $type,
            'amount'         => $amount,
            'description'    => $description,
            'reference_type' => $refType,
            'reference_id'   => $refId,
            'balance_before' => $balanceBefore,
            'balance_after'  => $balanceAfter,
            'created_at'     => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get total credits for user
     */
    public function getTotalCredit(int $userId): float
    {
        $result = $this->selectSum('amount')
            ->where('user_id', $userId)
            ->where('type', self::TYPE_CREDIT)
            ->first();
        
        return (float) ($result['amount'] ?? 0);
    }

    /**
     * Get total debits for user
     */
    public function getTotalDebit(int $userId): float
    {
        $result = $this->selectSum('amount')
            ->where('user_id', $userId)
            ->where('type', self::TYPE_DEBIT)
            ->first();
        
        return (float) ($result['amount'] ?? 0);
    }

    /**
     * Get recent transactions
     */
    public function getRecent(int $userId, int $limit = 10): array
    {
        return $this->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
