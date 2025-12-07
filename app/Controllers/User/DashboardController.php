<?php

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\WalletModel;
use App\Models\TopUpModel;
use App\Models\BillModel;
use App\Models\WalletTransactionModel;
use Config\Midtrans;

/**
 * Dashboard Controller
 * 
 * Handles user dashboard and overview pages
 * 
 * @package App\Controllers\User
 */
class DashboardController extends BaseController
{
    protected $session;
    protected WalletModel $walletModel;
    protected TopUpModel $topUpModel;
    protected BillModel $billModel;
    protected WalletTransactionModel $walletTransactionModel;

    public function __construct()
    {
        $this->session                = session();
        $this->walletModel            = new WalletModel();
        $this->topUpModel             = new TopUpModel();
        $this->billModel              = new BillModel();
        $this->walletTransactionModel = new WalletTransactionModel();
    }

    /**
     * Check if user is authenticated
     * 
     * @return \CodeIgniter\HTTP\RedirectResponse|null
     */
    protected function checkAuth()
    {
        $user = $this->session->get('user');
        if (!$user) {
            return redirect()->to(site_url('login'));
        }
        return null;
    }

    /**
     * Get common layout data for views
     * 
     * @param string $title Page title
     * @param string $activePage Active menu item
     * @return array View data
     */
    protected function getLayoutData(string $title, string $activePage = ''): array
    {
        $user = $this->session->get('user');
        return [
            'user'       => $user,
            'title'      => $title,
            'activePage' => $activePage,
            'balance'    => $this->walletModel->getBalance($user['id']),
        ];
    }

    /**
     * Display user dashboard
     * 
     * Shows overview of wallet balance, bills, and recent transactions
     * 
     * @return string|RedirectResponse
     */
    public function index()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $user = $this->session->get('user');
        $data = $this->getLayoutData('Dashboard', 'dashboard');
        
        // Get unpaid bills
        $bills = $this->billModel->getUnpaid($user['id']);
        $data['bills']       = $bills;
        $data['unpaidBills'] = count($bills);
        $data['totalUnpaid'] = array_sum(array_column($bills, 'amount'));
        
        // Get paid bills count this month
        $data['paidBills'] = $this->billModel
            ->where('user_id', $user['id'])
            ->where('status', 'paid')
            ->where('MONTH(paid_at)', date('m'))
            ->where('YEAR(paid_at)', date('Y'))
            ->countAllResults();
        
        // Get successful top-ups count
        $data['totalTopups'] = $this->topUpModel
            ->where('user_id', $user['id'])
            ->where('status', 'success')
            ->countAllResults();
        
        // Get recent transactions
        $data['transactions'] = $this->walletTransactionModel->getRecent($user['id'], 5);

        return view('user/dashboard', $data);
    }
}
