<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVolunteerSkillRequests extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('volunteer_skill_requests')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'volunteer_id' => ['type' => 'INT', 'unsigned' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'requested_skills_json' => ['type' => 'TEXT', 'null' => true],
            'previous_skills_json' => ['type' => 'TEXT', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'pending'],
            'reviewer_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'reviewer_notes' => ['type' => 'TEXT', 'null' => true],
            'reviewed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('volunteer_id');
        $this->forge->addKey('status');
        $this->forge->createTable('volunteer_skill_requests', true);
    }

    public function down(): void
    {
        if ($this->db->tableExists('volunteer_skill_requests')) {
            $this->forge->dropTable('volunteer_skill_requests', true);
        }
    }
}
