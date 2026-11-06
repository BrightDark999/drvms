<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class VolunteerSkillRequestModel extends Model
{
    protected $table         = 'volunteer_skill_requests';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'volunteer_id',
        'user_id',
        'requested_skills_json',
        'previous_skills_json',
        'status',
        'reviewer_id',
        'reviewer_notes',
        'reviewed_at',
    ];
    protected $useTimestamps = true;
}
