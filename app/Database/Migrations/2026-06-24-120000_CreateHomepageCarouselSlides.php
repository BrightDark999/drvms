<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHomepageCarouselSlides extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('homepage_carousel_slides')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'badge_text' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255],
            'description' => ['type' => 'TEXT', 'null' => true],
            'image_path' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'background_style' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'gradient-1'],
            'button_primary_label' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'button_primary_url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'button_secondary_label' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'button_secondary_url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'sort_order' => ['type' => 'INT', 'default' => 0],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['is_active', 'sort_order']);
        $this->forge->createTable('homepage_carousel_slides', true);
    }

    public function down(): void
    {
        if ($this->db->tableExists('homepage_carousel_slides')) {
            $this->forge->dropTable('homepage_carousel_slides', true);
        }
    }
}
