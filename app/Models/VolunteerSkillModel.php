<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class VolunteerSkillModel extends Model
{
    protected $table         = 'volunteer_skills';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = ['volunteer_id', 'skill_name', 'certification'];
    protected $useTimestamps = true;
}
