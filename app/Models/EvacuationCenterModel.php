<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class EvacuationCenterModel extends Model
{
    protected $table         = 'evacuation_centers';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'name',
        'barangay_id',
        'address',
        'capacity',
        'current_occupancy',
        'families_registered',
        'medical_needs_count',
        'status',
        'contact_phone',
        'contact_person',
        'intake_procedures',
        'required_items',
    ];
    protected $useTimestamps = true;
}
