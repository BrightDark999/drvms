<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Contact person + family intake guidance for evacuation centers.
 */
class AddEvacuationCenterIntakeFields extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('evacuation_centers')) {
            return;
        }

        $fields = [];

        if (! $this->db->fieldExists('contact_person', 'evacuation_centers')) {
            $fields['contact_person'] = [
                'type'       => 'VARCHAR',
                'constraint' => 160,
                'null'       => true,
                'after'      => 'contact_phone',
            ];
        }

        if (! $this->db->fieldExists('intake_procedures', 'evacuation_centers')) {
            $fields['intake_procedures'] = [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'contact_person',
            ];
        }

        if (! $this->db->fieldExists('required_items', 'evacuation_centers')) {
            $fields['required_items'] = [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'intake_procedures',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('evacuation_centers', $fields);
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('evacuation_centers')) {
            return;
        }

        foreach (['required_items', 'intake_procedures', 'contact_person'] as $col) {
            if ($this->db->fieldExists($col, 'evacuation_centers')) {
                $this->forge->dropColumn('evacuation_centers', $col);
            }
        }
    }
}
