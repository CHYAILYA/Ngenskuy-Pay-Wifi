<?php

namespace App\Controllers\Merchant;

use App\Models\MerchantTransactionModel;
use App\Models\WalletModel;

/**
 * Merchant Dashboard Controller
 * Main dashboard for merchant users
 */
class DashboardController extends BaseMerchantController
{
    /**
     * Dashboard view
     */
    public function index()
    {
        $transactionModel = new MerchantTransactionModel();
        $walletModel = new WalletModel();
        
        // Get merchant stats
        $stats = $transactionModel->getMerchantStats($this->merchant['id']);
        
        // Get recent transactions
        $recentTransactions = $transactionModel->getTransactionsWithUser($this->merchant['id'], 10);
        
        // Get daily revenue for chart
        $dailyRevenue = $transactionModel->getDailyRevenue($this->merchant['id'], 7);
        
        // Get wallet balance
        $balance = $walletModel->getBalance($this->user['id']);
        
        $data = array_merge($this->getViewData(), [
            'stats'              => $stats,
            'recent_transactions' => $recentTransactions,
            'daily_revenue'      => $dailyRevenue,
            'balance'            => $balance,
        ]);
        
        return view('merchant/dashboard', $data);
    }
}
