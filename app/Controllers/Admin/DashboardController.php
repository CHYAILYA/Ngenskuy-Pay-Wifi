<?php

namespace App\Controllers\Admin;

use App\Models\UserModel;
use App\Models\TransactionModel;
use App\Models\BillModel;
use App\Models\MerchantModel;
use App\Models\MerchantTransactionModel;
use App\Models\TopUpModel;
use App\Models\WalletModel;

/**
 * Admin Dashboard Controller
 * 
 * Handles admin dashboard and overview statistics
 * Provides comprehensive data integration across all system components
 * 
 * @package App\Controllers\Admin
 */
class DashboardController extends BaseAdminController
{
    protected UserModel $userModel;
    protected TransactionModel $transactionModel;
    protected BillModel $billModel;
    protected MerchantModel $merchantModel;
    protected MerchantTransactionModel $merchantTransactionModel;
    protected TopUpModel $topUpModel;
    protected WalletModel $walletModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel               = new UserModel();
        $this->transactionModel        = new TransactionModel();
        $this->billModel               = new BillModel();
        $this->merchantModel           = new MerchantModel();
        $this->merchantTransactionModel = new MerchantTransactionModel();
        $this->topUpModel              = new TopUpModel();
        $this->walletModel             = new WalletModel();
    }

    /**
     * Admin Dashboard - Overview statistics
     * 
     * @return string|RedirectResponse
     */
    public function index()
    {
        if ($redirect = $this->checkAdmin()) {
            return $redirect;
        }

        $data = $this->getLayoutData('Admin Dashboard', 'dashboard');
        
        // === MAIN STATISTICS ===
        $data['stats'] = [
            // User Stats
            'total_users'        => $this->userModel->where('role', 'user')->countAllResults(),
            'total_merchants'    => $this->userModel->where('role', 'merchant')->countAllResults(),
            'total_admins'       => $this->userModel->where('role', 'admin')->countAllResults(),
            'new_users_today'    => $this->userModel->where('DATE(created_at)', date('Y-m-d'))->countAllResults(),
            'new_users_week'     => $this->userModel->where('created_at >=', date('Y-m-d', strtotime('-7 days')))->countAllResults(),
            
            // Transaction Stats  
            'total_transactions' => $this->transactionModel->countAllResults(),
            'success_transactions' => $this->transactionModel->where('status', 'success')->countAllResults(),
            'pending_transactions' => $this->transactionModel->where('status', 'pending')->countAllResults(),
            'failed_transactions'  => $this->transactionModel->where('status', 'failed')->countAllResults(),
            
            // Revenue Stats
            'total_revenue'      => $this->transactionModel
                ->selectSum('amount')
                ->where('status', 'success')
                ->first()['amount'] ?? 0,
            'today_revenue'      => $this->transactionModel
                ->selectSum('amount')
                ->where('status', 'success')
                ->where('DATE(created_at)', date('Y-m-d'))
                ->first()['amount'] ?? 0,
            'week_revenue'       => $this->transactionModel
                ->selectSum('amount')
                ->where('status', 'success')
                ->where('created_at >=', date('Y-m-d', strtotime('-7 days')))
                ->first()['amount'] ?? 0,
            'month_revenue'      => $this->transactionModel
                ->selectSum('amount')
                ->where('status', 'success')
                ->where('created_at >=', date('Y-m-01'))
                ->first()['amount'] ?? 0,
                
            // Bill Stats
            'total_bills'        => $this->billModel->countAllResults(),
            'paid_bills'         => $this->billModel->where('status', 'paid')->countAllResults(),
            'pending_bills'      => $this->billModel->where('status', 'pending')->countAllResults(),
            'overdue_bills'      => $this->billModel
                ->where('status', 'pending')
                ->where('due_date <', date('Y-m-d'))
                ->countAllResults(),
            'total_bill_amount'  => $this->billModel
                ->selectSum('amount')
                ->first()['amount'] ?? 0,
            'paid_bill_amount'   => $this->billModel
                ->selectSum('amount')
                ->where('status', 'paid')
                ->first()['amount'] ?? 0,
                
            // TopUp Stats
            'total_topups'       => $this->topUpModel->countAllResults(),
            'success_topups'     => $this->topUpModel->where('status', 'success')->countAllResults(),
            'pending_topups'     => $this->topUpModel->where('status', 'pending')->countAllResults(),
            'topup_amount'       => $this->topUpModel
                ->selectSum('amount')
                ->where('status', 'success')
                ->first()['amount'] ?? 0,
            'today_topup'        => $this->topUpModel
                ->selectSum('amount')
                ->where('status', 'success')
                ->where('DATE(created_at)', date('Y-m-d'))
                ->first()['amount'] ?? 0,
                
            // Merchant Stats
            'active_merchants'   => $this->merchantModel->where('status', 'active')->countAllResults(),
            'pending_merchants'  => $this->merchantModel->where('status', 'pending')->countAllResults(),
            'merchant_balance'   => $this->merchantModel
                ->selectSum('balance')
                ->first()['balance'] ?? 0,
            'merchant_transactions' => $this->merchantTransactionModel->countAllResults(),
            'merchant_revenue'   => $this->merchantTransactionModel
                ->selectSum('amount')
                ->where('status', 'success')
                ->first()['amount'] ?? 0,
                
            // Wallet Stats
            'total_wallet_balance' => $this->walletModel
                ->selectSum('balance')
                ->first()['balance'] ?? 0,
        ];
        
        // === RECENT TRANSACTIONS ===
        $data['recent_transactions'] = $this->transactionModel
            ->select('transactions.*, users.name as user_name, users.email as user_email')
            ->join('users', 'users.id = transactions.user_id', 'left')
            ->orderBy('transactions.created_at', 'DESC')
            ->limit(10)
            ->find();
            
        // === RECENT BILLS ===
        $data['recent_bills'] = $this->billModel
            ->select('bills.*, users.name as user_name')
            ->join('users', 'users.id = bills.user_id', 'left')
            ->orderBy('bills.created_at', 'DESC')
            ->limit(5)
            ->find();
            
        // === RECENT TOPUPS ===
        $data['recent_topups'] = $this->topUpModel
            ->select('topups.*, users.name as user_name')
            ->join('users', 'users.id = topups.user_id', 'left')
            ->orderBy('topups.created_at', 'DESC')
            ->limit(5)
            ->find();
            
        // === RECENT USERS ===
        $data['recent_users'] = $this->userModel
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->find();
            
        // === RECENT MERCHANTS ===
        $data['recent_merchants'] = $this->merchantModel
            ->select('merchants.*, users.name as owner_name, users.email as owner_email')
            ->join('users', 'users.id = merchants.user_id', 'left')
            ->orderBy('merchants.created_at', 'DESC')
            ->limit(5)
            ->find();
            
        // === MERCHANT TRANSACTIONS ===
        $data['recent_merchant_transactions'] = $this->merchantTransactionModel
            ->select('merchant_transactions.*, merchants.business_name, users.name as customer_name')
            ->join('merchants', 'merchants.id = merchant_transactions.merchant_id', 'left')
            ->join('users', 'users.id = merchant_transactions.customer_id', 'left')
            ->orderBy('merchant_transactions.created_at', 'DESC')
            ->limit(5)
            ->find();
            
        // === REVENUE BY TYPE (Last 30 days) ===
        $data['revenue_by_type'] = $this->transactionModel
            ->select('type, SUM(amount) as total, COUNT(*) as count')
            ->where('status', 'success')
            ->where('created_at >=', date('Y-m-d', strtotime('-30 days')))
            ->groupBy('type')
            ->orderBy('total', 'DESC')
            ->findAll();
            
        // === DAILY REVENUE (Last 7 days) ===
        $data['daily_revenue'] = $this->transactionModel
            ->select('DATE(created_at) as date, SUM(amount) as total, COUNT(*) as count')
            ->where('status', 'success')
            ->where('created_at >=', date('Y-m-d', strtotime('-7 days')))
            ->groupBy('DATE(created_at)')
            ->orderBy('date', 'ASC')
            ->findAll();
            
        // === TOP USERS BY TRANSACTIONS ===
        $data['top_users'] = $this->transactionModel
            ->select('users.id, users.name, users.email, COUNT(transactions.id) as tx_count, SUM(transactions.amount) as total_amount')
            ->join('users', 'users.id = transactions.user_id', 'left')
            ->where('transactions.status', 'success')
            ->groupBy('transactions.user_id')
            ->orderBy('total_amount', 'DESC')
            ->limit(5)
            ->find();
            
        // === OVERDUE BILLS ===
        $data['overdue_bills'] = $this->billModel
            ->select('bills.*, users.name as user_name, users.email as user_email')
            ->join('users', 'users.id = bills.user_id', 'left')
            ->where('bills.status', 'pending')
            ->where('bills.due_date <', date('Y-m-d'))
            ->orderBy('bills.due_date', 'ASC')
            ->limit(5)
            ->find();

        return view('admin/dashboard', $data);
    }
}
