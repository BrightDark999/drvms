<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class VolunteerGroupMemberModel extends Model
{
    protected $table          = 'volunteer_group_members';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields  = true;
    protected $allowedFields  = [
        'group_id',
        'full_name',
        'contact_number',
        'email',
        'role_or_skills',
        'address',
        'status',
        'change_type',
        'pending_data',
    ];
    protected $useTimestamps  = true;
}
