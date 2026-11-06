<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Thesis feature columns: incident types, priority tags, volunteer profile, relief expiry, evac families.
 */
class AddThesisFeatureColumns extends Migration
{
    public function up(): void
    {
        $fields = [
            'incident_type'        => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'other', 'after' => 'description'],
            'priority_tag'         => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'MEDIUM', 'after' => 'severity'],
            'needs_rescue'         => ['type' => 'INT', 'constraint' => 3, 'unsigned' => true, 'default' => 0, 'after' => 'priority_tag'],
            'needs_medical'        => ['type' => 'INT', 'constraint' => 3, 'unsigned' => true, 'default' => 0, 'after' => 'needs_rescue'],
            'needs_drivers'        => ['type' => 'INT', 'constraint' => 3, 'unsigned' => true, 'default' => 0, 'after' => 'needs_medical'],
            'required_skills_json' => ['type' => 'TEXT', 'null' => true, 'after' => 'needs_drivers'],
        ];
        if ($this->db->tableExists('incidents') && ! $this->db->fieldExists('incident_type', 'incidents')) {
            $this->forge->addColumn('incidents', $fields);
        }

        $vFields = [
            'experience_years'       => ['type' => 'INT', 'constraint' => 3, 'unsigned' => true, 'default' => 0, 'after' => 'availability_status'],
            'id_verification_path'   => ['type' => 'VARCHAR', 'constraint' => 512, 'null' => true, 'after' => 'experience_years'],
        ];
        if ($this->db->tableExists('volunteers') && ! $this->db->fieldExists('experience_years', 'volunteers')) {
            $this->forge->addColumn('volunteers', $vFields);
        }

        $rFields = [
            'expires_at'          => ['type' => 'DATE', 'null' => true, 'after' => 'low_stock_threshold'],
            'warehouse_location'  => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true, 'after' => 'expires_at'],
        ];
        if ($this->db->tableExists('relief_items') && ! $this->db->fieldExists('expires_at', 'relief_items')) {
            $this->forge->addColumn('relief_items', $rFields);
        }

        $dFields = [
            'claim_status' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'pending', 'after' => 'claim_qr_token'],
        ];
        if ($this->db->tableExists('relief_distributions') && ! $this->db->fieldExists('claim_status', 'relief_distributions')) {
            $this->forge->addColumn('relief_distributions', $dFields);
        }

        $eFields = [
            'families_registered'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0, 'after' => 'current_occupancy'],
            'medical_needs_count'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0, 'after' => 'families_registered'],
        ];
        if ($this->db->tableExists('evacuation_centers') && ! $this->db->fieldExists('families_registered', 'evacuation_centers')) {
            $this->forge->addColumn('evacuation_centers', $eFields);
        }
    }

    public function down(): void
    {
        foreach (['incidents' => ['incident_type', 'priority_tag', 'needs_rescue', 'needs_medical', 'needs_drivers', 'required_skills_json'],
            'volunteers' => ['experience_years', 'id_verification_path'],
            'relief_items' => ['expires_at', 'warehouse_location'],
            'relief_distributions' => ['claim_status'],
            'evacuation_centers' => ['families_registered', 'medical_needs_count']] as $table => $cols) {
            if ($this->db->tableExists($table)) {
                foreach ($cols as $col) {
                    if ($this->db->fieldExists($col, $table)) {
                        $this->forge->dropColumn($table, $col);
                    }
                }
            }
        }
    }
}
