<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\IncidentModel;
use CodeIgniter\HTTP\IncomingRequest;

/**
 * Server-side DataTables queries for incidents (real database rows).
 */
class IncidentDataTableService
{
    /** @var list<string> */
    private array $orderColumns = [
        'incidents.reference_code',
        'incidents.title',
        'incidents.incident_type',
        'incidents.severity',
        'incidents.priority_tag',
        'incidents.status',
        'barangays.name',
        'incidents.created_at',
    ];

    public function __construct(
        private IncidentModel $incidents = new IncidentModel(),
    ) {
    }

    /**
     * @return array{rows:list<array<string,mixed>>, total:int, filtered:int}
     */
    public function fetch(IncomingRequest $request): array
    {
        $start  = max(0, (int) $request->getVar('start'));
        $length = min(100, max(5, (int) ($request->getVar('length') ?: 10)));
        $search = $request->getVar('search');
        $term   = is_array($search) ? trim((string) ($search['value'] ?? '')) : '';

        $scope  = new BarangayScopeService();
        $total  = $this->scopedCount($scope, '');
        $filtered = $this->scopedCount($scope, $term);

        $order   = $request->getVar('order');
        $colIdx  = is_array($order) ? (int) ($order[0]['column'] ?? 7) : 7;
        $dir     = (is_array($order) && strtolower((string) ($order[0]['dir'] ?? 'desc')) === 'asc') ? 'ASC' : 'DESC';
        $orderBy = $this->orderColumns[$colIdx] ?? 'incidents.created_at';

        $builder = $this->baseListBuilder($term);
        (new BarangayScopeService())->applyIncidentScope($builder);
        $rows    = $builder
            ->orderBy($orderBy, $dir)
            ->limit($length, $start)
            ->get()
            ->getResultArray();

        return [
            'rows'     => $rows,
            'total'    => $total,
            'filtered' => $filtered,
        ];
    }

    /**
     * Format DB rows for DataTables (HTML badges + actions).
     *
     * @param list<array<string,mixed>> $rows
     * @return list<list<string>>
     */
    public function formatRows(array $rows, bool $withActions = true): array
    {
        helper('drms');
        $data = [];

        foreach ($rows as $r) {
            $id       = (int) ($r['id'] ?? 0);
            $severity = strtolower((string) ($r['severity'] ?? ''));
            $status   = (string) ($r['status'] ?? '');
            $typeKey  = (string) ($r['incident_type'] ?? 'other');
            $typeLabel = esc(config('DrmsFeatures')->incidentTypes[$typeKey] ?? ucfirst(str_replace('_', ' ', $typeKey)));
            $priority = strtoupper((string) ($r['priority_tag'] ?? 'MEDIUM'));
            $sevLabel = esc(ucfirst($severity));
            $stLabel  = esc(ucfirst(str_replace('_', ' ', $status)));
            $priBadge = match ($priority) {
                'HIGH'   => 'danger',
                'LOW'    => 'info',
                default  => 'warning',
            };

            $row = [
                esc($r['reference_code'] ?? ''),
                esc($r['title'] ?? ''),
                '<span class="badge badge-light">' . $typeLabel . '</span>',
                '<span class="badge badge-' . esc($this->severityBadge($severity)) . '">' . $sevLabel . '</span>',
                '<span class="badge badge-' . esc($priBadge) . '">' . esc($priority) . '</span>',
                '<span class="badge badge-secondary">' . $stLabel . '</span>',
                esc($r['barangay_name'] ?? '—'),
                esc(drms_format_datetime((string) ($r['created_at'] ?? '')) ?: '—'),
            ];

            if ($withActions) {
                helper('crud');
                $row[] = drms_incident_table_actions($id, $status);
            }

            $data[] = $row;
        }

        return $data;
    }

    private function severityBadge(string $severity): string
    {
        return match ($severity) {
            'critical' => 'dark',
            'high'     => 'danger',
            'moderate' => 'warning',
            'low'      => 'info',
            default    => 'secondary',
        };
    }

    /**
     * Base query for listing — must exclude soft-deleted rows (Model::builder() does not).
     *
     * @return \CodeIgniter\Database\BaseBuilder
     */
    private function baseListBuilder(string $term)
    {
        $builder = $this->incidents->builder();
        $builder
            ->select('incidents.id, incidents.reference_code, incidents.title, incidents.incident_type, incidents.severity, incidents.priority_tag, incidents.status, incidents.created_at, barangays.name AS barangay_name')
            ->join('barangays', 'barangays.id = incidents.barangay_id', 'left')
            ->where('incidents.deleted_at', null);

        if ($term !== '') {
            $builder
                ->groupStart()
                ->like('incidents.title', $term)
                ->orLike('incidents.reference_code', $term)
                ->orLike('barangays.name', $term)
                ->groupEnd();
        }

        return $builder;
    }

    private function countFiltered(string $term): int
    {
        $scope = new BarangayScopeService();

        return $this->scopedCount($scope, $term);
    }

    private function scopedCount(BarangayScopeService $scope, string $term): int
    {
        $builder = $this->baseListBuilder($term);
        $scope->applyIncidentScope($builder);

        return $builder->countAllResults();
    }
}
