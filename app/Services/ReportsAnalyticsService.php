<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EvacuationCenterModel;
use App\Models\IncidentModel;
use App\Models\VolunteerDeploymentModel;
use Config\DrmsFeatures;
use Throwable;

/**
 * Analytics bundle for the admin Reports dashboard.
 */
class ReportsAnalyticsService
{
    public function __construct(
        private DashboardStatsService $stats = new DashboardStatsService(),
        private AlertsService $alerts = new AlertsService(),
        private DrmsFeatures $features = new DrmsFeatures(),
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getBundle(): array
    {
        return [
            'generated_at'     => date('c'),
            'overview'         => $this->stats->getOverview(),
            'alerts'           => $this->alerts->getDashboardAlerts(),
            'trend_7d'         => $this->incidentTrend7d(),
            'by_status'        => $this->incidentsGrouped('status', ['reported', 'verified', 'in_progress', 'resolved', 'closed']),
            'by_severity'      => $this->incidentsGrouped('severity', ['critical', 'high', 'moderate', 'low']),
            'by_type'          => $this->incidentsByType(),
            'recent_incidents' => $this->recentIncidents(8),
            'deployments_7d'   => $this->deploymentsLast7Days(),
            'evac_summary'     => $this->evacuationSummary(),
        ];
    }

    /**
     * @return array{labels:list<string>, values:list<int>}
     */
    public function incidentTrend7d(): array
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
                $values[6 - $i] = (int) $model->builder()
                    ->where('DATE(created_at)', $day)
                    ->countAllResults();
            }
        } catch (Throwable $e) {
            log_message('error', 'ReportsAnalyticsService::incidentTrend7d — ' . $e->getMessage());
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * @param list<string> $order
     * @return array{labels:list<string>, values:list<int>}
     */
    public function incidentsGrouped(string $column, array $order): array
    {
        $counts = array_fill_keys($order, 0);

        try {
            $rows = model(IncidentModel::class)
                ->select($column . ', COUNT(*) AS cnt')
                ->groupBy($column)
                ->findAll(50);

            foreach ($rows as $row) {
                $key = strtolower((string) ($row[$column] ?? ''));
                if (array_key_exists($key, $counts)) {
                    $counts[$key] = (int) ($row['cnt'] ?? 0);
                }
            }
        } catch (Throwable $e) {
            log_message('error', 'ReportsAnalyticsService::incidentsGrouped — ' . $e->getMessage());
        }

        return [
            'labels' => array_map(static fn (string $k): string => ucfirst(str_replace('_', ' ', $k)), array_keys($counts)),
            'values' => array_values($counts),
        ];
    }

    /**
     * @return array{labels:list<string>, values:list<int>}
     */
    public function incidentsByType(): array
    {
        $labels = [];
        $values = [];
        $types  = $this->features->incidentTypes;

        try {
            $rows = model(IncidentModel::class)
                ->select('incident_type, COUNT(*) AS cnt')
                ->groupBy('incident_type')
                ->findAll(50);

            $counts = [];
            foreach ($rows as $row) {
                $type = (string) ($row['incident_type'] ?? 'other');
                $counts[$type] = (int) ($row['cnt'] ?? 0);
            }

            foreach ($types as $key => $label) {
                if (($counts[$key] ?? 0) > 0) {
                    $labels[] = $label;
                    $values[] = $counts[$key];
                }
            }
            if (isset($counts['other']) && ! isset($types['other'])) {
                $labels[] = 'Other';
                $values[] = $counts['other'];
            }
        } catch (Throwable $e) {
            log_message('error', 'ReportsAnalyticsService::incidentsByType — ' . $e->getMessage());
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentIncidents(int $limit = 8): array
    {
        try {
            return model(IncidentModel::class)
                ->orderBy('id', 'DESC')
                ->findAll($limit);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * @return array{labels:list<string>, values:list<int>}
     */
    public function deploymentsLast7Days(): array
    {
        $labels = [];
        $values = [];

        for ($i = 6; $i >= 0; $i--) {
            $labels[] = date('D', strtotime("-{$i} days"));
            $values[] = 0;
        }

        try {
            $model = model(VolunteerDeploymentModel::class);
            for ($i = 6; $i >= 0; $i--) {
                $day = date('Y-m-d', strtotime("-{$i} days"));
                $values[6 - $i] = (int) $model->builder()
                    ->where('DATE(deployed_at)', $day)
                    ->countAllResults();
            }
        } catch (Throwable $e) {
            log_message('error', 'ReportsAnalyticsService::deploymentsLast7Days — ' . $e->getMessage());
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * @return array{
     *   centers:list<array<string,mixed>>,
     *   total_capacity:int,
     *   total_occupancy:int,
     *   open_count:int,
     *   full_count:int
     * }
     */
    public function evacuationSummary(): array
    {
        $out = [
            'centers'          => [],
            'total_capacity'   => 0,
            'total_occupancy'  => 0,
            'open_count'       => 0,
            'full_count'       => 0,
            'families_total'   => 0,
            'medical_needs'    => 0,
        ];

        try {
            $rows = model(EvacuationCenterModel::class)->orderBy('name', 'ASC')->findAll(100);
            foreach ($rows as $r) {
                $cap = (int) ($r['capacity'] ?? 0);
                $occ = (int) ($r['current_occupancy'] ?? 0);
                $pct = $cap > 0 ? (int) round(($occ / $cap) * 100) : 0;

                $out['total_capacity']  += $cap;
                $out['total_occupancy'] += $occ;
                $out['families_total']    += (int) ($r['families_registered'] ?? 0);
                $out['medical_needs']     += (int) ($r['medical_needs_count'] ?? 0);

                $status = strtolower((string) ($r['status'] ?? ''));
                if ($status === 'open') {
                    $out['open_count']++;
                }
                if ($status === 'full' || ($cap > 0 && $occ >= $cap)) {
                    $out['full_count']++;
                }

                $out['centers'][] = [
                    'id'         => (int) ($r['id'] ?? 0),
                    'name'       => (string) ($r['name'] ?? ''),
                    'capacity'   => $cap,
                    'occupancy'  => $occ,
                    'pct'        => $pct,
                    'status'     => $status,
                    'barangay_id'=> (int) ($r['barangay_id'] ?? 0),
                ];
            }
        } catch (Throwable $e) {
            log_message('error', 'ReportsAnalyticsService::evacuationSummary — ' . $e->getMessage());
        }

        return $out;
    }
}
