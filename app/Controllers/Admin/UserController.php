<?php

namespace App\Controllers\Admin;

use App\Models\UserModel;

/**
 * Admin User Controller
 * 
 * Handles user management for admin panel
 * 
 * @package App\Controllers\Admin
 */
class UserController extends BaseAdminController
{
    protected UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
    }

    /**
     * List all users with search and filter
     * 
     * @return string|RedirectResponse
     */
    public function index()
    {
        if ($redirect = $this->checkAdmin()) {
            return $redirect;
        }

        $data = $this->getLayoutData('Manage Users', 'users');
        
        $search = $this->request->getGet('search');
        $role   = $this->request->getGet('role');
        
        $builder = $this->userModel;
        
        if ($search) {
            $builder = $builder->groupStart()
                ->like('name', $search)
                ->orLike('email', $search)
                ->groupEnd();
        }
        
        if ($role && in_array($role, ['user', 'merchant', 'admin'])) {
            $builder = $builder->where('role', $role);
        }
        
        $data['users']       = $builder->orderBy('created_at', 'DESC')->findAll();
        $data['search']      = $search;
        $data['role_filter'] = $role;

        return view('admin/users', $data);
    }

    /**
     * Create a new user
     * 
     * @return string|RedirectResponse
     */
    public function create()
    {
        if ($redirect = $this->checkAdmin()) {
            return $redirect;
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            $userData = [
                'name'       => $this->request->getPost('name'),
                'email'      => $this->request->getPost('email'),
                'password'   => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
                'role'       => $this->request->getPost('role') ?? 'user',
                'created_at' => date('Y-m-d H:i:s'),
            ];

            log_message('info', 'Attempting to create user with data: ' . json_encode([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'role' => $userData['role']
            ]));

            $insertId = $this->userModel->insert($userData);
            
            if ($insertId) {
                log_message('info', 'User created successfully with ID: ' . $insertId);
                $this->session->setFlashdata('success', 'User berhasil ditambahkan');
            } else {
                $errors = $this->userModel->errors();
                log_message('error', 'Failed to create user: ' . json_encode($errors));
                $this->session->setFlashdata('error', 'Gagal menambahkan user: ' . json_encode($errors));
            }
            return redirect()->to(site_url('admin/users'));
        }

        $data = $this->getLayoutData('Tambah User', 'users');
        return view('admin/user_form', $data);
    }

    /**
     * Edit an existing user
     * 
     * @param int $id User ID
     * @return string|RedirectResponse
     */
    public function edit(int $id)
    {
        if ($redirect = $this->checkAdmin()) {
            return $redirect;
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            $this->session->setFlashdata('error', 'User tidak ditemukan');
            return redirect()->to(site_url('admin/users'));
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            $updateData = [
                'name'       => $this->request->getPost('name'),
                'email'      => $this->request->getPost('email'),
                'role'       => $this->request->getPost('role'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            $password = $this->request->getPost('password');
            if (!empty($password)) {
                $updateData['password'] = password_hash($password, PASSWORD_DEFAULT);
            }

            // Log the update attempt
            log_message('info', 'Attempting to update user ID: ' . $id . ' with data: ' . json_encode($updateData));
            
            $updateResult = $this->userModel->update($id, $updateData);
            
            // Log the result
            log_message('info', 'Update result: ' . ($updateResult ? 'success' : 'failed'));
            
            if ($updateResult !== false) {
                // Verify the update by fetching fresh data
                $updatedUser = $this->userModel->find($id);
                log_message('info', 'Updated user data: ' . json_encode($updatedUser));
                
                // Update session if editing current user or if user being edited is logged in elsewhere
                $currentUser = $this->session->get('user');
                if ($currentUser && $currentUser['id'] == $id) {
                    $currentUser['name'] = $updateData['name'];
                    $currentUser['email'] = $updateData['email'];
                    $currentUser['role'] = $updateData['role'];
                    $this->session->set('user', $currentUser);
                    log_message('info', 'Session updated for current user');
                }
                
                $this->session->setFlashdata('success', 'User berhasil diupdate. Role: ' . $updateData['role']);
            } else {
                $errors = $this->userModel->errors();
                log_message('error', 'Failed to update user: ' . json_encode($errors));
                $this->session->setFlashdata('error', 'Gagal update user: ' . json_encode($errors));
            }
            return redirect()->to(site_url('admin/users'));
        }

        $data              = $this->getLayoutData('Edit User', 'users');
        $data['edit_user'] = $user;
        return view('admin/user_form', $data);
    }

    /**
     * Delete a user
     * 
     * @param int $id User ID
     * @return RedirectResponse
     */
    public function delete(int $id)
    {
        if ($redirect = $this->checkAdmin()) {
            return $redirect;
        }

        $currentUser = $this->session->get('user');
        if ($currentUser['id'] == $id) {
            $this->session->setFlashdata('error', 'Tidak bisa menghapus akun sendiri');
            return redirect()->to(site_url('admin/users'));
        }

        if ($this->userModel->delete($id)) {
            $this->session->setFlashdata('success', 'User berhasil dihapus');
        } else {
            $this->session->setFlashdata('error', 'Gagal menghapus user');
        }
        return redirect()->to(site_url('admin/users'));
    }
}
