<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\IncidentModel;
use App\Services\AlertsService;
use App\Services\DashboardStatsService;
use App\Services\IncidentGeoService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Operations dashboard — KPI tiles backed by operational tables when DB is migrated.
 */
class Dashboard extends BaseController
{
    public function index(): string
    {
        $drms   = drms_config();
        $stats  = (new DashboardStatsService())->getOverview();
        $alerts = (new AlertsService())->getDashboardAlerts();
        $portal = drms_is_limited_portal_user();

        if ($portal && auth()->user()->inGroup('volunteer')) {
            $userId  = (int) auth()->id();
            $volSvc  = new \App\Services\VolunteerDashboardService();
            $chart   = $volSvc->deploymentsChart7d($userId);

            return view('admin/dashboard/volunteer', [
                'title'              => 'Volunteer Dashboard',
                'drms'               => $drms,
                'stats'              => $volSvc->getOverviewForUser($userId),
                'alerts'             => $volSvc->getAlertsForUser($userId),
                'assignments'        => $volSvc->listAssignmentsForUser($userId),
                'chartLabels'        => $chart['labels'],
                'chartValues'        => $chart['values'],
                'incidentsMapUrl'    => base_url('admin/dashboard/incidents-map'),
                'incidentsHeatmapUrl'=> base_url('admin/dashboard/incidents-heatmap'),
                'portalLiveUrl'      => base_url('admin/dashboard/portal-live'),
                'mapConfig'          => drms_leaflet_map_config(),
                'mapMarkerLegend'    => drms_incident_map_marker_legend(),
                'barangays'          => drms_barangay_list(),
                'incidentTypes'      => config('DrmsFeatures')->incidentTypes,
            ]);
        }

        if (auth()->user()->inGroup('evac_manager') && ! drms_user_can('incidents.*', 'admin.*')) {
            $userId  = (int) auth()->id();
            $evacSvc = new \App\Services\EvacManagerDashboardService();

            return view('admin/dashboard/evac_manager', [
                'title'          => 'Evacuation Dashboard',
                'drms'           => $drms,
                'stats'          => $evacSvc->getOverviewForUser($userId),
                'assignedCenter' => $evacSvc->getAssignedCenter($userId),
                'centers'        => $evacSvc->listCentersForUser($userId),
                'families'       => $evacSvc->listFamiliesForUser($userId),
            ]);
        }

        $view = $portal ? 'admin/dashboard/portal' : 'admin/dashboard/index';

        return view($view, [
            'title'              => $portal ? 'My Portal' : 'Dashboard',
            'drms'               => $drms,
            'stats'              => $stats,
            'alerts'             => $alerts,
            'incidentsDataUrl'    => base_url('admin/incidents/data?no_actions=1'),
            'incidentsChartUrl'   => base_url('admin/dashboard/incidents-chart'),
            'incidentsMapUrl'     => base_url('admin/dashboard/incidents-map'),
            'incidentsHeatmapUrl' => base_url('admin/dashboard/incidents-heatmap'),
            'liveStatsUrl'        => base_url('admin/dashboard/live'),
            'mapConfig'           => drms_leaflet_map_config(),
            'mapMarkerLegend'     => drms_incident_map_marker_legend(),
            'barangays'           => drms_barangay_list(),
            'incidentTypes'       => config('DrmsFeatures')->incidentTypes,
        ]);
    }

