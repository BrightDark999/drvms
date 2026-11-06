<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class DonorModel extends Model
{
    protected $table         = 'donors';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = ['name', 'org_name', 'email', 'phone'];
    protected $useTimestamps = true;
}
