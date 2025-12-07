<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAIAgentTables extends Migration
{
    public function up()
    {
        // AI Chat Logs Table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'message' => [
                'type' => 'LONGTEXT',
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);
        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->addKey('user_id');
        $this->forge->addKey('created_at');
        $this->forge->createTable('ai_chat_logs');

        // AI Conversations Table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'user_message' => [
                'type' => 'LONGTEXT',
                'null' => false,
            ],
            'ai_response' => [
                'type' => 'LONGTEXT',
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);
        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->addKey('user_id');
        $this->forge->addKey('created_at');
        $this->forge->createTable('ai_conversations');

        // AI Interactions Table (more detailed)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'user_message' => [
                'type' => 'LONGTEXT',
                'null' => false,
            ],
            'ai_response' => [
                'type' => 'LONGTEXT',
                'null' => false,
            ],
            'provider' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'kolosal',
            ],
            'response_time' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'comment'    => 'Response time in milliseconds',
            ],
            'tokens_used' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'sentiment' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => 'positive, negative, neutral',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);
        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->addKey('user_id');
        $this->forge->addKey('provider');
        $this->forge->addKey('created_at');
        $this->forge->createTable('ai_interactions');

        // AI Preferences Table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'unique'     => true,
            ],
            'language' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'default'    => 'id',
            ],
            'tone' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'friendly',
                'comment'    => 'friendly, professional, casual',
            ],
            'notification_enabled' => [
                'type'    => 'TINYINT',
                'default' => 1,
            ],
            'weekly_summary_enabled' => [
                'type'    => 'TINYINT',
                'default' => 1,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
                'update' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);
        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->createTable('ai_preferences');

        // AI User Context Cache (untuk performa)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'unique'     => true,
            ],
            'context_data' => [
                'type' => 'LONGTEXT',
                'null' => true,
                'comment' => 'JSON encoded user context',
            ],
            'last_updated' => [
                'type' => 'DATETIME',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
                'update' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);
        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->createTable('ai_user_context_cache');
    }

    public function down()
    {
        $this->forge->dropTable('ai_chat_logs');
        $this->forge->dropTable('ai_conversations');
        $this->forge->dropTable('ai_interactions');
        $this->forge->dropTable('ai_preferences');
        $this->forge->dropTable('ai_user_context_cache');
    }
}
