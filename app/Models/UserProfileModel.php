<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class UserProfileModel extends Model
{
    protected $table         = 'user_profiles';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id', 'barangay_id', 'assigned_evacuation_center_id', 'full_name', 'phone', 'address',
        'profile_photo', 'id_document_path', 'id_verification_status',
    ];
    protected $useTimestamps = true;
}
