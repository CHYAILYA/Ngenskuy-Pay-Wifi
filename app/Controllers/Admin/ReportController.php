<?php

namespace App\Controllers\Admin;

use App\Models\TransactionModel;

/**
 * Admin Report Controller
 * 
 * Handles reports and analytics for admin panel
 * 
 * @package App\Controllers\Admin
 */
class ReportController extends BaseAdminController
{
    protected TransactionModel $transactionModel;

    public function __construct()
    {
        parent::__construct();
        $this->transactionModel = new TransactionModel();
    }

    /**
     * Display reports dashboard
     * 
     * @return string|RedirectResponse
     */
    public function index()
    {
        if ($redirect = $this->checkAdmin()) {
            return $redirect;
        }

        $data = $this->getLayoutData('Reports', 'reports');
        
        // Monthly revenue
        $data['monthly_revenue'] = $this->transactionModel
            ->select("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as total")
            ->where('status', 'success')
            ->groupBy('month')
            ->orderBy('month', 'DESC')
            ->limit(12)
            ->findAll();
        
        // Transaction by type
        $data['by_type'] = $this->transactionModel
            ->select("type, COUNT(*) as count, SUM(amount) as total")
            ->where('status', 'success')
            ->groupBy('type')
            ->findAll();

        return view('admin/reports', $data);
    }
}
