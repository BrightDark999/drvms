<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSocialGalleryPosts extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('social_gallery_posts')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'caption' => ['type' => 'TEXT', 'null' => true],
            'image_path' => ['type' => 'VARCHAR', 'constraint' => 500],
            'external_url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'platform' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'facebook'],
            'published_at' => ['type' => 'DATETIME', 'null' => true],
            'sort_order' => ['type' => 'INT', 'default' => 0],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['is_active', 'sort_order']);
        $this->forge->createTable('social_gallery_posts', true);
    }

    public function down(): void
    {
        if ($this->db->tableExists('social_gallery_posts')) {
            $this->forge->dropTable('social_gallery_posts', true);
        }
    }
}
