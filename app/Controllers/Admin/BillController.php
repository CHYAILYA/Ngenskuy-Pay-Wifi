<?php

namespace App\Controllers\Admin;

use App\Models\BillModel;
use App\Models\UserModel;

/**
 * Admin Bill Controller
 * 
 * Handles bill management for admin panel
 * 
 * @package App\Controllers\Admin
 */
class BillController extends BaseAdminController
{
    protected BillModel $billModel;
    protected UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->billModel = new BillModel();
        $this->userModel = new UserModel();
    }

    /**
     * List all bills
     * 
     * @return string|RedirectResponse
     */
    public function index()
    {
        if ($redirect = $this->checkAdmin()) {
            return $redirect;
        }

        $data = $this->getLayoutData('All Bills', 'bills');
        
        $data['bills'] = $this->billModel
            ->select('bills.*, users.name as user_name')
            ->join('users', 'users.id = bills.user_id', 'left')
            ->orderBy('bills.due_date', 'ASC')
            ->findAll();

        return view('admin/bills', $data);
    }

    /**
     * Create a new bill
     * 
     * @return string|RedirectResponse
     */
    public function create()
    {
        if ($redirect = $this->checkAdmin()) {
            return $redirect;
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            $billData = [
                'user_id'     => $this->request->getPost('user_id'),
                'type'        => $this->request->getPost('type'),
                'description' => $this->request->getPost('description'),
                'amount'      => $this->request->getPost('amount'),
                'due_date'    => $this->request->getPost('due_date'),
                'status'      => 'pending',
                'created_at'  => date('Y-m-d H:i:s'),
            ];

            if ($this->billModel->insert($billData)) {
                $this->session->setFlashdata('success', 'Bill berhasil ditambahkan');
            } else {
                $this->session->setFlashdata('error', 'Gagal menambahkan bill');
            }
            return redirect()->to(site_url('admin/bills'));
        }

        $data          = $this->getLayoutData('Tambah Bill', 'bills');
        $data['users'] = $this->userModel->where('role', 'user')->findAll();
        return view('admin/bill_form', $data);
    }
}
