<?php

namespace App\Controllers\Admin;

use App\Models\TransactionModel;

/**
 * Admin Transaction Controller
 * 
 * Handles transaction management for admin panel
 * 
 * @package App\Controllers\Admin
 */
class TransactionController extends BaseAdminController
{
    protected TransactionModel $transactionModel;

    public function __construct()
    {
        parent::__construct();
        $this->transactionModel = new TransactionModel();
    }

    /**
     * List all transactions with filter
     * 
     * @return string|RedirectResponse
     */
    public function index()
    {
        if ($redirect = $this->checkAdmin()) {
            return $redirect;
        }

        $data = $this->getLayoutData('Transactions', 'transactions');
        
        $status = $this->request->getGet('status');
        $type   = $this->request->getGet('type');
        
        $builder = $this->transactionModel
            ->select('transactions.*, users.name as user_name')
            ->join('users', 'users.id = transactions.user_id', 'left');
        
        if ($status) {
            $builder = $builder->where('transactions.status', $status);
        }
        
        if ($type) {
            $builder = $builder->where('transactions.type', $type);
        }
        
        $data['transactions']  = $builder->orderBy('transactions.created_at', 'DESC')->findAll();
        $data['status_filter'] = $status;
        $data['type_filter']   = $type;

        return view('admin/transactions', $data);
    }
}
