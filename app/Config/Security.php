<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Security Configuration
 * 
 * Configures CSRF protection and other security settings
 * for the UDARA payment system.
 * 
 * @package Config
 */
class Security extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * CSRF Protection Method
     * --------------------------------------------------------------------------
     *
     * Protection Method for Cross Site Request Forgery protection.
     * Using 'session' for better security than cookies.
     *
     * @var string 'cookie' or 'session'
     */
    public string $csrfProtection = 'session';

    /**
     * --------------------------------------------------------------------------
     * CSRF Token Randomization
     * --------------------------------------------------------------------------
     *
     * Randomize the CSRF Token for added security.
     * Enabled to prevent token prediction attacks.
     */
    public bool $tokenRandomize = true;

    /**
     * --------------------------------------------------------------------------
     * CSRF Token Name
     * --------------------------------------------------------------------------
     *
     * Token name for Cross Site Request Forgery protection.
     */
    public string $tokenName = 'udara_csrf_token';

    /**
     * --------------------------------------------------------------------------
     * CSRF Header Name
     * --------------------------------------------------------------------------
     *
     * Header name for Cross Site Request Forgery protection.
     */
    public string $headerName = 'X-CSRF-TOKEN';

    /**
     * --------------------------------------------------------------------------
     * CSRF Cookie Name
     * --------------------------------------------------------------------------
     *
     * Cookie name for Cross Site Request Forgery protection.
     */
    public string $cookieName = 'udara_csrf_cookie';

    /**
     * --------------------------------------------------------------------------
     * CSRF Expires
     * --------------------------------------------------------------------------
     *
     * Expiration time for Cross Site Request Forgery protection cookie.
     *
     * Set to 2 hours (7200 seconds) for balance between security and usability.
     */
    public int $expires = 7200;

    /**
     * --------------------------------------------------------------------------
     * CSRF Regenerate
     * --------------------------------------------------------------------------
     *
     * Regenerate CSRF Token on every submission for maximum security.
     */
    public bool $regenerate = true;

    /**
     * --------------------------------------------------------------------------
     * CSRF Redirect
     * --------------------------------------------------------------------------
     *
     * Redirect to previous page with error on failure.
     * Enabled in production to prevent information leakage.
     *
     * @see https://codeigniter4.github.io/userguide/libraries/security.html#redirection-on-failure
     */
    public bool $redirect = true;

    /**
     * --------------------------------------------------------------------------
     * CSRF Allowed URLs (Exclude from CSRF)
     * --------------------------------------------------------------------------
     *
     * URLs that should be excluded from CSRF protection.
     * Required for external webhooks like Midtrans payment notifications.
     */
    public array $exclude = [
        'webhook/*',
        'midtrans/*',
        'payment/notify',
        'api/payment/notification',
        'endpoin/esp/*',
        'api/esp/*',
    ];
}
