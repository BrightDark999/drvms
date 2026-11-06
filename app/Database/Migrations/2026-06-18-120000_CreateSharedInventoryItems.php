<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Centralized inventory master — shared by DRMS (medical/emergency view) and relief system (full catalog).
 */
class CreateSharedInventoryItems extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('inventory_items')) {
            return;
        }

        $attrs = ($this->db->DBDriver === 'MySQLi') ? ['ENGINE' => 'InnoDB'] : [];

        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'sku'                 => ['type' => 'VARCHAR', 'constraint' => 64],
            'name'                => ['type' => 'VARCHAR', 'constraint' => 255],
            'category'            => ['type' => 'VARCHAR', 'constraint' => 64],
            'quantity'            => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'unit'                => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'pc'],
            'low_stock_threshold' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'null' => true],
            'warehouse_location'  => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
            'expires_at'          => ['type' => 'DATE', 'null' => true],
            'notes'               => ['type' => 'TEXT', 'null' => true],
            'is_active'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('sku');
        $this->forge->addKey('category');
        $this->forge->createTable('inventory_items', false, $attrs);
    }

    public function down(): void
    {
        $this->forge->dropTable('inventory_items', true);
    }
}
