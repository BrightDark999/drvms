<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class NotificationQueueModel extends Model
{
    protected $table         = 'notification_queue';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'channel',
        'recipient',
        'subject',
        'body',
        'status',
        'meta_json',
        'sent_at',
    ];
    protected $useTimestamps = false;
}
