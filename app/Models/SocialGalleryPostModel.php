<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class SocialGalleryPostModel extends Model
{
    protected $table         = 'social_gallery_posts';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'title',
        'caption',
        'image_path',
        'external_url',
        'platform',
        'published_at',
        'sort_order',
        'is_active',
    ];
    protected $useTimestamps = true;
}
