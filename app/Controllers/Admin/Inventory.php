<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Inventory extends BaseController
{
    public function index(): string
    {
        helper('drms');
        $features = config('DrmsFeatures');
        $service  = new \App\Services\InventoryService();
        $summary  = $service->summaryForScope('drms');
        $connected = drms_erp_relief_connected();

        return view('admin/inventory/index', [
            'title'            => 'Emergency Supplies Inventory',
            'drms'             => drms_config(),
            'categories'       => $features->inventoryCategories,
            'drmsCategories'   => $features->inventoryDrmsCategories,
            'summary'          => $summary,
            'reliefConnected'  => $connected,
            'usingSharedTable' => ! $connected,
        ]);
    }
}
