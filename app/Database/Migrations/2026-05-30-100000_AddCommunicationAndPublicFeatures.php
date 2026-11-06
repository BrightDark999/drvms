<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCommunicationAndPublicFeatures extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('volunteers') && ! $this->db->fieldExists('fatigue_level', 'volunteers')) {
            $this->forge->addColumn('volunteers', [
                'fatigue_level' => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0, 'after' => 'experience_years'],
            ]);
        }

        if ($this->db->tableExists('relief_distributions') && ! $this->db->fieldExists('scheduled_claim_at', 'relief_distributions')) {
            $this->forge->addColumn('relief_distributions', [
                'scheduled_claim_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'claim_status'],
                'claimed_at'         => ['type' => 'DATETIME', 'null' => true, 'after' => 'scheduled_claim_at'],
            ]);
        }

        if (! $this->db->tableExists('chat_messages')) {
            $this->forge->addField([
                'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'sender_user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'recipient_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'body'              => ['type' => 'TEXT'],
                'is_read'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'created_at'        => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('recipient_user_id');
            $this->forge->createTable('chat_messages', true);
        }

        if (! $this->db->tableExists('user_notifications')) {
            $this->forge->addField([
                'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'title'      => ['type' => 'VARCHAR', 'constraint' => 160],
                'body'       => ['type' => 'TEXT', 'null' => true],
                'url'        => ['type' => 'VARCHAR', 'constraint' => 512, 'null' => true],
                'is_read'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('user_id');
            $this->forge->createTable('user_notifications', true);
        }

        if (! $this->db->tableExists('evac_families')) {
            $this->forge->addField([
                'id'                   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'evacuation_center_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'family_head_name'     => ['type' => 'VARCHAR', 'constraint' => 160],
                'members_count'        => ['type' => 'INT', 'constraint' => 5, 'unsigned' => true, 'default' => 1],
                'medical_needs'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'contact_phone'        => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
                'family_qr_token'      => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'registered_at'        => ['type' => 'DATETIME', 'null' => true],
                'created_at'           => ['type' => 'DATETIME', 'null' => true],
                'updated_at'           => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('evacuation_center_id');
            $this->forge->createTable('evac_families', true);
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

            $now = date('Y-m-d H:i:s');
            $this->db->table('social_media_flags')->insertBatch([
                [
                    'source'     => 'Facebook',
                    'content'    => 'Flooding in Sitio Mabuhay — water knee-deep, need rescue!',
                    'location'   => 'Barangay San Jose',
                    'status'     => 'pending_review',
                    'flagged_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                    'created_at' => $now,
                ],
                [
                    'source'     => 'X / Twitter',
                    'content'    => '#BarasRizal landslide reported near highway, road blocked',
                    'location'   => 'Baras, Rizal',
                    'status'     => 'pending_review',
                    'flagged_at' => date('Y-m-d H:i:s', strtotime('-5 hours')),
                    'created_at' => $now,
                ],
            ]);
        }
    }

    public function down(): void
    {
        foreach (['social_media_flags', 'evac_families', 'user_notifications', 'chat_messages'] as $table) {
            if ($this->db->tableExists($table)) {
                $this->forge->dropTable($table, true);
            }
        }
        if ($this->db->tableExists('volunteers') && $this->db->fieldExists('fatigue_level', 'volunteers')) {
            $this->forge->dropColumn('volunteers', 'fatigue_level');
        }
        if ($this->db->tableExists('relief_distributions')) {
            foreach (['scheduled_claim_at', 'claimed_at'] as $col) {
                if ($this->db->fieldExists($col, 'relief_distributions')) {
                    $this->forge->dropColumn('relief_distributions', $col);
                }
            }
        }
    }
}
