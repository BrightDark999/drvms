<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEvacFamilyCheckedOutAt extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('evac_families') && ! $this->db->fieldExists('checked_out_at', 'evac_families')) {
            $this->forge->addColumn('evac_families', [
                'checked_out_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'checked_in_at'],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('evac_families') && $this->db->fieldExists('checked_out_at', 'evac_families')) {
            $this->forge->dropColumn('evac_families', 'checked_out_at');
        }
    }
}
