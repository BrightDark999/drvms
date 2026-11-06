<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class UserNotificationModel extends Model
{
    protected $table         = 'user_notifications';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = ['user_id', 'title', 'body', 'url', 'is_read'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
}
