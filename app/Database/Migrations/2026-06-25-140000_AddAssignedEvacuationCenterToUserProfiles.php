<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAssignedEvacuationCenterToUserProfiles extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('user_profiles')) {
            return;
        }

        if ($this->db->fieldExists('assigned_evacuation_center_id', 'user_profiles')) {
            return;
        }

        $this->forge->addColumn('user_profiles', [
            'assigned_evacuation_center_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'barangay_id',
            ],
        ]);
    }

    public function down(): void
    {
        if ($this->db->tableExists('user_profiles')
            && $this->db->fieldExists('assigned_evacuation_center_id', 'user_profiles')) {
            $this->forge->dropColumn('user_profiles', 'assigned_evacuation_center_id');
        }
    }
}
