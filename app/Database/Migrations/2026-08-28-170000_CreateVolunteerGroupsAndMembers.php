<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVolunteerGroupsAndMembers extends Migration
{
    public function up(): void
    {
        // 1. Create volunteer_groups table
        if (! $this->db->tableExists('volunteer_groups')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'user_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'organization_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                ],
                'head_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 180,
                ],
                'head_contact' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                ],
                'head_email' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 180,
                    'null'       => true,
                ],
                'barangay_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'address' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'description' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                    'default'    => 'pending',
                ],
                'approval_notes' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'deleted_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey('user_id');
            $this->forge->addKey('status');
            $this->forge->createTable('volunteer_groups', true);
        }

        // 2. Create volunteer_group_members table
        if (! $this->db->tableExists('volunteer_group_members')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'group_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'full_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 180,
                ],
                'contact_number' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => true,
                ],
                'email' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 180,
                    'null'       => true,
                ],
                'role_or_skills' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'address' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                    'default'    => 'pending',
                ],
                'change_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                    'default'    => 'none',
                ],
                'pending_data' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'deleted_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey('group_id');
            $this->forge->addKey('status');
            $this->forge->createTable('volunteer_group_members', true);
        }

        // 3. Add preferred_hours and volunteer_type to volunteers table if not existing
        if ($this->db->tableExists('volunteers')) {
            if (! $this->db->fieldExists('volunteer_type', 'volunteers')) {
                $this->forge->addColumn('volunteers', [
                    'volunteer_type' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 32,
                        'default'    => 'individual',
                        'after'      => 'availability_status',
                    ],
                ]);
            }
            if (! $this->db->fieldExists('preferred_hours', 'volunteers')) {
                $this->forge->addColumn('volunteers', [
                    'preferred_hours' => [
                        'type'       => 'TINYINT',
                        'constraint' => 3,
                        'unsigned'   => true,
                        'default'    => 8,
                        'after'      => 'volunteer_type',
                    ],
                ]);
            }
            if (! $this->db->fieldExists('group_id', 'volunteers')) {
                $this->forge->addColumn('volunteers', [
                    'group_id' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'unsigned'   => true,
                        'null'       => true,
                        'after'      => 'preferred_hours',
                    ],
                ]);
            }
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('volunteer_group_members', true);
        $this->forge->dropTable('volunteer_groups', true);

        if ($this->db->tableExists('volunteers')) {
            if ($this->db->fieldExists('volunteer_type', 'volunteers')) {
                $this->forge->dropColumn('volunteers', 'volunteer_type');
            }
            if ($this->db->fieldExists('preferred_hours', 'volunteers')) {
                $this->forge->dropColumn('volunteers', 'preferred_hours');
            }
            if ($this->db->fieldExists('group_id', 'volunteers')) {
                $this->forge->dropColumn('volunteers', 'group_id');
            }
        }
    }
}
