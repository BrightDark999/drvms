<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EvacuationCenterModel;
use App\Models\IncidentModel;
use Config\Automation;
use Throwable;

/**
 * Rule-based operational alerts for the dashboard.
 */
class AlertsService
{
    public function __construct(
        private Automation $config = new Automation(),
    ) {
    }

    /**
     * @return list<array{type:string, level:string, title:string, message:string, url?:string}>
     */
    public function getDashboardAlerts(): array
    {
        $alerts = [];

        try {
            $this->addIncidentAlerts($alerts);
            $this->addEvacuationAlerts($alerts);
        } catch (Throwable $e) {
            log_message('error', 'AlertsService: ' . $e->getMessage());
            $alerts[] = [
                'type'    => 'system',
                'level'   => 'warning',
                'title'   => 'Alerts unavailable',
                'message' => 'Could not load alerts. Check database connection.',
            ];
        }

        return $alerts;
    }

    /**
     * @param list<array<string, string>> $alerts
     */
    private function addIncidentAlerts(array &$alerts): void
    {
        $model = new IncidentModel();

        $pending = $model->where('status', 'reported')->countAllResults();
        if ($pending > 0) {
            $alerts[] = [
                'type'    => 'incident',
                'level'   => 'warning',
                'title'   => 'Pending verification',
                'message' => "{$pending} incident(s) awaiting MDRRMO/LGU verification.",
                'url'     => base_url('admin/incidents'),
            ];
        }

        $critical = $model->where('severity', 'critical')
            ->whereIn('status', ['reported', 'verified', 'in_progress'])
            ->findAll(5);

        foreach ($critical as $inc) {
            $alerts[] = [
                'type'    => 'incident',
                'level'   => 'danger',
                'title'   => 'Critical: ' . ($inc['reference_code'] ?? ''),
                'message' => $inc['title'] ?? '',
                'url'     => base_url('admin/incidents'),
            ];
        }

        $cutoff = date('Y-m-d H:i:s', strtotime('-' . $this->config->staleIncidentHours . ' hours'));
        $stale  = $model->where('status', 'reported')->where('created_at <', $cutoff)->countAllResults();
        if ($stale > 0) {
            $alerts[] = [
                'type'    => 'incident',
                'level'   => 'info',
                'title'   => 'Stale reports',
                'message' => "{$stale} reported incident(s) older than {$this->config->staleIncidentHours}h — follow up.",
                'url'     => base_url('admin/incidents'),
            ];
        }
    }

    /**
     * @param list<array<string, string>> $alerts
     */
    private function addEvacuationAlerts(array &$alerts): void
    {
        $centers = model(EvacuationCenterModel::class)->findAll(50);
        $threshold = $this->config->evacOccupancyAlertPercent;

        foreach ($centers as $c) {
            $cap = (int) ($c['capacity'] ?? 0);
            $occ = (int) ($c['current_occupancy'] ?? 0);
            if ($cap <= 0) {
                continue;
            }
            $pct = (int) round(($occ / $cap) * 100);
            if ($pct >= $threshold) {
                $alerts[] = [
                    'type'    => 'evacuation',
                    'level'   => $pct >= 95 ? 'danger' : 'warning',
                    'title'   => 'High occupancy: ' . ($c['name'] ?? 'Center'),
                    'message' => "{$occ}/{$cap} ({$pct}%) — consider additional capacity.",
                    'url'     => base_url('admin/evacuation-centers'),
                ];
            }
        }
    }
}
