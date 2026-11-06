<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Services\ReportsAnalyticsService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class ReportsApi extends ResourceController
{
    protected $format = 'json';

    public function analytics(): ResponseInterface
    {
        return $this->respond([
            'data' => (new ReportsAnalyticsService())->getBundle(),
        ]);
    }

    public function dashboardChart(): ResponseInterface
    {
        $trend = (new ReportsAnalyticsService())->incidentTrend7d();
        $total = array_sum($trend['values']);

        return $this->respond([
            'status' => 'ok',
            'labels' => $trend['labels'],
            'values' => $trend['values'],
            'data'   => $trend['values'],
            'total'  => $total,
        ]);
    }
}
