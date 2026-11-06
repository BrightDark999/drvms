<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ExtendUserProfilesAndVerification extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('user_profiles')) {
            return;
        }

        $columns = [
            'full_name'               => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'user_id'],
            'phone'                   => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'full_name'],
            'address'                 => ['type' => 'TEXT', 'null' => true, 'after' => 'phone'],
            'profile_photo'           => ['type' => 'VARCHAR', 'constraint' => 512, 'null' => true, 'after' => 'address'],
            'id_document_path'        => ['type' => 'VARCHAR', 'constraint' => 512, 'null' => true, 'after' => 'profile_photo'],
            'id_verification_status'  => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'none', 'after' => 'id_document_path'],
        ];

        foreach ($columns as $name => $def) {
            if (! $this->db->fieldExists($name, 'user_profiles')) {
                $this->forge->addColumn('user_profiles', [$name => $def]);
            }
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('user_profiles')) {
            return;
        }

        foreach (['id_verification_status', 'id_document_path', 'profile_photo', 'address', 'phone', 'full_name'] as $col) {
            if ($this->db->fieldExists($col, 'user_profiles')) {
                $this->forge->dropColumn('user_profiles', $col);
            }
        }
    }
}
