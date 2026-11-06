<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Models\IncidentModel;
use App\Services\AlertsService;
use App\Services\DashboardStatsService;
use App\Services\IncidentGeoService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

/**
 * Aggregated KPIs for dashboards / external widgets.
 */
class DashboardApi extends ResourceController
{
    protected $format = 'json';

    public function overview(): ResponseInterface
    {
        return $this->respond([
            'data' => (new DashboardStatsService())->getOverview(),
        ]);
    }

    public function live(): ResponseInterface
    {
        return $this->respond([
            'data' => [
                'stats'  => (new DashboardStatsService())->getOverview(),
                'alerts' => (new AlertsService())->getDashboardAlerts(),
            ],
        ]);
    }

    public function incidentsMap(): ResponseInterface
    {
        helper('drms');
        $features = [];
        $statuses = ['reported', 'verified', 'in_progress'];

        $builder = model(IncidentModel::class)->builder();
        $builder->whereIn('incidents.status', $statuses);
        (new \App\Services\BarangayScopeService())->applyIncidentScope($builder);
        $incidents = $builder->orderBy('incidents.id', 'DESC')->get(100)->getResultArray();
        $geo       = new IncidentGeoService();

        foreach ($incidents as $inc) {
            $resolved = $geo->resolve($inc);
            if ($resolved === null) {
                continue;
            }
            $sev    = (string) ($inc['severity'] ?? 'moderate');
            $type   = (string) ($inc['incident_type'] ?? 'other');
            $marker = drms_incident_map_marker($type, $sev);
            $features[] = [
                'type'       => 'Feature',
                'geometry'   => ['type' => 'Point', 'coordinates' => [$resolved['lng'], $resolved['lat']]],
                'properties' => array_merge([
                    'id'        => (int) ($inc['id'] ?? 0),
                    'reference' => $inc['reference_code'] ?? '',
                    'title'     => $inc['title'] ?? '',
                    'severity'  => $sev,
                    'status'    => $inc['status'] ?? '',
                ], $marker),
            ];
        }

        return $this->respond([
            'data' => ['type' => 'FeatureCollection', 'features' => $features],
        ]);
    }
}
