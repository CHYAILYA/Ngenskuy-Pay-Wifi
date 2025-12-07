<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration untuk ESP AI Agent Features
 * - Offline transactions sync
 * - Debt/Credit system (Hutang/Piutang)
 * - Payment requests
 */
class CreateESPAgentTables extends Migration
{
    public function up()
    {
        // =============================================
        // Table: offline_transactions
        // Menyimpan transaksi offline dari ESP untuk sync
        // =============================================
        if (!$this->db->tableExists('offline_transactions')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'offline_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => false,
                ],
                'merchant_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'user_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '15,2',
                    'default' => 0,
                ],
                'description' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'transaction_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                ],
                'synced' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                ],
                'synced_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('offline_id');
            $this->forge->addKey('merchant_id');
            $this->forge->addKey('user_id');
            $this->forge->addKey('synced');
            $this->forge->createTable('offline_transactions');
        }
        
        // =============================================
        // Table: debts
        // Sistem hutang/piutang
        // =============================================
        if (!$this->db->tableExists('debts')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'debt_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => false,
                ],
                'debtor_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'comment' => 'User yang berhutang',
                ],
                'creditor_type' => [
                    'type' => 'ENUM',
                    'constraint' => ['user', 'merchant'],
                    'default' => 'merchant',
                ],
                'creditor_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'comment' => 'User/Merchant yang memberikan hutang',
                ],
                'amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '15,2',
                    'default' => 0,
                ],
                'original_amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '15,2',
                    'default' => 0,
                ],
                'description' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'status' => [
                    'type' => 'ENUM',
                    'constraint' => ['pending', 'partial', 'paid', 'cancelled'],
                    'default' => 'pending',
                ],
                'due_date' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'paid_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('debt_id');
            $this->forge->addKey('debtor_id');
            $this->forge->addKey(['creditor_type', 'creditor_id']);
            $this->forge->addKey('status');
            $this->forge->createTable('debts');
        }
        
        // =============================================
        // Table: debt_payments
        // Riwayat pembayaran hutang
        // =============================================
        if (!$this->db->tableExists('debt_payments')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'debt_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '15,2',
                    'default' => 0,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('debt_id');
            $this->forge->createTable('debt_payments');
        }
        
        // =============================================
        // Table: payment_requests
        // Permintaan pembayaran (minta bayar ke orang lain)
        // =============================================
        if (!$this->db->tableExists('payment_requests')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'request_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => false,
                ],
                'from_user_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'comment' => 'User yang meminta pembayaran',
                ],
                'to_user_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'comment' => 'User yang diminta membayar',
                ],
                'amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '15,2',
                    'default' => 0,
                ],
                'description' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'status' => [
                    'type' => 'ENUM',
                    'constraint' => ['pending', 'approved', 'rejected', 'expired', 'cancelled'],
                    'default' => 'pending',
                ],
                'expires_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'responded_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('request_id');
            $this->forge->addKey('from_user_id');
            $this->forge->addKey('to_user_id');
            $this->forge->addKey('status');
            $this->forge->createTable('payment_requests');
        }
        
        // =============================================
        // Table: notifications (if not exists)
        // =============================================
        if (!$this->db->tableExists('notifications')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'user_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'title' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                ],
                'message' => [
                    'type' => 'TEXT',
                ],
                'type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'default' => 'info',
                ],
                'is_read' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                ],
                'read_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('user_id');
            $this->forge->addKey('is_read');
            $this->forge->createTable('notifications');
        }
    }

    public function down()
    {
        $this->forge->dropTable('offline_transactions', true);
        $this->forge->dropTable('debts', true);
        $this->forge->dropTable('debt_payments', true);
        $this->forge->dropTable('payment_requests', true);
        // Don't drop notifications as it might be used elsewhere
    }
}
