<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\Controller;

/**
 * Auth Controller
 * Handles user authentication: login, register, logout, password reset
 */
class Auth extends Controller
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Helper: Render login view with consistent data structure
     */
    private function renderLoginView(array $options = []): string
    {
        $defaults = [
            'error'       => null,
            'info'        => null,
            'ui_forgot'   => false,
            'ui_magic'    => false,
            'ui_register' => false,
        ];
        
        return view('login', array_merge($defaults, $options));
    }

    /**
     * Helper: Check if request is POST
     */
    private function isPost(): bool
    {
        return strtolower($this->request->getMethod()) === 'post';
    }

    /**
     * Helper: Validate email format
     */
    private function isValidEmail(string $email): bool
    {
        return !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Login page and authentication
     */
    public function login()
    {
        $session = session();

        if ($this->isPost()) {
            $email    = trim($this->request->getPost('email') ?? '');
            $password = $this->request->getPost('password') ?? '';

            // Validate input
            if (!$this->isValidEmail($email)) {
                return $this->renderLoginView(['error' => 'Email tidak valid']);
            }

            if (empty($password)) {
                return $this->renderLoginView(['error' => 'Password wajib diisi']);
            }

            // Authenticate user
            $user = $this->userModel->where('email', $email)->first();

            if ($user && password_verify($password, $user['password'])) {
                $role = $user['role'] ?? UserModel::ROLE_USER;
                
                $session->set('user', [
                    'id'          => $user['id'],
                    'email'       => $user['email'],
                    'name'        => $user['name'],
                    'role'        => $role,
                    'card_number' => $user['card_number'] ?? null,
                ]);
                
                // Redirect based on role
                $dashboardRoute = $this->userModel->getDashboardRoute($role);
                return redirect()->to(site_url($dashboardRoute));
            }

            return $this->renderLoginView(['error' => 'Email atau password salah']);
        }

        // GET request - show login form
        return $this->renderLoginView([
            'error' => $session->getFlashdata('error'),
            'info'  => $session->getFlashdata('info'),
        ]);
    }

    /**
     * User registration
     */
    public function register()
    {
        if ($this->isPost()) {
            $email    = trim($this->request->getPost('reg_email') ?? '');
            $name     = trim($this->request->getPost('reg_name') ?? '');
            $password = $this->request->getPost('reg_password') ?? '';

            // Validate input
            if (!$this->isValidEmail($email)) {
                return $this->renderLoginView([
                    'error'       => 'Email tidak valid',
                    'ui_register' => true,
                ]);
            }

            if (empty($name)) {
                return $this->renderLoginView([
                    'error'       => 'Nama wajib diisi',
                    'ui_register' => true,
                ]);
            }

            if (strlen($password) < 6) {
                return $this->renderLoginView([
                    'error'       => 'Password minimal 6 karakter',
                    'ui_register' => true,
                ]);
            }

            // Check existing user
            if ($this->userModel->where('email', $email)->first()) {
                return $this->renderLoginView([
                    'error'       => 'Email sudah terdaftar',
                    'ui_register' => true,
                ]);
            }

            // Create user with default role and card number
            $userData = [
                'name'        => $name,
                'email'       => $email,
                'password'    => password_hash($password, PASSWORD_DEFAULT),
                'role'        => UserModel::ROLE_USER,
                'card_number' => $this->userModel->generateCardNumber(),
                'created_at'  => date('Y-m-d H:i:s'),
            ];

            if ($this->userModel->insert($userData)) {
                return $this->renderLoginView([
                    'info'        => 'Registrasi berhasil, silakan login.',
                    'ui_register' => false,
                ]);
            }

            return $this->renderLoginView([
                'error'       => 'Registrasi gagal, silakan coba lagi.',
                'ui_register' => true,
            ]);
        }

        // GET request - show register form
        return $this->renderLoginView(['ui_register' => true]);
    }

    /**
     * Forgot password
     */
    public function forgot()
    {
        if ($this->isPost()) {
            $email = trim($this->request->getPost('forgot_email') ?? '');

            if (!$this->isValidEmail($email)) {
                return $this->renderLoginView([
                    'error'     => 'Email tidak valid',
                    'ui_forgot' => true,
                ]);
            }

            // Security: Always show success message (don't reveal if email exists)
            return $this->renderLoginView([
                'info'      => 'Jika email terdaftar, instruksi reset password telah dikirim.',
                'ui_forgot' => true,
            ]);
        }

        return $this->renderLoginView(['ui_forgot' => true]);
    }

    /**
     * Magic link login
     */
    public function magic()
    {
        if ($this->isPost()) {
            $email = trim($this->request->getPost('magic_email') ?? '');

            if (!$this->isValidEmail($email)) {
                return $this->renderLoginView([
                    'error'    => 'Email tidak valid',
                    'ui_magic' => true,
                ]);
            }

            // Security: Always show success message
            return $this->renderLoginView([
                'info'     => 'Jika email terdaftar, magic link telah dikirim.',
                'ui_magic' => true,
            ]);
        }

        return $this->renderLoginView(['ui_magic' => true]);
    }

    /**
     * Dashboard (protected) - routes to role-based dashboard
     */
    public function dashboard()
    {
        $session = session();
        $user = $session->get('user');

        if (!$user) {
            return redirect()->to(site_url('login'));
        }

        $role = $user['role'] ?? UserModel::ROLE_USER;
        $dashboardRoute = $this->userModel->getDashboardRoute($role);
        
        return redirect()->to(site_url($dashboardRoute));
    }

    /**
     * Logout
     */
    public function logout()
    {
        session()->destroy();
        return redirect()->to(site_url('login'));
    }
}
