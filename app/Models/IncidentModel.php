<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class IncidentModel extends Model
{
    protected $table            = 'incidents';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'reference_code',
        'title',
        'description',
        'incident_type',
        'severity',
        'priority_tag',
        'needs_rescue',
        'needs_medical',
        'needs_drivers',
        'required_skills_json',
        'status',
        'latitude',
        'longitude',
        'barangay_id',
        'reported_by_user_id',
        'verified_by_user_id',
        'verified_at',
    ];
    protected bool $allowEmptyInserts = false;
    protected $useTimestamps      = true;
    protected $dateFormat         = 'datetime';
    protected $createdField       = 'created_at';
    protected $updatedField       = 'updated_at';
    protected $deletedField       = 'deleted_at';

    /** @var list<string> */
    protected $validationRules = [
        'title'    => 'required|string|max_length[255]',
        'severity' => 'required|string|max_length[32]',
        'status'   => 'required|string|max_length[32]',
    ];
}
