<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class BarangayModel extends Model
{
    protected $table         = 'barangays';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = ['code', 'name', 'latitude', 'longitude'];
    protected $useTimestamps = true;
}
