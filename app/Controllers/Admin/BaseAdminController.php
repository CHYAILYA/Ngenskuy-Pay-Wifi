<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

/**
 * Base Admin Controller
 * 
 * Common functionality for all admin controllers
 * 
 * @package App\Controllers\Admin
 */
class BaseAdminController extends BaseController
{
    protected $session;

    public function __construct()
    {
        $this->session = session();
    }

    /**
     * Check if user is authenticated as admin
     * 
     * @return RedirectResponse|null
     */
    protected function checkAdmin()
    {
        $user = $this->session->get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            return redirect()->to(site_url('login'));
        }
        return null;
    }

    /**
     * Get common layout data for admin views
     * 
     * @param string $title Page title
     * @param string $activePage Active menu item
     * @return array
     */
    protected function getLayoutData(string $title, string $activePage = ''): array
    {
        return [
            'user'       => $this->session->get('user'),
            'title'      => $title,
            'activePage' => $activePage,
        ];
    }
}
