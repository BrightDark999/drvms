<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class IncidentMediaModel extends Model
{
    protected $table         = 'incident_media';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = ['incident_id', 'path', 'original_name', 'mime'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
}
