<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Consolidate roles: lgu → mdrrmo; remove ngo_coordinator and barangay_official.
 */
class ConsolidateOperationalRoles extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('auth_groups_users')) {
            return;
        }

        $this->db->table('auth_groups_users')
            ->whereIn('group', ['lgu', 'ngo_coordinator', 'barangay_official'])
            ->update(['group' => 'mdrrmo']);
    }

    public function down(): void
    {
        // Cannot reliably restore former group assignments.
    }
}