    /**
     * GeoJSON-like pins for active incidents (Leaflet map).
     */
    public function incidentsMap(): ResponseInterface
    {
        $features = [];
        $statuses = ['reported', 'verified', 'in_progress'];

        try {
            $builder = model(IncidentModel::class)->builder();
            $builder->select('incidents.*, barangays.name AS barangay_name');
            $builder->join('barangays', 'barangays.id = incidents.barangay_id', 'left');
            $builder->whereIn('incidents.status', $statuses);
            (new \App\Services\BarangayScopeService())->applyIncidentScope($builder);
            $incidents = $builder->orderBy('incidents.id', 'DESC')->get(150)->getResultArray();
            $geo       = new IncidentGeoService();

            foreach ($incidents as $inc) {
                $resolved = $geo->resolve($inc);
                if ($resolved === null) {
                    continue;
                }
                $lat = $resolved['lat'];
                $lng = $resolved['lng'];

                $sev      = (string) ($inc['severity'] ?? 'moderate');
                $type     = (string) ($inc['incident_type'] ?? 'other');
                $marker   = drms_incident_map_marker($type, $sev);
                $features[] = [
                    'type'       => 'Feature',
                    'geometry'   => ['type' => 'Point', 'coordinates' => [$lng, $lat]],
                    'properties' => array_merge([
                        'id'            => (int) ($inc['id'] ?? 0),
                        'reference'     => $inc['reference_code'] ?? '',
                        'title'         => $inc['title'] ?? '',
                        'description'   => $inc['description'] ?? '',
                        'severity'      => $sev,
                        'status'        => $inc['status'] ?? '',
                        'barangay_id'   => (int) ($inc['barangay_id'] ?? 0),
                        'barangay_name' => $inc['barangay_name'] ?? 'Baras (General)',
                        'created_at'    => $inc['created_at'] ?? '',
                        'url'           => base_url('admin/incidents'),
                    ], $marker),
                ];
            }
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard::incidentsMap — ' . $e->getMessage());
        }

        return $this->response->setJSON([
            'type'     => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    /**
     * Weighted points for Leaflet heatmap layer (severity-based intensity).
     */
    public function incidentsHeatmap(): ResponseInterface
    {
        $points = [];
        $statuses = ['reported', 'verified', 'in_progress'];

        try {
            $builder = model(IncidentModel::class)->builder();
            $builder->whereIn('incidents.status', $statuses);
            (new \App\Services\BarangayScopeService())->applyIncidentScope($builder);
            $incidents = $builder->orderBy('incidents.id', 'DESC')->get(150)->getResultArray();
            $geo       = new IncidentGeoService();

            foreach ($incidents as $inc) {
                $resolved = $geo->resolve($inc);
                if ($resolved === null) {
                    continue;
                }
                $lat = $resolved['lat'];
                $lng = $resolved['lng'];

                $sev = strtolower((string) ($inc['severity'] ?? 'moderate'));
                $intensity = match ($sev) {
                    'critical' => 1.0,
                    'high'     => 0.8,
                    'low'      => 0.3,
                    default    => 0.5,
                };
                $priority = strtoupper((string) ($inc['priority_tag'] ?? ''));
                if ($priority === 'HIGH') {
                    $intensity = min(1.0, $intensity + 0.2);
                }

                $points[] = [$lat, $lng, round($intensity, 2)];
            }
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard::incidentsHeatmap — ' . $e->getMessage());
        }

        return $this->response->setJSON([
            'status' => 'ok',
            'points' => $points,
        ]);
    }

    public function liveStats(): ResponseInterface
    {
        $mapResponse = $this->incidentsMap();

        return $this->response->setJSON([
            'status'    => 'ok',
            'timestamp' => date('c'),
            'stats'     => (new DashboardStatsService())->getOverview(),
            'alerts'    => (new AlertsService())->getDashboardAlerts(),
            'map'       => json_decode($mapResponse->getBody(), true),
        ]);
    }

    /**
     * Live refresh payload for volunteer portal dashboard.
     */
    public function portalLive(): ResponseInterface
    {
        if (! auth()->loggedIn() || ! auth()->user()->inGroup('volunteer')) {
            return $this->response->setStatusCode(403)->setJSON(['message' => 'Volunteer access only.']);
        }

        $userId = (int) auth()->id();
        $svc    = new \App\Services\VolunteerDashboardService();
        $chart  = $svc->deploymentsChart7d($userId);
        $map    = json_decode($this->incidentsMap()->getBody(), true);

        return $this->response->setJSON([
            'status'      => 'ok',
            'timestamp'   => date('c'),
            'stats'       => $svc->getOverviewForUser($userId),
            'alerts'      => $svc->getAlertsForUser($userId),
            'assignments' => $svc->listAssignmentsForUser($userId),
            'chart'       => $chart,
            'map'         => $map,
        ]);
    }

    private function coord(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }

        return (float) $v;
    }

    /**
     * Last 7 days incident reports for dashboard chart.
     */
    public function incidentsChart(): ResponseInterface
    {
        $labels = [];
        $values = [];

        for ($i = 6; $i >= 0; $i--) {
            $day      = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = date('D', strtotime($day));
            $values[] = 0;
        }

        try {
            $model = model(IncidentModel::class);
            for ($i = 6; $i >= 0; $i--) {
                $day = date('Y-m-d', strtotime("-{$i} days"));
                $cnt = $model->builder()
                    ->where('DATE(created_at)', $day)
                    ->countAllResults();
                $values[6 - $i] = (int) $cnt;
            }
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard::incidentsChart — ' . $e->getMessage());
        }

        return $this->response->setJSON([
            'status' => 'ok',
            'labels' => $labels,
            'values' => $values,
            'source' => 'database',
        ]);
    }
}
