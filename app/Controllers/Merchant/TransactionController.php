<?php

namespace App\Controllers\Merchant;

use App\Models\MerchantTransactionModel;

/**
 * Merchant Transactions Controller
 * View and manage transactions
 */
class TransactionController extends BaseMerchantController
{
    /**
     * List all transactions
     */
    public function index()
    {
        $transactionModel = new MerchantTransactionModel();
        
        // Get filter parameters
        $status = $this->request->getGet('status');
        $dateFrom = $this->request->getGet('from');
        $dateTo = $this->request->getGet('to');
        
        // Build query
        $builder = $transactionModel->select('merchant_transactions.*, users.name as customer_name')
            ->join('users', 'users.id = merchant_transactions.customer_id', 'left')
            ->where('merchant_transactions.merchant_id', $this->merchant['id']);
        
        if ($status) {
            $builder->where('merchant_transactions.status', $status);
        }
        if ($dateFrom) {
            $builder->where('DATE(merchant_transactions.created_at) >=', $dateFrom);
        }
        if ($dateTo) {
            $builder->where('DATE(merchant_transactions.created_at) <=', $dateTo);
        }
        
        $transactions = $builder->orderBy('merchant_transactions.created_at', 'DESC')->findAll();
        
        // Get stats
        $stats = $transactionModel->getMerchantStats($this->merchant['id']);
        
        $data = array_merge($this->getViewData(), [
            'transactions' => $transactions,
            'stats'        => $stats,
            'filters'      => [
                'status' => $status,
                'from'   => $dateFrom,
                'to'     => $dateTo,
            ],
        ]);
        
        return view('merchant/transactions', $data);
    }

    /**
     * View transaction detail
     */
    public function detail($id)
    {
        $transactionModel = new MerchantTransactionModel();
        
        $transaction = $transactionModel->select('merchant_transactions.*, users.name as customer_name, users.email as customer_email')
            ->join('users', 'users.id = merchant_transactions.customer_id', 'left')
            ->where('merchant_transactions.id', $id)
            ->where('merchant_transactions.merchant_id', $this->merchant['id'])
            ->first();
        
        if (!$transaction) {
            return redirect()->to('/merchant/transactions')->with('error', 'Transaction not found');
        }
        
        $data = array_merge($this->getViewData(), [
            'transaction' => $transaction,
        ]);
        
        return view('merchant/transaction_detail', $data);
    }
}
