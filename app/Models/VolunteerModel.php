<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class VolunteerModel extends Model
{
    protected $table          = 'volunteers';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields  = true;
    protected $allowedFields  = [
        'user_id',
        'full_name',
        'email',
        'phone',
        'barangay_id',
        'address',
        'availability_status',
        'experience_years',
        'fatigue_level',
        'id_verification_path',
        'id_verification_status',
        'notes',
    ];
    protected $useTimestamps = true;
}
