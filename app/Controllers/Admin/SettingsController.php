<?php

namespace App\Controllers\Admin;

/**
 * Admin Settings Controller
 * 
 * Handles system settings and activity logs for admin panel
 * 
 * @package App\Controllers\Admin
 */
class SettingsController extends BaseAdminController
{
    /**
     * Display settings page
     * 
     * @return string|RedirectResponse
     */
    public function index()
    {
        if ($redirect = $this->checkAdmin()) {
            return $redirect;
        }

        $data = $this->getLayoutData('Settings', 'settings');
        return view('admin/settings', $data);
    }

    /**
     * Display activity logs
     * 
     * @return string|RedirectResponse
     */
    public function logs()
    {
        if ($redirect = $this->checkAdmin()) {
            return $redirect;
        }

        $data = $this->getLayoutData('Activity Logs', 'logs');
        
        // Read from log file
        $logPath = WRITEPATH . 'logs/log-' . date('Y-m-d') . '.log';
        $data['logs'] = file_exists($logPath) ? file_get_contents($logPath) : 'No logs for today';

        return view('admin/logs', $data);
    }
}
