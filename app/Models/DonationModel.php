<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class DonationModel extends Model
{
    protected $table         = 'donations';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'donor_id',
        'donation_type',
        'description',
        'amount_peso',
        'received_at',
        'recorded_by_user_id',
    ];
    protected $useTimestamps = true;
}
