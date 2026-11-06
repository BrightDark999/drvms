<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Auth;

/**
 * Relief inventory/distributions and AI features moved to separate systems.
 * Drops local tables and purges related audit log rows.
 */
class RemoveReliefAndAiTables extends Migration
{
    /** @var list<string> */
    private array $tablesToDrop = [
        'relief_distributions',
        'relief_items',
        'ai_suggestions',
        'chatbot_messages',
        'social_media_flags',
    ];

    public function up(): void
    {
        $this->purgeReliefAndAiAuditLogs();

        foreach ($this->tablesToDrop as $table) {
            if ($this->db->tableExists($table)) {
                $this->forge->dropTable($table, true);
            }
        }
    }

    public function down(): void
    {
        $users = config(Auth::class)->tables['users'];
        $attrs = ($this->db->DBDriver === 'MySQLi') ? ['ENGINE' => 'InnoDB'] : [];

        if (! $this->db->tableExists('relief_items')) {
            $this->forge->addField([
                'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'sku'                 => ['type' => 'VARCHAR', 'constraint' => 64],
                'name'                => ['type' => 'VARCHAR', 'constraint' => 255],
                'category'            => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'quantity'            => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
                'unit'                => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'pc'],
                'low_stock_threshold' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'null' => true],
                'expires_at'          => ['type' => 'DATE', 'null' => true],
                'warehouse_location'  => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
                'created_at'          => ['type' => 'DATETIME', 'null' => true],
                'updated_at'          => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addUniqueKey('sku');
            $this->forge->createTable('relief_items', false, $attrs);
        }

        if (! $this->db->tableExists('relief_distributions')) {
            $this->forge->addField([
                'id'                     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'relief_item_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'quantity'               => ['type' => 'DECIMAL', 'constraint' => '14,2'],
                'beneficiary_name'       => ['type' => 'VARCHAR', 'constraint' => 160],
                'beneficiary_contact'    => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'barangay_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'claim_qr_token'         => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'claim_status'           => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'pending'],
                'scheduled_claim_at'     => ['type' => 'DATETIME', 'null' => true],
                'claimed_at'             => ['type' => 'DATETIME', 'null' => true],
                'distributed_at'         => ['type' => 'DATETIME', 'null' => true],
                'distributed_by_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'incident_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'notes'                  => ['type' => 'TEXT', 'null' => true],
                'created_at'             => ['type' => 'DATETIME', 'null' => true],
                'updated_at'             => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addUniqueKey('claim_qr_token');
            $this->forge->addForeignKey('relief_item_id', 'relief_items', 'id', 'RESTRICT', 'CASCADE');
            $this->forge->addForeignKey('barangay_id', 'barangays', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('distributed_by_user_id', $users, 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('incident_id', 'incidents', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('relief_distributions', false, $attrs);
        }

        if (! $this->db->tableExists('ai_suggestions')) {
            $this->forge->addField([
                'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'user_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'entity_type'   => ['type' => 'VARCHAR', 'constraint' => 64],
                'entity_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'prompt_type'   => ['type' => 'VARCHAR', 'constraint' => 64],
                'prompt_text'   => ['type' => 'TEXT', 'null' => true],
                'response_text' => ['type' => 'TEXT', 'null' => true],
                'status'        => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'generated'],
                'created_at'    => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addForeignKey('user_id', $users, 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('ai_suggestions', false, $attrs);
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

        if (! $this->db->tableExists('social_media_flags')) {
            $this->forge->addField([
                'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'source'      => ['type' => 'VARCHAR', 'constraint' => 64],
                'content'     => ['type' => 'TEXT'],
                'location'    => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
                'status'      => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'pending_review'],
                'incident_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'flagged_at'  => ['type' => 'DATETIME', 'null' => true],
                'created_at'  => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('social_media_flags', true);
        }
    }

    private function purgeReliefAndAiAuditLogs(): void
    {
        if (! $this->db->tableExists('audit_logs')) {
            return;
        }

        $this->db->table('audit_logs')
            ->groupStart()
            ->whereIn('entity_type', ['relief_item', 'relief_distribution', 'ai_suggestion'])
            ->orLike('action', 'relief_item.', 'after')
            ->orLike('action', 'relief_distribution.', 'after')
            ->orLike('action', 'relief.', 'after')
            ->orLike('action', 'ai.', 'after')
            ->groupEnd()
            ->delete();
    }
}
