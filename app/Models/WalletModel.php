<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Wallet Model
 * 
 * Manages user wallet balance
 */
class WalletModel extends Model
{
    protected $table            = 'wallets';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['user_id', 'balance', 'created_at', 'updated_at'];
    protected $useTimestamps    = false;

    /**
     * Get wallet by user ID, create if not exists
     */
    public function getOrCreate(int $userId): array
    {
        $wallet = $this->where('user_id', $userId)->first();
        
        if (!$wallet) {
            // Create wallet with 0 balance
            $this->insert([
                'user_id'    => $userId,
                'balance'    => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            
            $wallet = $this->where('user_id', $userId)->first();
        }
        
        return $wallet;
    }

    /**
     * Get user balance
     */
    public function getBalance(int $userId): float
    {
        $wallet = $this->getOrCreate($userId);
        return (float) $wallet['balance'];
    }

    /**
     * Add balance to user wallet
     */
    public function addBalance(int $userId, float $amount, string $description = ''): bool
    {
        $wallet = $this->getOrCreate($userId);
        
        return $this->update($wallet['id'], [
            'balance'    => $wallet['balance'] + $amount,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Deduct balance from user wallet
     */
    public function deductBalance(int $userId, float $amount, string $description = ''): bool
    {
        $wallet = $this->getOrCreate($userId);
        
        if ($wallet['balance'] < $amount) {
            return false;
        }
        
        return $this->update($wallet['id'], [
            'balance'    => $wallet['balance'] - $amount,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
