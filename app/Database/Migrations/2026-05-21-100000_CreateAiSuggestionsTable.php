<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Auth;

class CreateAiSuggestionsTable extends Migration
{
    public function up(): void
    {
        $users = config(Auth::class)->tables['users'];
        $attrs = ($this->db->DBDriver === 'MySQLi') ? ['ENGINE' => 'InnoDB'] : [];

        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'entity_type'   => ['type' => 'VARCHAR', 'constraint' => 64],
            'entity_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'prompt_type'   => ['type' => 'VARCHAR', 'constraint' => 64],
            'prompt_text'   => ['type' => 'TEXT', 'null' => true],
            'response_text' => ['type' => 'TEXT', 'null' => true],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'generated'],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('user_id', $users, 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('ai_suggestions', false, $attrs);
    }

    public function down(): void
    {
        $this->forge->dropTable('ai_suggestions', true);
    }
}
