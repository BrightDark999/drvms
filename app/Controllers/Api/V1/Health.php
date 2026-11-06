<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Simple API health check – confirms REST routing works.
 */
class Health extends BaseController
{
    public function index(): ResponseInterface
    {
        $drms = drms_config();

        return $this->response->setJSON([
            'status'          => 'ok',
            'app'             => $drms->appName,
            'app_full_name'   => $drms->appFullName,
            'relief_api'      => drms_erp_relief_connected() ? 'connected' : 'not_configured',
            'version'         => $drms->appVersion,
            'time'            => date('c'),
        ]);
    }
}
