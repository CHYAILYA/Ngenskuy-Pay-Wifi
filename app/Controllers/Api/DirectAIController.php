<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\KolosalAIModel;

/**
 * Direct Kolosal AI Controller
 * Connects directly to Kolosal AI API without localhost dependency
 * All requests go directly to: https://api.kolosal.ai/v1/chat/completions
 */
class DirectAIController extends BaseController
{
    protected KolosalAIModel $kolosalAI;

    public function __construct()
    {
        $this->kolosalAI = new KolosalAIModel();
    }

    /**
     * General Chat with AI
     * GET /direct-ai/chat?message=xxx
     */
    public function chat()
    {
        $message = $this->request->getGet('message') ?? '';

        if (empty($message)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Message parameter required'
            ])->setStatusCode(400);
        }

        $session = session();
        $user = $session->get('user');
        $role = $user['role'] ?? 'user';

        $result = $this->kolosalAI->generalChat($message, $role);

        return $this->response->setJSON($result);
    }

    /**
     * Payment Assistant
     * GET /direct-ai/payment?message=xxx
     */
    public function payment()
    {
        $message = $this->request->getGet('message') ?? '';

        if (empty($message)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Message parameter required'
            ])->setStatusCode(400);
        }

        // Get payment context from database (optional)
        $paymentContext = $this->getPaymentContext();

        $result = $this->kolosalAI->paymentAssistant($message, $paymentContext);

        return $this->response->setJSON($result);
    }

    /**
     * Financial Advisor
     * GET /direct-ai/finance?question=xxx
     */
    public function finance()
    {
        $question = $this->request->getGet('question') ?? '';

        if (empty($question)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Question parameter required'
            ])->setStatusCode(400);
        }

        // Get financial context from database (optional)
        $financialData = $this->getFinancialContext();

        $result = $this->kolosalAI->financialAdvisor($question, $financialData);

        return $this->response->setJSON($result);
    }

    /**
     * Device Control
     * GET /direct-ai/device?command=xxx
     */
    public function device()
    {
        $command = $this->request->getGet('command') ?? '';

        if (empty($command)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Command parameter required'
            ])->setStatusCode(400);
        }

        // Get user's devices (optional)
        $devices = $this->getUserDevices();

        $result = $this->kolosalAI->deviceControl($command, $devices);

        return $this->response->setJSON($result);
    }

    /**
     * Sentiment Analysis
     * GET /direct-ai/sentiment?text=xxx
     */
    public function sentiment()
    {
        $text = $this->request->getGet('text') ?? '';

        if (empty($text)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Text parameter required'
            ])->setStatusCode(400);
        }

        $result = $this->kolosalAI->analyzeSentiment($text);

        return $this->response->setJSON($result);
    }

    /**
     * Summarize Text
     * GET /direct-ai/summarize?text=xxx&length=200
     */
    public function summarize()
    {
        $text = $this->request->getGet('text') ?? '';
        $length = (int)($this->request->getGet('length') ?? 200);

        if (empty($text)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Text parameter required'
            ])->setStatusCode(400);
        }

        $result = $this->kolosalAI->summarize($text, $length);

        return $this->response->setJSON($result);
    }

    /**
     * Test Connection to Kolosal AI
     * GET /direct-ai/test
     */
    public function test()
    {
        $result = $this->kolosalAI->chat('Halo, apa kabar?', 'Jawab dengan singkat: apa itu Kolosal AI?');

        return $this->response->setJSON([
            'success' => $result['success'],
            'message' => 'Test connection to Kolosal AI API',
            'response' => $result['response'] ?? null,
            'error' => $result['error'] ?? null,
            'api_endpoint' => 'https://api.kolosal.ai/v1/chat/completions',
            'model' => 'Claude Sonnet 4.5'
        ]);
    }

    /**
     * Get payment context from database
     */
    private function getPaymentContext(): array
    {
        $session = session();
        $user = $session->get('user');
        $userId = $user['id'] ?? null;

        if (!$userId) {
            return [];
        }

        try {
            $db = \Config\Database::connect();
            
            $wallet = $db->table('wallets')
                ->where('user_id', $userId)
                ->get()
                ->getRowArray();

            $transactions = $db->table('topups')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'DESC')
                ->limit(5)
                ->get()
                ->getResultArray();

            $bills = $db->table('bills')
                ->where('user_id', $userId)
                ->where('status', 'unpaid')
                ->get()
                ->getResultArray();

            return [
                'balance' => (float)($wallet['balance'] ?? 0),
                'recent_transactions' => $transactions,
                'unpaid_bills' => $bills
            ];
        } catch (\Exception $e) {
            log_message('error', 'Error getting payment context: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get financial context from database
     */
    private function getFinancialContext(): array
    {
        $session = session();
        $user = $session->get('user');
        $userId = $user['id'] ?? null;

        if (!$userId) {
            return [];
        }

        try {
            $db = \Config\Database::connect();

            // Get wallet transactions
            $transactions = $db->table('wallet_transactions')
                ->where('user_id', $userId)
                ->where('created_at >=', date('Y-m-d', strtotime('-30 days')))
                ->get()
                ->getResultArray();

            $totalIncome = 0;
            $totalExpense = 0;

            foreach ($transactions as $tx) {
                if ($tx['type'] === 'credit') {
                    $totalIncome += (float)$tx['amount'];
                } else {
                    $totalExpense += (float)$tx['amount'];
                }
            }

            $avgDaily = !empty($transactions) ? $totalExpense / 30 : 0;

            return [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'avg_daily' => $avgDaily,
                'transaction_count' => count($transactions)
            ];
        } catch (\Exception $e) {
            log_message('error', 'Error getting financial context: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user devices from database
     */
    private function getUserDevices(): array
    {
        $session = session();
        $user = $session->get('user');
        $userId = $user['id'] ?? null;

        if (!$userId) {
            return [];
        }

        try {
            $db = \Config\Database::connect();
            
            $devices = $db->table('devices')
                ->where('user_id', $userId)
                ->get()
                ->getResultArray();

            return array_map(function($device) {
                return [
                    'id' => $device['id'],
                    'name' => $device['name'],
                    'type' => $device['type'],
                    'status' => $device['status'] ?? 'off'
                ];
            }, $devices);
        } catch (\Exception $e) {
            log_message('error', 'Error getting user devices: ' . $e->getMessage());
            return [];
        }
    }
}
