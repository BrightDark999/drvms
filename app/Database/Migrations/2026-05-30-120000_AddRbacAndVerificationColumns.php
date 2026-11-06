<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRbacAndVerificationColumns extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('volunteers') && ! $this->db->fieldExists('id_verification_status', 'volunteers')) {
            $this->forge->addColumn('volunteers', [
                'id_verification_status' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'none', 'after' => 'id_verification_path'],
            ]);
        }

        if (! $this->db->tableExists('user_profiles')) {
            $this->forge->addField([
                'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'user_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'barangay_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'created_at'  => ['type' => 'DATETIME', 'null' => true],
                'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('user_id');
            $this->forge->createTable('user_profiles', true);
        }

        if ($this->db->tableExists('evac_families') && ! $this->db->fieldExists('checked_in_at', 'evac_families')) {
            $this->forge->addColumn('evac_families', [
                'checked_in_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'family_qr_token'],
            ]);
        }

        if (! $this->db->tableExists('chatbot_messages')) {
            $this->forge->addField([
                'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'role'       => ['type' => 'VARCHAR', 'constraint' => 16],
                'message'    => ['type' => 'TEXT'],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('user_id');
            $this->forge->createTable('chatbot_messages', true);
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('chatbot_messages')) {
            $this->forge->dropTable('chatbot_messages', true);
        }
        if ($this->db->tableExists('user_profiles')) {
            $this->forge->dropTable('user_profiles', true);
        }
        if ($this->db->tableExists('volunteers') && $this->db->fieldExists('id_verification_status', 'volunteers')) {
            $this->forge->dropColumn('volunteers', 'id_verification_status');
        }
        if ($this->db->tableExists('evac_families') && $this->db->fieldExists('checked_in_at', 'evac_families')) {
            $this->forge->dropColumn('evac_families', 'checked_in_at');
        }
    }
}
