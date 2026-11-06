<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Rename Shield group slug superadmin → admin (display title: Admin).
 */
class RenameSuperadminGroupToAdmin extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('auth_groups_users')) {
            return;
        }

        $this->db->table('auth_groups_users')
            ->where('group', 'superadmin')
            ->update(['group' => 'admin']);
    }

    public function down(): void
    {
        if (! $this->db->tableExists('auth_groups_users')) {
            return;
        }

        $this->db->table('auth_groups_users')
            ->where('group', 'admin')
            ->update(['group' => 'superadmin']);
    }
}
