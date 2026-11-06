<?php

declare(strict_types=1);

namespace App\Validation;

use Config\DrmsFeatures;

class InventoryRules
{
    /**
     * @return array<string, array<string, mixed>|string|list<string>>
     */
    public static function save(int $itemId = 0): array
    {
        $cats = implode(',', config(DrmsFeatures::class)->inventoryDrmsCategories);
        $skuRule = 'required|min_length[2]|max_length[64]|alpha_dash';
        if ($itemId > 0) {
            $skuRule .= "|is_unique[inventory_items.sku,id,{$itemId}]";
        } else {
            $skuRule .= '|is_unique[inventory_items.sku]';
        }

        return [
            'sku' => [
                'label'  => 'SKU',
                'rules'  => $skuRule,
                'errors' => ['is_unique' => 'This SKU is already in use.'],
            ],
            'name' => 'required|min_length[2]|max_length[255]',
            'category' => "required|in_list[{$cats}]",
            'quantity' => 'permit_empty|decimal|greater_than_equal_to[0]',
            'unit' => 'permit_empty|max_length[32]',
            'low_stock_threshold' => 'permit_empty|decimal|greater_than_equal_to[0]',
            'warehouse_location' => 'permit_empty|max_length[160]',
            'expires_at' => 'permit_empty|valid_date[Y-m-d]',
            'notes' => 'permit_empty|max_length[2000]',
        ];
    }

    /**
     * @return array<string, array<string, mixed>|string|list<string>>
     */
    public static function adjustStock(): array
    {
        return [
            'quantity_delta' => 'required|decimal|greater_than[0]',
            'direction'      => 'required|in_list[add,remove]',
            'reason'         => 'required|min_length[3]|max_length[500]',
        ];
    }
}
