<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class InventoryItemModel extends Model
{
    protected $table            = 'inventory_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'sku',
        'name',
        'category',
        'quantity',
        'unit',
        'low_stock_threshold',
        'warehouse_location',
        'expires_at',
        'notes',
        'is_active',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
