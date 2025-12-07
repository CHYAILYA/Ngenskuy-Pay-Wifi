<?php

namespace Database\Seeders;

use CodeIgniter\Database\Seeder;

class AIAgentSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'user_id' => 1,
                'message' => 'Berapa saldo saya?',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ],
            [
                'user_id' => 1,
                'message' => 'Bagaimana cara top up?',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ],
            [
                'user_id' => 1,
                'message' => 'Lihat transaksi terakhir',
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
            ],
        ];

        // Insert AI Chat Logs
        foreach ($data as $row) {
            $this->db->table('ai_chat_logs')->insert($row);
        }

        // Insert AI Conversations
        $conversations = [
            [
                'user_id' => 1,
                'user_message' => 'Berapa saldo saya?',
                'ai_response' => 'Saldo kamu saat ini adalah Rp 1.500.000. Apakah ada yang bisa saya bantu?',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ],
            [
                'user_id' => 1,
                'user_message' => 'Bagaimana cara top up?',
                'ai_response' => '💰 Untuk top up saldo, kamu bisa:\n1. Klik menu Top Up\n2. Pilih nominal\n3. Pilih metode pembayaran\n4. Selesaikan pembayaran\n\nSaat ini saldo kamu: Rp 1.500.000',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ],
        ];

        foreach ($conversations as $row) {
            $this->db->table('ai_conversations')->insert($row);
        }

        // Insert AI Interactions
        $interactions = [
            [
                'user_id' => 1,
                'user_message' => 'Berapa saldo saya?',
                'ai_response' => 'Saldo kamu saat ini adalah Rp 1.500.000',
                'provider' => 'kolosal',
                'response_time' => 250,
                'tokens_used' => 45,
                'sentiment' => 'neutral',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ],
            [
                'user_id' => 1,
                'user_message' => 'Bagaimana cara menghemat?',
                'ai_response' => 'Berikut tips menghemat untuk kamu...',
                'provider' => 'kolosal',
                'response_time' => 320,
                'tokens_used' => 85,
                'sentiment' => 'positive',
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
            ],
        ];

        foreach ($interactions as $row) {
            $this->db->table('ai_interactions')->insert($row);
        }

        // Insert AI Preferences
        $preferences = [
            [
                'user_id' => 1,
                'language' => 'id',
                'tone' => 'friendly',
                'notification_enabled' => 1,
                'weekly_summary_enabled' => 1,
                'updated_at' => date('Y-m-d H:i:s')
            ],
        ];

        foreach ($preferences as $row) {
            $this->db->table('ai_preferences')->insert($row);
        }

        echo "AI Agent seed data inserted successfully!";
    }
}
