<?php

namespace App\Controllers\Merchant;

use App\Controllers\BaseController;
use App\Models\MerchantModel;
use App\Models\UserModel;

/**
 * Base Merchant Controller
 * Provides common functionality for all merchant controllers
 */
class BaseMerchantController extends BaseController
{
    protected $merchantModel;
    protected $userModel;
    protected $merchant;
    protected $user;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        
        $this->merchantModel = new MerchantModel();
        $this->userModel = new UserModel();
        
        // Get user from session (stored as array with keys: id, email, name, role, card_number)
        $sessionUser = session()->get('user');
        
        // Check if user is logged in
        if (!$sessionUser) {
            header('Location: /login');
            exit;
        }
        
        // Allow any logged-in user to access setup page
        if ($this->isSetupRoute()) {
            // Get full user data from database
            $this->user = $this->userModel->find($sessionUser['id']);
            
            // Check if user already has merchant profile
            $this->merchant = $this->merchantModel->getByUserId($sessionUser['id']);
            
            // If already has merchant, redirect to merchant dashboard
            if ($this->merchant) {
                header('Location: /merchant/dashboard');
                exit;
            }
            return;
        }
        
        // For other merchant pages, check if user is merchant
        if (($sessionUser['role'] ?? '') !== 'merchant') {
            header('Location: /user/dashboard');
            exit;
        }
        
        // Get full user data from database
        $this->user = $this->userModel->find($sessionUser['id']);
        
        // Get merchant data
        $this->merchant = $this->merchantModel->getByUserId($sessionUser['id']);
        
        // If no merchant profile, redirect to setup
        if (!$this->merchant) {
            header('Location: /merchant/setup');
            exit;
        }
    }

    /**
     * Check if current route is setup route
     */
    private function isSetupRoute(): bool
    {
        $uri = service('uri');
        $path = $uri->getPath();
        return strpos($path, 'merchant/setup') !== false || $path === 'merchant/setup';
    }

    /**
     * Get common view data
     */
    protected function getViewData(): array
    {
        return [
            'user'     => $this->user,
            'merchant' => $this->merchant,
        ];
    }
}
