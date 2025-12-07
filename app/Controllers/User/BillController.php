<?php

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\WalletModel;
use App\Models\BillModel;
use App\Models\WalletTransactionModel;

/**
 * Bill Controller
 * 
 * Handles bill viewing and payment
 * 
 * @package App\Controllers\User
 */
class BillController extends BaseController
{
    protected $session;
    protected WalletModel $walletModel;
    protected BillModel $billModel;
    protected WalletTransactionModel $walletTransactionModel;

    public function __construct()
    {
        $this->session                = session();
        $this->walletModel            = new WalletModel();
        $this->billModel              = new BillModel();
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
     * Display bills list
     * 
     * @return string|RedirectResponse
     */
    public function index()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $user = $this->session->get('user');
        $data = $this->getLayoutData('Tagihan Saya', 'bills');
        
        // Get user's bills
        $data['bills'] = $this->billModel
            ->where('user_id', $user['id'])
            ->orderBy('due_date', 'ASC')
            ->findAll();

        return view('user/bills', $data);
    }

    /**
     * Pay a bill using wallet balance
     * 
     * @param int $billId Bill ID to pay
     * @return RedirectResponse
     */
    public function pay(int $billId)
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $user = $this->session->get('user');
        
        // Get bill
        $bill = $this->billModel->find($billId);
        
        if (!$bill || $bill['user_id'] != $user['id']) {
            $this->session->setFlashdata('error', 'Tagihan tidak ditemukan');
            return redirect()->to('/user/bills');
        }
        
        if ($bill['status'] === 'paid') {
            $this->session->setFlashdata('error', 'Tagihan sudah dibayar');
            return redirect()->to('/user/bills');
        }
        
        // Check balance
        $balance = $this->walletModel->getBalance($user['id']);
        
        if ($balance < $bill['amount']) {
            $this->session->setFlashdata('error', 'Saldo tidak mencukupi. Silakan top up terlebih dahulu.');
            return redirect()->to('/user/topup');
        }
        
        // Process payment
        $currentBalance = $balance;
        $newBalance     = $balance - $bill['amount'];
        
        // Deduct balance
        $this->walletModel->deductBalance($user['id'], $bill['amount'], 'Bayar ' . ($bill['description'] ?? 'Tagihan'));
        
        // Update bill status
        $this->billModel->update($billId, [
            'status'  => 'paid',
            'paid_at' => date('Y-m-d H:i:s'),
        ]);
        
        // Record transaction
        $this->walletTransactionModel->addTransaction(
            $user['id'],
            'debit',
            $bill['amount'],
            'Pembayaran: ' . ($bill['description'] ?? 'Tagihan'),
            'bill',
            $billId,
            $currentBalance,
            $newBalance
        );
        
        $this->session->setFlashdata('success', 'Tagihan berhasil dibayar!');
        return redirect()->to('/user/bills');
    }
}
