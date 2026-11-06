<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Auth;

/**
 * Core DRMS operational tables. Run after Shield auth migration.
 * Foreign keys reference Shield `users` table.
 */
class CreateDrmsOperationalTables extends Migration
{
    private function attrs(): array
    {
        return ($this->db->DBDriver === 'MySQLi') ? ['ENGINE' => 'InnoDB'] : [];
    }

    public function up(): void
    {
        $users = config(Auth::class)->tables['users'];

        // barangays
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'code'       => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 128],
            'latitude'   => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'longitude'  => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('barangays', false, $this->attrs());

        // incidents
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'reference_code'      => ['type' => 'VARCHAR', 'constraint' => 32],
            'title'               => ['type' => 'VARCHAR', 'constraint' => 255],
            'description'         => ['type' => 'TEXT', 'null' => true],
            'severity'            => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'moderate'],
            'status'              => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'reported'],
            'latitude'            => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'longitude'           => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'barangay_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'reported_by_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'verified_by_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'verified_at'         => ['type' => 'DATETIME', 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('reference_code');
        $this->forge->addForeignKey('barangay_id', 'barangays', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('reported_by_user_id', $users, 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('verified_by_user_id', $users, 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('incidents', false, $this->attrs());

        // incident_media
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'incident_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'path'          => ['type' => 'VARCHAR', 'constraint' => 512],
            'original_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'mime'          => ['type' => 'VARCHAR', 'constraint' => 128],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('incident_id', 'incidents', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('incident_media', false, $this->attrs());

        // volunteers
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'full_name'            => ['type' => 'VARCHAR', 'constraint' => 160],
            'email'                => ['type' => 'VARCHAR', 'constraint' => 191],
            'phone'                => ['type' => 'VARCHAR', 'constraint' => 48],
            'barangay_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'address'              => ['type' => 'TEXT', 'null' => true],
            'availability_status'  => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'available'],
            'notes'                => ['type' => 'TEXT', 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('user_id', $users, 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('barangay_id', 'barangays', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('volunteers', false, $this->attrs());

        // volunteer_skills
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'volunteer_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'skill_name'    => ['type' => 'VARCHAR', 'constraint' => 128],
            'certification' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('volunteer_id', 'volunteers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('volunteer_skills', false, $this->attrs());

        // volunteer_deployments
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'volunteer_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'incident_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'deployed'],
            'deployed_at'   => ['type' => 'DATETIME', 'null' => true],
            'returned_at'   => ['type' => 'DATETIME', 'null' => true],
            'notes'         => ['type' => 'TEXT', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('volunteer_id', 'volunteers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('incident_id', 'incidents', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('volunteer_deployments', false, $this->attrs());

        // relief_items
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'sku'                 => ['type' => 'VARCHAR', 'constraint' => 64],
            'name'                => ['type' => 'VARCHAR', 'constraint' => 255],
            'category'            => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'quantity'            => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'unit'                => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'pc'],
            'low_stock_threshold' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('sku');
        $this->forge->createTable('relief_items', false, $this->attrs());

        // relief_distributions
        $this->forge->addField([
            'id'                     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'relief_item_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'quantity'               => ['type' => 'DECIMAL', 'constraint' => '14,2'],
            'beneficiary_name'       => ['type' => 'VARCHAR', 'constraint' => 160],
            'beneficiary_contact'    => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'barangay_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'claim_qr_token'         => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
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
        $this->forge->createTable('relief_distributions', false, $this->attrs());

        // evacuation_centers
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'              => ['type' => 'VARCHAR', 'constraint' => 255],
            'barangay_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'address'           => ['type' => 'TEXT', 'null' => true],
            'capacity'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'current_occupancy' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'status'            => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'open'],
            'contact_phone'     => ['type' => 'VARCHAR', 'constraint' => 48, 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('barangay_id', 'barangays', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('evacuation_centers', false, $this->attrs());

        // donors
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 160],
            'org_name'   => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'phone'      => ['type' => 'VARCHAR', 'constraint' => 48, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('donors', false, $this->attrs());

        // donations
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'donor_id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'donation_type'        => ['type' => 'VARCHAR', 'constraint' => 32],
            'description'          => ['type' => 'TEXT', 'null' => true],
            'amount_peso'          => ['type' => 'DECIMAL', 'constraint' => '14,2', 'null' => true],
            'received_at'          => ['type' => 'DATETIME', 'null' => true],
            'recorded_by_user_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('donor_id', 'donors', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('recorded_by_user_id', $users, 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('donations', false, $this->attrs());

        // notification_queue
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'channel'    => ['type' => 'VARCHAR', 'constraint' => 32],
            'recipient'  => ['type' => 'VARCHAR', 'constraint' => 255],
            'subject'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'body'       => ['type' => 'TEXT', 'null' => true],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'pending'],
            'meta_json'  => ['type' => 'TEXT', 'null' => true],
            'sent_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('notification_queue', false, $this->attrs());

        // audit_logs
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'action'      => ['type' => 'VARCHAR', 'constraint' => 128],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 64],
            'entity_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'meta_json'   => ['type' => 'TEXT', 'null' => true],
            'ip_address'  => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('user_id', $users, 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('audit_logs', false, $this->attrs());
    }

    public function down(): void
    {
        $tables = [
            'audit_logs',
            'notification_queue',
            'donations',
            'donors',
            'evacuation_centers',
            'relief_distributions',
            'relief_items',
            'volunteer_deployments',
            'volunteer_skills',
            'volunteers',
            'incident_media',
            'incidents',
            'barangays',
        ];

        foreach ($tables as $t) {
            $this->forge->dropTable($t, true);
        }
    }
}
