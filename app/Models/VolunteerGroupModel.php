<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class VolunteerGroupModel extends Model
{
    protected $table          = 'volunteer_groups';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields  = true;
    protected $allowedFields  = [
        'user_id',
        'organization_name',
        'head_name',
        'head_contact',
        'head_email',
        'barangay_id',
        'address',
        'description',
        'status',
        'approval_notes',
    ];
    protected $useTimestamps  = true;
}
