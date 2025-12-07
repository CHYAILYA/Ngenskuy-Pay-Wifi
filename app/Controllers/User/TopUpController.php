<?php

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\WalletModel;
use App\Models\TopUpModel;
use Config\Midtrans;

/**
 * TopUp Controller
 * 
 * Handles wallet top-up pages and related views
 * 
 * @package App\Controllers\User
 */
class TopUpController extends BaseController
{
    protected $session;
    protected WalletModel $walletModel;
    protected TopUpModel $topUpModel;

    public function __construct()
    {
        $this->session     = session();
        $this->walletModel = new WalletModel();
        $this->topUpModel  = new TopUpModel();
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
     * Display top-up page
     * 
     * Shows top-up form and recent top-up history
     * 
     * @return string|RedirectResponse
     */
    public function index()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $user = $this->session->get('user');
        $data = $this->getLayoutData('Top Up Saldo', 'topup');
        
        // Get Midtrans config for frontend
        $midtransConfig      = new Midtrans();
        $data['clientKey']   = $midtransConfig->getClientKey();
        $data['snapUrl']     = $midtransConfig->getSnapUrl();
        
        // Get recent top-up history
        $data['recentTopups'] = $this->topUpModel
            ->where('user_id', $user['id'])
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->findAll();

        return view('user/topup', $data);
    }
}
