<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Add missing fields to users table
 */
class AlterUsersTable extends Migration
{
    public function up()
    {
        // Add card_number if not exists
        if (!$this->db->fieldExists('card_number', 'users')) {
            $this->forge->addColumn('users', [
                'card_number' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '20',
                    'null'       => true,
                    'after'      => 'role',
                ],
            ]);
            
            // Add index for card_number
            $this->forge->addKey('card_number', false, false, 'idx_users_card');
        }
        
        // Add updated_at if not exists
        if (!$this->db->fieldExists('updated_at', 'users')) {
            $this->forge->addColumn('users', [
                'updated_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                    'after' => 'created_at',
                ],
            ]);
        }
    }

    public function down()
    {
        // Remove columns if they exist
        if ($this->db->fieldExists('card_number', 'users')) {
            $this->forge->dropColumn('users', 'card_number');
        }
        
        if ($this->db->fieldExists('updated_at', 'users')) {
            $this->forge->dropColumn('users', 'updated_at');
        }
    }
}
