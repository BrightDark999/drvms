<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class EvacFamilyModel extends Model
{
    protected $table         = 'evac_families';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'evacuation_center_id', 'family_head_name', 'members_count',
        'medical_needs', 'contact_phone', 'family_qr_token', 'registered_at', 'checked_in_at', 'checked_out_at',
    ];
    protected $useTimestamps = true;
}
