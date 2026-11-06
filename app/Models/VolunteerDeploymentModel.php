<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class VolunteerDeploymentModel extends Model
{
    protected $table         = 'volunteer_deployments';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'volunteer_id',
        'incident_id',
        'status',
        'deployed_at',
        'returned_at',
        'notes',
    ];
    protected $useTimestamps = true;
}
