<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class HomeCarouselSlideModel extends Model
{
    protected $table         = 'homepage_carousel_slides';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'badge_text',
        'title',
        'description',
        'image_path',
        'background_style',
        'button_primary_label',
        'button_primary_url',
        'button_secondary_label',
        'button_secondary_url',
        'sort_order',
        'is_active',
    ];
    protected $useTimestamps = true;
}
