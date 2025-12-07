<?php

namespace Config;

use App\Validation\CustomRules;
use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;

class Validation extends BaseConfig
{
    // --------------------------------------------------------------------
    // Setup
    // --------------------------------------------------------------------

    /**
     * Stores the classes that contain the
     * rules that are available.
     *
     * @var list<string>
     */
    public array $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
        CustomRules::class, // Custom validation rules for UDARA
    ];

    /**
     * Specifies the views that are used to display the
     * errors.
     *
     * @var array<string, string>
     */
    public array $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    // --------------------------------------------------------------------
    // Rules
    // --------------------------------------------------------------------

    /**
     * Validation rules for user registration
     */
    public array $registration = [
        'name' => [
            'rules'  => 'required|min_length[3]|max_length[100]|no_xss',
            'errors' => [
                'required'   => 'Name is required',
                'min_length' => 'Name must be at least 3 characters',
                'max_length' => 'Name cannot exceed 100 characters',
            ],
        ],
        'email' => [
            'rules'  => 'required|valid_email|is_unique[users.email]',
            'errors' => [
                'required'    => 'Email is required',
                'valid_email' => 'Please enter a valid email address',
                'is_unique'   => 'This email is already registered',
            ],
        ],
        'password' => [
            'rules'  => 'required|min_length[8]|strong_password',
            'errors' => [
                'required'        => 'Password is required',
                'min_length'      => 'Password must be at least 8 characters',
                'strong_password' => 'Password must contain uppercase, lowercase, and number',
            ],
        ],
        'phone' => [
            'rules'  => 'permit_empty|valid_phone',
            'errors' => [
                'valid_phone' => 'Please enter a valid phone number',
            ],
        ],
    ];

    /**
     * Validation rules for login
     */
    public array $login = [
        'email' => [
            'rules'  => 'required|valid_email',
            'errors' => [
                'required'    => 'Email is required',
                'valid_email' => 'Please enter a valid email address',
            ],
        ],
        'password' => [
            'rules'  => 'required',
            'errors' => [
                'required' => 'Password is required',
            ],
        ],
    ];

    /**
     * Validation rules for top-up
     */
    public array $topup = [
        'amount' => [
            'rules'  => 'required|integer|valid_amount[10000,10000000]',
            'errors' => [
                'required'     => 'Amount is required',
                'integer'      => 'Amount must be a number',
                'valid_amount' => 'Amount must be between Rp 10.000 and Rp 10.000.000',
            ],
        ],
    ];

    /**
     * Validation rules for transfer
     */
    public array $transfer = [
        'recipient' => [
            'rules'  => 'required|no_sql_injection',
            'errors' => [
                'required' => 'Recipient is required',
            ],
        ],
        'amount' => [
            'rules'  => 'required|integer|valid_amount[1000,50000000]',
            'errors' => [
                'required'     => 'Amount is required',
                'integer'      => 'Amount must be a number',
                'valid_amount' => 'Amount must be between Rp 1.000 and Rp 50.000.000',
            ],
        ],
    ];

    /**
     * Validation rules for profile update
     */
    public array $profile = [
        'name' => [
            'rules'  => 'required|min_length[3]|max_length[100]|no_xss',
            'errors' => [
                'required'   => 'Name is required',
                'min_length' => 'Name must be at least 3 characters',
            ],
        ],
        'phone' => [
            'rules'  => 'permit_empty|valid_phone',
            'errors' => [
                'valid_phone' => 'Please enter a valid phone number',
            ],
        ],
    ];
}
