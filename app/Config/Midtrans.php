<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Midtrans Payment Gateway Configuration
 * 
 * All sensitive credentials are loaded from .env file
 * to comply with security best practices
 */
class Midtrans extends BaseConfig
{
    /**
     * Merchant ID from Midtrans Dashboard
     */
    public string $merchantId;

    /**
     * Client Key for frontend Snap integration
     */
    public string $clientKey;

    /**
     * Server Key for backend API calls (KEEP SECRET!)
     */
    public string $serverKey;

    /**
     * Production mode flag
     */
    public bool $isProduction;

    /**
     * Snap JS URL (sandbox or production)
     */
    public string $snapUrl;

    /**
     * Snap API URL for creating transactions
     */
    public string $snapApiUrl;

    public function __construct()
    {
        parent::__construct();

        // Load from environment variables
        $this->merchantId   = env('midtrans.merchantId', '');
        $this->clientKey    = env('midtrans.clientKey', '');
        $this->serverKey    = env('midtrans.serverKey', '');
        $this->isProduction = env('midtrans.isProduction', false) === 'true';
        
        // Set URLs based on environment
        if ($this->isProduction) {
            $this->snapUrl    = 'https://app.midtrans.com/snap/snap.js';
            $this->snapApiUrl = 'https://app.midtrans.com/snap/v1/transactions';
        } else {
            $this->snapUrl    = 'https://app.sandbox.midtrans.com/snap/snap.js';
            $this->snapApiUrl = 'https://app.sandbox.midtrans.com/snap/v1/transactions';
        }
    }

    /**
     * Get authorization header for API requests
     */
    public function getAuthHeader(): string
    {
        return 'Basic ' . base64_encode($this->serverKey . ':');
    }

    /**
     * Get Client Key for frontend
     */
    public function getClientKey(): string
    {
        return $this->clientKey;
    }

    /**
     * Get Snap JS URL
     */
    public function getSnapUrl(): string
    {
        return $this->snapUrl;
    }

    /**
     * Get Merchant ID
     */
    public function getMerchantId(): string
    {
        return $this->merchantId;
    }

    /**
     * Check if production mode
     */
    public function isProduction(): bool
    {
        return $this->isProduction;
    }
}
