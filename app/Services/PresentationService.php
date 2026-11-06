<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Central presentation/demo layer — sample JSON for lists/charts and simulated POST responses.
 */
class PresentationService
{
    /** @var array<string, string> normalized path => fixture key */
    private array $getRoutes = [
        'admin/incidents/data'              => 'incidents',
        'admin/volunteers/data'             => 'volunteers',
        'admin/evacuation-centers/data'     => 'evacuation',
        'admin/users/data'                  => 'users',
        'admin/audit-trail/data'            => 'audit',
        'admin/dashboard/incidents-chart'   => 'incidents_chart',
        'admin/dashboard/incidents-map'     => 'incidents_map',
        'admin/dashboard/incidents-heatmap' => 'heatmap',
        'admin/dashboard/live'              => 'live',
    ];

    public function applyGetFixture(string $path, IncomingRequest $request): ?array
    {
        $path = trim($path, '/');

        if (preg_match('#^admin/incidents/show/(\d+)$#', $path, $m)) {
            $row = PresentationFixtures::incidentRows()[0];
            $row['id'] = (int) $m[1];

            return ['status' => 'ok', 'message' => 'OK', 'data' => $row];
        }

        if (preg_match('#^admin/volunteers/show/(\d+)$#', $path, $m)) {
            return [
                'status'  => 'ok',
                'message' => 'OK',
                'data'    => [
                    'id'                     => (int) $m[1],
                    'full_name'              => 'Juan Dela Cruz',
                    'email'                  => 'juan.delacruz@example.com',
                    'phone'                  => '09171234567',
                    'availability_status'    => 'available',
                    'id_verification_status' => 'verified',
                    'barangay_id'            => 1,
                ],
            ];
        }

        $key  = $this->getRoutes[$path] ?? null;
        if ($key === null) {
            return null;
        }

        helper(['datatable', 'crud']);

        return match ($key) {
            'incidents'       => $this->datatableFromIncidents($request),
            'volunteers'      => $this->datatablePayload($request, PresentationFixtures::volunteersTable()),
            'evacuation'      => $this->datatablePayload($request, PresentationFixtures::evacuationCentersTable()),
            'users'           => $this->datatablePayload($request, PresentationFixtures::usersTable()),
            'audit'           => $this->datatablePayload($request, PresentationFixtures::auditTrailTable()),
            'incidents_chart' => PresentationFixtures::incidentsChart(),
            'incidents_map'   => PresentationFixtures::incidentsMapGeoJson(),
            'heatmap'         => [
                'status' => 'ok',
                'points' => PresentationFixtures::incidentsHeatmapPoints(),
            ],
            'live' => [
                'status'    => 'ok',
                'timestamp' => date('c'),
                'stats'     => PresentationFixtures::dashboardStats(),
                'alerts'    => PresentationFixtures::dashboardAlerts(),
                'map'       => PresentationFixtures::incidentsMapGeoJson(),
            ],
            default => null,
        };
    }

    public function blockWriteRequest(string $path, IncomingRequest $request): ResponseInterface|RedirectResponse|null
    {
        // Only block writes when offline demo fixtures are used (no real MySQL).
        // Database-backed demo and normal logins must allow real CRUD.
        if (! function_exists('drms_use_presentation_fixtures') || ! drms_use_presentation_fixtures()) {
            return null;
        }

        $path = trim($path, '/');

        if ($this->isExcludedPost($path)) {
            return null;
        }

        if (! $this->isBlockedWritePath($path)) {
            return null;
        }

        helper('crud');

        return crud_json_ok(
            'Demo mode: saved for preview only (not written to the database).',
            ['demo' => true, 'path' => $path],
        );
    }

    private function isBlockedWritePath(string $path): bool
    {
        if ($path === 'report/submit') {
            return false;
        }

        return str_starts_with($path, 'admin/')
            || str_starts_with($path, 'profile/')
            || str_starts_with($path, 'api/v1/')
            || str_starts_with($path, 'evac-centers/');
    }

    private function isExcludedPost(string $path): bool
    {
        return in_array($path, ['login', 'register', 'logout', 'demo/logout'], true)
            || str_starts_with($path, 'auth/');
    }

    private function datatableFromIncidents(IncomingRequest $request): array
    {
        $service     = new IncidentDataTableService();
        $withActions = $request->getVar('no_actions') !== '1';
        $rows        = PresentationFixtures::incidentRows();
        $data        = $service->formatRows($rows, $withActions);
        $total       = count($data);
        $draw        = is_numeric($request->getVar('draw')) ? (int) $request->getVar('draw') : 0;

        return [
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $data,
            'demo'            => true,
        ];
    }

    /**
     * @param list<list<string>> $rows
     * @return array<string, mixed>
     */
    private function datatablePayload(IncomingRequest $request, array $rows): array
    {
        $total = count($rows);
        $draw  = is_numeric($request->getVar('draw')) ? (int) $request->getVar('draw') : 0;

        return [
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $rows,
            'demo'            => true,
        ];
    }
}
