<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Models\InventoryItemModel;
use CodeIgniter\Database\Seeder;

/**
 * Sample rows for shared inventory_items (medical + relief categories).
 */
class InventoryItemsSeeder extends Seeder
{
    public function run(): void
    {
        $model = model(InventoryItemModel::class);
        if ($model->countAllResults() > 0) {
            return;
        }

        $rows = [
            ['sku' => 'MED-IFAK-01', 'name' => 'Individual first aid kit', 'category' => 'first_aid', 'quantity' => 120, 'unit' => 'kits', 'low_stock_threshold' => 30, 'warehouse_location' => 'MDRRMO supply room', 'expires_at' => '2027-06-30'],
            ['sku' => 'MED-BAND-L', 'name' => 'Elastic bandage (large)', 'category' => 'medical', 'quantity' => 85, 'unit' => 'rolls', 'low_stock_threshold' => 25, 'warehouse_location' => 'MDRRMO supply room', 'expires_at' => '2026-12-31'],
            ['sku' => 'MED-O2-CYL', 'name' => 'Portable oxygen cylinder', 'category' => 'emergency', 'quantity' => 6, 'unit' => 'units', 'low_stock_threshold' => 2, 'warehouse_location' => 'Rescue staging', 'expires_at' => null],
            ['sku' => 'MED-SAL-500', 'name' => 'Saline solution 500ml', 'category' => 'medical', 'quantity' => 18, 'unit' => 'bottles', 'low_stock_threshold' => 40, 'warehouse_location' => 'MDRRMO supply room', 'expires_at' => '2026-09-15'],
            ['sku' => 'EMR-STRETCH', 'name' => 'Collapsible stretcher', 'category' => 'emergency', 'quantity' => 4, 'unit' => 'units', 'low_stock_threshold' => 2, 'warehouse_location' => 'Rescue staging', 'expires_at' => null],
            ['sku' => 'EMR-RADIO', 'name' => 'Handheld VHF radio', 'category' => 'emergency', 'quantity' => 10, 'unit' => 'units', 'low_stock_threshold' => 3, 'warehouse_location' => 'MDRRMO comms', 'expires_at' => null],
            ['sku' => 'FOOD-RICE-5', 'name' => 'Rice (5kg sack)', 'category' => 'food', 'quantity' => 450, 'unit' => 'sacks', 'low_stock_threshold' => 50, 'warehouse_location' => 'Municipal warehouse', 'expires_at' => '2026-12-31'],
            ['sku' => 'FOOD-CAN-M', 'name' => 'Canned goods (assorted)', 'category' => 'food', 'quantity' => 320, 'unit' => 'cans', 'low_stock_threshold' => 80, 'warehouse_location' => 'Municipal warehouse', 'expires_at' => '2027-03-01'],
            ['sku' => 'HYG-KIT-F', 'name' => 'Family hygiene kit', 'category' => 'hygiene', 'quantity' => 200, 'unit' => 'kits', 'low_stock_threshold' => 40, 'warehouse_location' => 'Municipal warehouse', 'expires_at' => null],
            ['sku' => 'WTR-JUG-5', 'name' => 'Drinking water jug (5L)', 'category' => 'water', 'quantity' => 90, 'unit' => 'jugs', 'low_stock_threshold' => 30, 'warehouse_location' => 'Municipal warehouse', 'expires_at' => '2026-08-01'],
        ];

        foreach ($rows as $row) {
            $model->insert($row);
        }
    }
}
