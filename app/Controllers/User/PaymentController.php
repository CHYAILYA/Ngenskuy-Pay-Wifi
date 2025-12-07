<?php

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\WalletModel;
use App\Models\WalletTransactionModel;

/**
 * Payment Controller
 * 
 * Handles payment history for user
 * 
 * @package App\Controllers\User
 */
class PaymentController extends BaseController
{
    protected $session;
    protected WalletModel $walletModel;
    protected WalletTransactionModel $walletTransactionModel;

    public function __construct()
    {
        $this->session                = session();
        $this->walletModel            = new WalletModel();
        $this->walletTransactionModel = new WalletTransactionModel();
    }

    /**
     * Check if user is authenticated
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
     * Get common layout data
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
     * Payment History Page
     * 
     * @return string|RedirectResponse
     */
    public function index()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $user   = $this->session->get('user');
        $filter = $this->request->getGet('filter');
        $data   = $this->getLayoutData('Payment History', 'payments');
        
        // Get transactions based on filter
        if ($filter === 'topup') {
            $data['transactions'] = $this->walletTransactionModel
                ->where('user_id', $user['id'])
                ->where('type', 'credit')
                ->orderBy('created_at', 'DESC')
                ->findAll();
        } elseif ($filter === 'payment') {
            $data['transactions'] = $this->walletTransactionModel
                ->where('user_id', $user['id'])
                ->where('type', 'debit')
                ->orderBy('created_at', 'DESC')
                ->findAll();
        } else {
            $data['transactions'] = $this->walletTransactionModel
                ->where('user_id', $user['id'])
                ->orderBy('created_at', 'DESC')
                ->findAll();
        }
        
        $data['filter']      = $filter ?? 'all';
        $data['totalCredit'] = $this->walletTransactionModel->getTotalCredit($user['id']);
        $data['totalDebit']  = $this->walletTransactionModel->getTotalDebit($user['id']);

        return view('user/payments', $data);
    }
}
