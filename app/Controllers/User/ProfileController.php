<?php

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Profile Controller
 * 
 * Handles user profile management
 * 
 * @package App\Controllers\User
 */
class ProfileController extends BaseController
{
    protected $session;
    protected UserModel $userModel;

    public function __construct()
    {
        $this->session   = session();
        $this->userModel = new UserModel();
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
     * Display user profile
     * 
     * @return string|RedirectResponse
     */
    public function index()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $user = $this->session->get('user');
        $data = [
            'user'       => $user,
            'title'      => 'Profil Saya',
            'activePage' => 'profile',
        ];

        return view('user/profile', $data);
    }

    /**
     * Update user profile
     * 
     * @return RedirectResponse
     */
    public function update()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $user = $this->session->get('user');
        
        $validation = \Config\Services::validation();
        
        $rules = [
            'name'  => 'required|min_length[3]',
            'email' => 'required|valid_email',
            'phone' => 'permit_empty|min_length[10]|max_length[15]',
        ];
        
        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $validation->getErrors());
        }
        
        $updateData = [
            'name'  => $this->request->getPost('name'),
            'email' => $this->request->getPost('email'),
            'phone' => $this->request->getPost('phone'),
        ];
        
        // Update in database
        $this->userModel->update($user['id'], $updateData);
        
        // Update session
        $user = array_merge($user, $updateData);
        $this->session->set('user', $user);
        
        $this->session->setFlashdata('success', 'Profil berhasil diperbarui!');
        return redirect()->to('/user/profile');
    }

    /**
     * Update user password
     * 
     * @return RedirectResponse
     */
    public function updatePassword()
    {
        if ($redirect = $this->checkAuth()) {
            return $redirect;
        }

        $user = $this->session->get('user');
        
        $validation = \Config\Services::validation();
        
        $rules = [
            'current_password' => 'required',
            'new_password'     => 'required|min_length[6]',
            'confirm_password' => 'required|matches[new_password]',
        ];
        
        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $validation->getErrors());
        }
        
        // Get user from database
        $userData = $this->userModel->find($user['id']);
        
        // Verify current password
        if (!password_verify($this->request->getPost('current_password'), $userData['password'])) {
            $this->session->setFlashdata('error', 'Password saat ini tidak cocok');
            return redirect()->back();
        }
        
        // Update password
        $this->userModel->update($user['id'], [
            'password' => password_hash($this->request->getPost('new_password'), PASSWORD_DEFAULT),
        ]);
        
        $this->session->setFlashdata('success', 'Password berhasil diperbarui!');
        return redirect()->to('/user/profile');
    }
}
