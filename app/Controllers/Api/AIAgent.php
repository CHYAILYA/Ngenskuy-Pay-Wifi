<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AI Agent API Controller
 * 
 * Proxy endpoints to communicate with the Python AI Agent backend.
 * This allows the frontend to communicate with AI services through
 * the main CodeIgniter application.
 */
class AIAgent extends BaseController
{
    /**
     * AI Agent backend URL
     */
    protected string $agentUrl;

    /**
     * Chat with AI Assistant
     * 
     * GET /agent/chat?message=xxx
     */
    public function chat()
    {
        $this->agentUrl = getenv('PYTHON_AGENT_URL') ?: 'http://localhost:5000';
        // Support both GET query param and POST JSON body
        $message = $this->request->getGet('message') ?? '';
        if (empty($message)) {
            $json = $this->request->getJSON(true);
            $message = $json['message'] ?? '';
        }
        if (empty($message)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Message is required. Use ?message=your_message'
            ]);
        }


        // Cek login: user harus sudah login (ada session user)
        $session = session();
        $userData = $session->get('user');
        $userId = $userData['id'] ?? null;
        $userRole = $userData['role'] ?? 'user';
        // Izinkan akses publik jika dari /pay/* (untuk pertanyaan pembayaran saja)
        $referer = $this->request->getHeaderLine('Referer');
        $uri = $this->request->getUri()->getPath();
        $isPayPage = (strpos($referer, '/pay/') !== false) || (strpos($uri, '/pay/') === 0);
        if (!$userId && !$isPayPage) {
            // Jika bukan dari /pay/* dan belum login, redirect/login required
            if ($this->request->getMethod() === 'post') {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Authentication required'
                ])->setStatusCode(401);
            } else {
                return redirect()->to(base_url('login'));
            }
        }
        // Ambil payment context dari POST jika ada
        $incomingContext = [];
        if ($this->request->getMethod() === 'post') {
            $json = $this->request->getJSON(true);
            if (isset($json['context']) && is_array($json['context'])) {
                $incomingContext = $json['context'];
            }
        }

        // PATCH: Untuk GET, jika user login, selalu kirim context user (wallet, transaksi, dll)
        if ($this->request->getMethod() === 'get' && $userId) {
            $context = $this->getUserContext($userId, $userRole);
        } elseif (!empty($incomingContext) && is_array($incomingContext)) {
            $context = $incomingContext;
            // Jika ada userId, tambahkan info user ke context (tidak menimpa payment)
            if ($userId) {
                $userContext = $this->getUserContext($userId, $userRole);
                foreach ($userContext as $k => $v) {
                    if (!isset($context[$k])) {
                        $context[$k] = $v;
                    }
                }
            }
        } else {
            $context = $this->getUserContext($userId, $userRole);
        }

        // DEBUG: Log context before sending to Python backend
        log_message('debug', '[AI_AGENT] Sending context to Python backend: ' . json_encode($context));

        // POST ke backend Python
        $client = \Config\Services::curlrequest();
        try {
            $response = $client->request('POST', $this->getAgentUrl() . '/api/chat/', [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'message' => $message,
                    'context' => $context // kirim sebagai field baru
                ],
                'timeout' => 30
            ]);
            $body = $response->getBody();
            $result = json_decode($body, true);
            $content = null;
            if (isset($result['choices'][0]['message']['content'])) {
                $content = $result['choices'][0]['message']['content'];
            }
            return $this->response->setJSON([
                'success' => true,
                'message' => $content, // Ubah di sini, langsung ambil isi pesan AI
                'raw' => $result // opsional, untuk debug
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal menghubungi AI Agent',
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get user context from database for AI
     */
    private function getUserContext(?int $userId, string $role = 'user'): array
    {
        if (!$userId) {
            return ['role' => 'guest'];
        }
        
        $db = \Config\Database::connect();
        $context = ['role' => $role, 'user_id' => $userId];
        
        // Get user info
        $user = $db->table('users')->where('id', $userId)->get()->getRowArray();
        if ($user) {
            $context['user'] = [
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'card_number' => $user['card_number'] ?? null
            ];
        }
        
        // Get wallet info
        $wallet = $db->table('wallets')->where('user_id', $userId)->get()->getRowArray();
        if ($wallet) {
            $context['wallet'] = [
                'balance' => (float)$wallet['balance'],
                'balance_formatted' => 'Rp ' . number_format($wallet['balance'], 0, ',', '.')
            ];
        }
        
        // Get wallet transactions (all types: topup, transfer, payment)
        $walletTx = $db->table('wallet_transactions')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();
        
        if ($walletTx) {
            $context['wallet_transactions'] = array_map(function($t) {
                return [
                    'type' => $t['type'],  // credit or debit
                    'amount' => (float)$t['amount'],
                    'description' => $t['description'],
                    'reference_type' => $t['reference_type'] ?? null, // topup, bill_payment, transfer
                    'balance_after' => (float)($t['balance_after'] ?? 0),
                    'date' => $t['created_at']
                ];
            }, $walletTx);
            
            // Calculate spending/income stats
            $totalCredit = array_sum(array_map(function($t) {
                return $t['type'] === 'credit' ? (float)$t['amount'] : 0;
            }, $walletTx));
            $totalDebit = array_sum(array_map(function($t) {
                return $t['type'] === 'debit' ? (float)$t['amount'] : 0;
            }, $walletTx));
            $context['wallet_stats'] = [
                'total_income' => $totalCredit,
                'total_expense' => $totalDebit,
                'transaction_count' => count($walletTx)
            ];
        }
        
        // Get recent topups (last 10)
        $transactions = $db->table('topups')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();
        
        if ($transactions) {
            $context['recent_topups'] = array_map(function($t) {
                return [
                    'order_id' => $t['order_id'],
                    'amount' => (float)$t['amount'],
                    'status' => $t['status'],
                    'date' => $t['created_at']
                ];
            }, $transactions);
            
            // Calculate stats
            $totalTopup = array_sum(array_map(function($t) {
                return $t['status'] === 'success' ? $t['amount'] : 0;
            }, $transactions));
            $context['topup_stats'] = [
                'total_successful' => $totalTopup,
                'count' => count($transactions)
            ];
        }
        
        // If merchant, get merchant-specific data
        if ($role === 'merchant') {
            $merchant = $db->table('merchants')->where('user_id', $userId)->get()->getRowArray();
            if ($merchant) {
                $context['merchant'] = [
                    'name' => $merchant['business_name'] ?? $merchant['name'] ?? null,
                    'business_type' => $merchant['business_type'] ?? null,
                    'qr_code' => $merchant['qr_code'] ?? null,
                    'merchant_id' => $merchant['merchant_id'] ?? null,
                    'balance' => (float)($merchant['balance'] ?? 0)
                ];
                
                // Get merchant transactions from merchant_transactions table
                $merchantTx = $db->table('merchant_transactions')
                    ->where('merchant_id', $merchant['id'])
                    ->orderBy('created_at', 'DESC')
                    ->limit(20)
                    ->get()
                    ->getResultArray();
                
                if ($merchantTx) {
                    $context['merchant_transactions'] = array_map(function($t) {
                        return [
                            'amount' => (float)$t['amount'],
                            'net_amount' => (float)($t['net_amount'] ?? $t['amount']),
                            'status' => $t['status'],
                            'description' => $t['description'] ?? null,
                            'date' => $t['created_at']
                        ];
                    }, $merchantTx);
                    
                    // Calculate merchant stats
                    $totalIncome = array_sum(array_map(function($t) {
                        return $t['status'] === 'success' ? (float)($t['net_amount'] ?? $t['amount']) : 0;
                    }, $merchantTx));
                    $todayIncome = array_sum(array_map(function($t) {
                        return ($t['status'] === 'success' && date('Y-m-d', strtotime($t['created_at'])) === date('Y-m-d')) 
                            ? (float)($t['net_amount'] ?? $t['amount']) : 0;
                    }, $merchantTx));
                    $context['merchant_stats'] = [
                        'total_income' => $totalIncome,
                        'today_income' => $todayIncome,
                        'transaction_count' => count($merchantTx)
                    ];
                }
            }
        }
        
        // Get bills if any
        $bills = $db->table('bills')
            ->where('user_id', $userId)
            ->where('status', 'unpaid')
            ->get()
            ->getResultArray();
        
        if ($bills) {
            $context['unpaid_bills'] = array_map(function($b) {
                return [
                    'type' => $b['type'],
                    'amount' => (float)$b['amount'],
                    'due_date' => $b['due_date'] ?? null
                ];
            }, $bills);
        }
        
        return $context;
    }
    
    /**
     * Call AI with full context
     */
    private function callAIWithContext(string $message, array $context, ?int $userId): array
    {
        // Build context string for AI
        $contextStr = $this->buildContextString($context);
        
        // Try Python Advanced AI backend first
        $params = [
            'message' => $message,
            'context' => json_encode($context)
        ];
        
        // Use advanced AI endpoint for multi-agent processing
        $response = $this->callAgentGet('/api/ai/advanced/chat', $params, $userId);
        
        // Check if response contains data from advanced AI
        if (isset($response['success']) && $response['success'] && isset($response['data'])) {
            $data = $response['data'];
            return [
                'success' => true,
                'response' => $data['response'] ?? 'OK',
                'data' => $data['data'] ?? null,
                'actions' => $data['actions'] ?? [],
                'insights' => $data['insights'] ?? [],
                'metadata' => $data['metadata'] ?? []
            ];
        }
        
        // Fallback to regular AI chat
        $response = $this->callAgentGet('/api/ai/chat', $params, $userId);
        
        // If Python backend fails, use direct Kolosal AI with context
        if (!isset($response['success']) || !$response['success']) {
            return $this->callKolosalDirect($message, $userId, $contextStr);
        }
        
        return $response;
    }
    
    /**
     * Build context string for AI prompt
     */
    private function buildContextString(array $context): string
    {
        $str = "\n\n=== KONTEKS USER ===\n";
        
        if (isset($context['user'])) {
            $str .= "Nama: {$context['user']['name']}\n";
            $str .= "Email: {$context['user']['email']}\n";
            $str .= "Role: {$context['user']['role']}\n";
        }
        
        if (isset($context['wallet'])) {
            $str .= "\n💰 SALDO WALLET: {$context['wallet']['balance_formatted']}\n";
        }
        
        if (isset($context['topup_stats'])) {
            $str .= "\n📊 STATISTIK TOP UP:\n";
            $str .= "- Total berhasil: Rp " . number_format($context['topup_stats']['total_successful'], 0, ',', '.') . "\n";
            $str .= "- Jumlah transaksi: {$context['topup_stats']['count']}\n";
        }
        
        if (isset($context['recent_topups']) && count($context['recent_topups']) > 0) {
            $str .= "\n📋 TOP UP TERAKHIR:\n";
            foreach (array_slice($context['recent_topups'], 0, 5) as $tx) {
                $amount = 'Rp ' . number_format($tx['amount'], 0, ',', '.');
                $str .= "- {$tx['date']}: {$amount} ({$tx['status']})\n";
            }
        }
        
        if (isset($context['merchant'])) {
            $str .= "\n🏪 INFO MERCHANT:\n";
            $str .= "- Nama Usaha: {$context['merchant']['name']}\n";
            if ($context['merchant']['business_type']) {
                $str .= "- Jenis Usaha: {$context['merchant']['business_type']}\n";
            }
            if (isset($context['merchant']['merchant_id'])) {
                $str .= "- Merchant ID: {$context['merchant']['merchant_id']}\n";
            }
            if (isset($context['merchant']['balance'])) {
                $str .= "- Saldo Merchant: Rp " . number_format($context['merchant']['balance'], 0, ',', '.') . "\n";
            }
        }
        
        if (isset($context['merchant_stats'])) {
            $str .= "\n📈 STATISTIK MERCHANT:\n";
            $str .= "- Total Pendapatan: Rp " . number_format($context['merchant_stats']['total_income'], 0, ',', '.') . "\n";
            $str .= "- Pendapatan Hari Ini: Rp " . number_format($context['merchant_stats']['today_income'] ?? 0, 0, ',', '.') . "\n";
            $str .= "- Jumlah Transaksi: {$context['merchant_stats']['transaction_count']}\n";
        }
        
        if (isset($context['merchant_transactions']) && count($context['merchant_transactions']) > 0) {
            $str .= "\n📋 TRANSAKSI MERCHANT TERAKHIR:\n";
            foreach (array_slice($context['merchant_transactions'], 0, 5) as $tx) {
                $amount = 'Rp ' . number_format($tx['amount'], 0, ',', '.');
                $desc = $tx['description'] ? " - {$tx['description']}" : '';
                $str .= "- {$tx['date']}: {$amount} ({$tx['status']}){$desc}\n";
            }
        }
        
        if (isset($context['wallet_stats'])) {
            $str .= "\n💵 RINGKASAN KEUANGAN:\n";
            $str .= "- Total Pemasukan: Rp " . number_format($context['wallet_stats']['total_income'], 0, ',', '.') . "\n";
            $str .= "- Total Pengeluaran: Rp " . number_format($context['wallet_stats']['total_expense'], 0, ',', '.') . "\n";
        }
        
        if (isset($context['wallet_transactions']) && count($context['wallet_transactions']) > 0) {
            $str .= "\n📋 RIWAYAT TRANSAKSI WALLET:\n";
            foreach (array_slice($context['wallet_transactions'], 0, 5) as $tx) {
                $amount = 'Rp ' . number_format($tx['amount'], 0, ',', '.');
                $type = $tx['type'] === 'credit' ? '➕' : '➖';
                $refType = $tx['reference_type'] ? " ({$tx['reference_type']})" : '';
                $str .= "- {$tx['date']}: {$type} {$amount}{$refType} - {$tx['description']}\n";
            }
        }
        
        if (isset($context['unpaid_bills']) && count($context['unpaid_bills']) > 0) {
            $str .= "\n⚠️ TAGIHAN BELUM DIBAYAR:\n";
            foreach ($context['unpaid_bills'] as $bill) {
                $amount = 'Rp ' . number_format($bill['amount'], 0, ',', '.');
                $str .= "- {$bill['type']}: {$amount}\n";
            }
        }
        
        return $str;
    }
    
    /**
     * Payment Assistant
     * 
     * GET /api/ai/payment/assistant?message=xxx
     */
    public function paymentAssistant()
    {
        $message = $this->request->getVar('message') ?? '';
        
        if (empty($message)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Message is required. Use ?message=your_message'
            ]);
        }
        
        $userId = session()->get('user_id');
        
        if (!$userId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Authentication required'
            ])->setStatusCode(401);
        }
        
        $response = $this->callAgent('/api/payment/assistant', [
            'message' => $message
        ], $userId);
        
        return $this->response->setJSON($response);
    }
    
    /**
     * Device Control via Natural Language
     * 
     * GET /api/ai/device/command?command=xxx&execute=true
     */
    public function deviceCommand()
    {
        $command = $this->request->getVar('command') ?? '';
        $execute = $this->request->getVar('execute') ?? true;
        
        if (is_string($execute)) {
            $execute = filter_var($execute, FILTER_VALIDATE_BOOLEAN);
        }
        
        if (empty($command)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Command is required. Use ?command=your_command'
            ]);
        }
        
        $userId = session()->get('user_id');
        
        if (!$userId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Authentication required'
            ])->setStatusCode(401);
        }
        
        $response = $this->callAgent('/api/device/command', [
            'command' => $command,
            'execute' => $execute
        ], $userId);
        
        return $this->response->setJSON($response);
    }
    
    /**
     * List User's Devices
     * 
     * GET /api/ai/device/list
     */
    public function deviceList()
    {
        $userId = session()->get('user_id');
        
        if (!$userId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Authentication required'
            ])->setStatusCode(401);
        }
        
        $response = $this->callAgent('/api/device/list', [], $userId, 'GET');
        
        return $this->response->setJSON($response);
    }
    
    /**
     * Financial Analysis
     * 
     * GET /api/ai/finance/analyze
     */
    public function financeAnalyze()
    {
        $days = $this->request->getGet('days') ?? 30;
        
        $userId = session()->get('user_id');
        
        if (!$userId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Authentication required'
            ])->setStatusCode(401);
        }
        
        $response = $this->callAgent('/api/finance/analyze?days=' . $days, [], $userId, 'GET');
        
        return $this->response->setJSON($response);
    }
    
    /**
     * Financial Advice
     * 
     * GET /api/ai/finance/advice?question=xxx
     */
    public function financeAdvice()
    {
        $question = $this->request->getVar('question') ?? '';
        
        if (empty($question)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Question is required. Use ?question=your_question'
            ]);
        }
        
        $userId = session()->get('user_id');
        
        if (!$userId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Authentication required'
            ])->setStatusCode(401);
        }
        
        $response = $this->callAgent('/api/finance/advice', [
            'question' => $question
        ], $userId);
        
        return $this->response->setJSON($response);
    }
    
    /**
     * Get Notifications
     * 
     * GET /api/ai/notifications
     */
    public function notifications()
    {
        $userId = session()->get('user_id');
        
        if (!$userId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Authentication required'
            ])->setStatusCode(401);
        }
        
        $response = $this->callAgent('/api/notification/list', [], $userId, 'GET');
        
        return $this->response->setJSON($response);
    }
    
    /**
     * Get Daily Summary
     * 
     * GET /api/ai/summary
     */
    public function summary()
    {
        $userId = session()->get('user_id');
        
        if (!$userId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Authentication required'
            ])->setStatusCode(401);
        }
        
        $response = $this->callAgent('/api/notification/smart-summary', [], $userId, 'GET');
        
        return $this->response->setJSON($response);
    }
    
    /**
     * Voice Command for Devices
     * 
     * GET /api/ai/voice?text=xxx
     */
    public function voiceCommand()
    {
        $text = $this->request->getVar('text') ?? '';
        
        if (empty($text)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Voice text is required. Use ?text=your_command'
            ]);
        }
        
        $userId = session()->get('user_id');
        
        if (!$userId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Authentication required'
            ])->setStatusCode(401);
        }
        
        $response = $this->callAgent('/api/device/voice-command', [
            'text' => $text,
            'language' => 'id'
        ], $userId);
        
        return $this->response->setJSON($response);
    }
    
    /**
     * Get Quick Actions
     * 
     * GET /api/ai/quick-actions
     */
    public function quickActions()
    {
        $userId = session()->get('user_id');
        
        if (!$userId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Authentication required'
            ])->setStatusCode(401);
        }
        
        $response = $this->callAgent('/api/payment/quick-actions', [], $userId, 'GET');
        
        return $this->response->setJSON($response);
    }
    
    /**
     * Get AI Proactive Insights
     * 
     * GET /agent/insights
     * Returns personalized insights based on user data
     */
    public function insights()
    {
        $session = session();
        $userData = $session->get('user');
        $userId = $userData['id'] ?? null;
        
        if (!$userId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Authentication required'
            ])->setStatusCode(401);
        }
        
        // Try Python backend first
        $response = $this->callAgentGet('/api/ai/advanced/insights', [], $userId);
        
        if (isset($response['success']) && $response['success']) {
            return $this->response->setJSON($response);
        }
        
        // Fallback: Generate insights locally
        $insights = $this->generateLocalInsights($userId);
        
        return $this->response->setJSON([
            'success' => true,
            'data' => ['insights' => $insights, 'count' => count($insights)]
        ]);
    }
    
    /**
     * Get Smart Suggestions
     * 
     * GET /agent/suggestions
     */
    public function suggestions()
    {
        $session = session();
        $userData = $session->get('user');
        $userId = $userData['id'] ?? null;
        $lastMessage = $this->request->getGet('last_message') ?? '';
        
        // Try Python backend
        $response = $this->callAgentGet('/api/ai/advanced/suggest', [
            'last_message' => $lastMessage
        ], $userId);
        
        if (isset($response['success']) && $response['success']) {
            return $this->response->setJSON($response);
        }
        
        // Fallback suggestions
        $suggestions = [
            "Berapa saldo saya?",
            "Analisis pengeluaran",
            "Lihat transaksi terakhir",
            "Tips menabung"
        ];
        
        return $this->response->setJSON([
            'success' => true,
            'data' => ['suggestions' => $suggestions]
        ]);
    }
    
    /**
     * Clear Conversation History
     * 
     * GET /agent/clear-history
     */
    public function clearHistory()
    {
        $session = session();
        $userData = $session->get('user');
        $userId = $userData['id'] ?? null;
        
        if (!$userId) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'No history to clear'
            ]);
        }
        
        $response = $this->callAgentGet('/api/ai/advanced/clear', [], $userId);
        
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Conversation history cleared'
        ]);
    }
    
    /**
     * Generate local insights when Python backend is unavailable
     */
    private function generateLocalInsights(int $userId): array
    {
        $db = \Config\Database::connect();
        $insights = [];
        
        // Check wallet balance
        $wallet = $db->table('wallets')->where('user_id', $userId)->get()->getRowArray();
        if ($wallet && (float)$wallet['balance'] < 50000) {
            $insights[] = [
                'type' => 'warning',
                'icon' => '💰',
                'title' => 'Saldo Rendah',
                'message' => 'Saldo kamu tinggal Rp ' . number_format($wallet['balance'], 0, ',', '.') . '. Top up sekarang?',
                'action' => ['label' => 'Top Up', 'type' => 'topup']
            ];
        }
        
        // Check unpaid bills
        $bills = $db->table('bills')
            ->where('user_id', $userId)
            ->where('status', 'unpaid')
            ->get()
            ->getResultArray();
        
        if ($bills && count($bills) > 0) {
            $total = array_sum(array_column($bills, 'amount'));
            $insights[] = [
                'type' => 'alert',
                'icon' => '📄',
                'title' => 'Tagihan Menunggu',
                'message' => 'Ada ' . count($bills) . ' tagihan belum dibayar (Rp ' . number_format($total, 0, ',', '.') . ')',
                'action' => ['label' => 'Bayar Sekarang', 'type' => 'pay_bills']
            ];
        }
        
        // Check spending pattern
        $recentTx = $db->table('wallet_transactions')
            ->where('user_id', $userId)
            ->where('type', 'debit')
            ->where('created_at >=', date('Y-m-d', strtotime('-7 days')))
            ->selectSum('amount')
            ->get()
            ->getRowArray();
        
        if ($recentTx && (float)($recentTx['amount'] ?? 0) > 500000) {
            $insights[] = [
                'type' => 'info',
                'icon' => '📊',
                'title' => 'Pengeluaran Minggu Ini',
                'message' => 'Kamu sudah menghabiskan Rp ' . number_format($recentTx['amount'], 0, ',', '.') . ' minggu ini',
                'action' => ['label' => 'Lihat Detail', 'type' => 'spending_analysis']
            ];
        }
        
        // Greeting insight if no other insights
        if (empty($insights)) {
            $hour = (int)date('H');
            if ($hour < 12) {
                $greeting = 'Selamat pagi! ☀️';
            } elseif ($hour < 15) {
                $greeting = 'Selamat siang! 🌤️';
            } elseif ($hour < 18) {
                $greeting = 'Selamat sore! 🌅';
            } else {
                $greeting = 'Selamat malam! 🌙';
            }
            
            $insights[] = [
                'type' => 'tip',
                'icon' => '👋',
                'title' => $greeting,
                'message' => 'Semua terlihat baik. Ada yang bisa saya bantu hari ini?',
                'action' => ['label' => 'Bantuan', 'type' => 'help']
            ];
        }
        
        return $insights;
    }
    
    /**
     * Call AI Agent Backend
     */
    private function callAgent(string $endpoint, array $data = [], ?int $userId = null, string $method = 'POST'): array
    {
        $client = \Config\Services::curlrequest();
        
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
        
        if ($userId) {
            $headers['X-User-ID'] = $userId;
        }
        
        try {
            $options = [
                'headers' => $headers,
                'timeout' => 10,
                'http_errors' => false
            ];
            
            if ($method === 'POST' && !empty($data)) {
                $options['json'] = $data;
            }
            
            $response = $client->request($method, $this->agentUrl . $endpoint, $options);
            
            $body = $response->getBody();
            $result = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Fallback to direct Kolosal AI if Python backend fails
                return $this->callKolosalDirect($data['message'] ?? $data['question'] ?? $data['command'] ?? '', $userId);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            log_message('error', 'AI Agent error: ' . $e->getMessage());
            
            // Fallback to direct Kolosal AI
            return $this->callKolosalDirect($data['message'] ?? $data['question'] ?? $data['command'] ?? '', $userId);
        }
    }
    
    /**
     * Call AI Agent Backend with GET request and query params
     */
    private function callAgentGet(string $endpoint, array $params = [], ?int $userId = null): array
    {
        $client = \Config\Services::curlrequest();
        
        $headers = [
            'Accept' => 'application/json'
        ];
        
        if ($userId) {
            $headers['X-User-ID'] = (string)$userId;
        }
        
        // Build URL with query params
        $url = $this->getAgentUrl() . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        try {
            $response = $client->request('GET', $url, [
                'headers' => $headers,
                'timeout' => 60,
                'http_errors' => false
            ]);
            
            $body = $response->getBody();
            $result = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE || !isset($result['success'])) {
                // Fallback to direct Kolosal AI if Python backend fails
                return $this->callKolosalDirect($params['message'] ?? $params['question'] ?? $params['text'] ?? '', $userId);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            log_message('error', 'AI Agent GET error: ' . $e->getMessage());
            
            // Fallback to direct Kolosal AI
            return $this->callKolosalDirect($params['message'] ?? $params['question'] ?? $params['text'] ?? '', $userId);
        }
    }
    
    /**
     * Direct call to Kolosal AI (fallback when Python backend is down)
     */
    private function callKolosalDirect(string $message, ?int $userId = null, string $additionalContext = ''): array
    {
        if (empty($message)) {
            return [
                'success' => false,
                'message' => 'No message provided'
            ];
        }
        
        $apiKey = 'kol_eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoiNDkyMDgwNTEtZjc0Ni00NzI0LTlkNzQtNTI2OGFkYzhkMmVkIiwia2V5X2lkIjoiM2NkYWY3NzMtNzJlMS00ZTY0LWEzOGEtODI4MjlmYTAwZjVkIiwia2V5X25hbWUiOiJOZ2Vuc2t1eSIsImVtYWlsIjoiYmVudGFwcm9qZWN0LmlkQGdtYWlsLmNvbSIsInJhdGVfbGltaXRfcnBzIjpudWxsLCJtYXhfY3JlZGl0X3VzZSI6bnVsbCwiY3JlYXRlZF9hdCI6MTc2NDk0OTc3MiwiZXhwaXJlc19hdCI6MTc5NjQ4NTc3MiwiaWF0IjoxNzY0OTQ5NzcyfQ.NU8PG1w9Xz6KcyRltd4nN2BJlzQqymOiIw3FL984PTg';
        $apiUrl = 'https://api.kolosal.ai/v1/chat/completions';
        
        // Use provided context or fetch from database
        $userContext = $additionalContext;
        if (empty($userContext) && $userId) {
            $db = \Config\Database::connect();
            $user = $db->table('users')->where('id', $userId)->get()->getRowArray();
            $wallet = $db->table('wallets')->where('user_id', $userId)->get()->getRowArray();
            
            if ($user && $wallet) {
                $userContext = "\n\nKonteks User:\n- Nama: {$user['name']}\n- Email: {$user['email']}\n- Saldo: Rp " . number_format($wallet['balance'], 0, ',', '.') . "\n";
            }
        }
        
        $systemPrompt = "Kamu adalah UDARA AI Assistant, asisten cerdas untuk aplikasi UDARA (dompet digital dan smart home).

KEMAMPUAN KAMU:
1. 💰 Membantu dengan top up saldo dan pembayaran - bisa memberitahu saldo, riwayat transaksi
2. 📊 Memberikan analisis keuangan dan saran finansial berdasarkan data transaksi user
3. 🏪 Membantu merchant dengan analisis pendapatan, transaksi, dan tips bisnis
4. 🏠 Membantu kontrol perangkat smart home (lampu, AC, dll)
5. 🔔 Memberikan notifikasi dan pengingat cerdas

PENTING:
- Jawab dengan ramah, singkat, dan dalam Bahasa Indonesia
- Gunakan emoji untuk membuat respons lebih menarik
- Jika user bertanya tentang saldo/transaksi, gunakan data dari konteks yang diberikan
- Untuk merchant, berikan analisis bisnis yang berguna
- Jika tidak ada data, minta user untuk login atau jelaskan cara mengakses fitur
{$userContext}";
        
        $client = \Config\Services::curlrequest();
        
        try {
            $response = $client->request('POST', $apiUrl, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $apiKey
                ],
                'json' => [
                    'model' => 'claude-sonnet-4-20250514',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $message]
                    ],
                    'max_tokens' => 1024,
                    'temperature' => 0.7
                ],
                'timeout' => 60
            ]);
            
            $body = json_decode($response->getBody(), true);
            
            if (isset($body['choices'][0]['message']['content'])) {
                return [
                    'success' => true,
                    'response' => $body['choices'][0]['message']['content']
                ];
            }
            
            return [
                'success' => false,
                'message' => 'AI tidak memberikan respons',
                'debug' => $body
            ];
            
        } catch (\Exception $e) {
            log_message('error', 'Kolosal AI error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Maaf, AI sedang tidak tersedia. Silakan coba lagi nanti.',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Tambahkan helper di class AIAgent
    private function getAgentUrl(): string
    {
        return getenv('PYTHON_AGENT_URL') ?: 'http://103.85.60.82:5000';
    }
}
