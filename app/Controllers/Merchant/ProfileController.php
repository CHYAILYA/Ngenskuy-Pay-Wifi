<?php

namespace App\Controllers\Merchant;

use App\Models\MerchantModel;
use App\Models\WalletModel;
use App\Models\WalletTransactionModel;

/**
 * Merchant Profile Controller
 * Manage merchant profile and settings
 */
class ProfileController extends BaseMerchantController
{
    /**
     * View/Edit profile
     */
    public function index()
    {
        $data = array_merge($this->getViewData(), [
            'business_types' => [
                'retail'  => 'Retail / Toko',
                'food'    => 'Food & Beverage',
                'service' => 'Jasa / Service',
                'online'  => 'Online Shop',
                'other'   => 'Lainnya',
            ],
        ]);
        
        return view('merchant/profile', $data);
    }

    /**
     * Update merchant profile
     */
    public function update()
    {
        $merchantModel = new MerchantModel();
        
        $data = [
            'business_name' => $this->request->getPost('business_name'),
            'business_type' => $this->request->getPost('business_type'),
            'address'       => $this->request->getPost('address'),
            'phone'         => $this->request->getPost('phone'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ];
        
        // Handle logo upload
        $logo = $this->request->getFile('logo');
        if ($logo && $logo->isValid() && !$logo->hasMoved()) {
            $newName = $this->merchant['merchant_id'] . '_' . time() . '.' . $logo->getExtension();
            $logo->move(FCPATH . 'assets/uploads/merchants', $newName);
            $data['logo'] = $newName;
        }
        
        $merchantModel->update($this->merchant['id'], $data);
        
        return redirect()->to('/merchant/profile')->with('success', 'Profile updated successfully');
    }

    /**
     * Show withdrawal page
     */
    public function withdraw()
    {
        // Get withdrawal history
        $db = \Config\Database::connect();
        $withdrawals = $db->table('merchant_withdrawals')
            ->where('merchant_id', $this->merchant['id'])
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();
        
        $data = array_merge($this->getViewData(), [
            'withdrawals' => $withdrawals,
        ]);
        
        return view('merchant/withdraw', $data);
    }

    /**
     * Process withdrawal request
     */
    public function processWithdraw()
    {
        $amount = (float)$this->request->getPost('amount');
        $bankName = $this->request->getPost('bank_name');
        $accountNumber = $this->request->getPost('account_number');
        $accountName = $this->request->getPost('account_name');

        // Validation
        if ($amount < 10000) {
            return redirect()->to('/merchant/withdraw')->with('error', 'Minimum withdrawal amount is Rp 10,000');
        }

        if ($amount > ($this->merchant['balance'] ?? 0)) {
            return redirect()->to('/merchant/withdraw')->with('error', 'Insufficient balance');
        }

        // Create withdrawal request
        $db = \Config\Database::connect();
        $db->table('merchant_withdrawals')->insert([
            'merchant_id'    => $this->merchant['id'],
            'amount'         => $amount,
            'bank_name'      => $bankName,
            'account_number' => $accountNumber,
            'account_name'   => $accountName,
            'status'         => 'pending',
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        // Deduct from merchant balance
        $merchantModel = new MerchantModel();
        $merchantModel->updateBalance($this->merchant['id'], -$amount);

        return redirect()->to('/merchant/withdraw')->with('success', 'Withdrawal request submitted successfully. Amount: Rp ' . number_format($amount, 0, ',', '.'));
    }

    /**
     * Setup merchant profile (first time)
     */
    public function setup()
    {
        // If already has merchant profile, redirect to dashboard
        if ($this->merchant) {
            return redirect()->to('/merchant/dashboard');
        }
        
        if (strtolower($this->request->getMethod()) === 'post') {
            return $this->createMerchant();
        }
        
        $data = [
            'user' => $this->user,
            'business_types' => [
                'retail'  => 'Retail / Toko',
                'food'    => 'Food & Beverage',
                'service' => 'Jasa / Service',
                'online'  => 'Online Shop',
                'other'   => 'Lainnya',
            ],
        ];
        
        return view('merchant/setup', $data);
    }

    /**
     * Create new merchant profile
     */
    private function createMerchant()
    {
        $merchantModel = new MerchantModel();
        $walletModel = new WalletModel();
        $userModel = new \App\Models\UserModel();
        
        // Generate unique merchant ID
        $merchantId = $merchantModel->generateMerchantId();
        
        $data = [
            'user_id'         => $this->user['id'],
            'merchant_id'     => $merchantId,
            'business_name'   => $this->request->getPost('business_name'),
            'business_type'   => $this->request->getPost('business_type'),
            'address'         => $this->request->getPost('address'),
            'phone'           => $this->request->getPost('phone'),
            'status'          => MerchantModel::STATUS_ACTIVE,
            'commission_rate' => 2.5, // Default 2.5% commission
            'created_at'      => date('Y-m-d H:i:s'),
        ];
        
        // Handle logo upload
        $logo = $this->request->getFile('logo');
        if ($logo && $logo->isValid() && !$logo->hasMoved()) {
            $uploadPath = FCPATH . 'assets/uploads/merchants';
            
            // Try to create directory if not exists
            if (!is_dir($uploadPath)) {
                @mkdir($uploadPath, 0755, true);
            }
            
            // Only upload if directory is writable
            if (is_writable($uploadPath) || is_writable(dirname($uploadPath))) {
                try {
                    $newName = $merchantId . '_' . time() . '.' . $logo->getExtension();
                    $logo->move($uploadPath, $newName);
                    $data['logo'] = $newName;
                } catch (\Exception $e) {
                    // Logo upload failed, continue without logo
                    log_message('warning', 'Logo upload failed: ' . $e->getMessage());
                }
            }
        }
        
        $merchantModel->insert($data);

        // Ensure wallet exists
        $walletModel->getOrCreate($this->user['id']);

        // Update user role to merchant in DB
        $updateResult = $userModel->update($this->user['id'], ['role' => 'merchant']);

        // Update session with new role (force update)
        $sessionUser = session()->get('user');
        $sessionUser['role'] = 'merchant';
        session()->set('user', $sessionUser);

        // Fallback: check if DB update failed
        $userAfter = $userModel->find($this->user['id']);
        if (($userAfter['role'] ?? '') !== 'merchant') {
            return redirect()->to('/merchant/setup')->with('error', 'Gagal update role user ke merchant. Silakan coba lagi atau hubungi admin.');
        }

        return redirect()->to('/merchant/dashboard')->with('success', 'Merchant profile created! Your Merchant ID: ' . $merchantId);
    }
}
