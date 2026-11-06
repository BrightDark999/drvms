<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLogModel;
use App\Models\IncidentModel;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\HTTP\IncomingRequest;
use Throwable;

/**
 * Query, format, and summarize audit log rows for the admin audit trail UI.
 */
class AuditTrailService
{
    /** @var list<string> */
    private array $excludedEntityTypes = ['relief_item', 'relief_distribution'];

    /** @var list<string> */
    private array $excludedActionPrefixes = ['relief_item.', 'relief_distribution.'];

    public function baseBuilder(): BaseBuilder
    {
        $builder = model(AuditLogModel::class)->builder();
        $builder->select('audit_logs.*, users.username, users.email')
            ->join('users', 'users.id = audit_logs.user_id', 'left');
        $this->applyExclusions($builder);

        return $builder;
    }

    public function applyFilters(BaseBuilder $builder, IncomingRequest $request): void
    {
        $entityType  = trim((string) ($request->getGet('entity_type') ?? $request->getVar('entity_type') ?? ''));
        $actionGroup = trim((string) ($request->getGet('action_group') ?? $request->getVar('action_group') ?? ''));
        $userId      = (int) ($request->getGet('user_id') ?? $request->getVar('user_id') ?? 0);
        $dateFrom    = trim((string) ($request->getGet('date_from') ?? $request->getVar('date_from') ?? ''));
        $dateTo      = trim((string) ($request->getGet('date_to') ?? $request->getVar('date_to') ?? ''));
        $preset      = trim((string) ($request->getGet('date_preset') ?? $request->getVar('date_preset') ?? ''));

        if ($preset === 'today') {
            $builder->where('DATE(audit_logs.created_at)', date('Y-m-d'));
        } elseif ($preset === '7d') {
            $builder->where('audit_logs.created_at >=', date('Y-m-d H:i:s', strtotime('-7 days')));
        } elseif ($preset === '30d') {
            $builder->where('audit_logs.created_at >=', date('Y-m-d H:i:s', strtotime('-30 days')));
        }

        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $builder->where('DATE(audit_logs.created_at) >=', $dateFrom);
        }
        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $builder->where('DATE(audit_logs.created_at) <=', $dateTo);
        }

        if ($entityType !== '') {
            $builder->where('audit_logs.entity_type', $entityType);
        }

        if ($userId > 0) {
            $builder->where('audit_logs.user_id', $userId);
        }

        if ($actionGroup !== '') {
            $prefix = match ($actionGroup) {
                'incident'   => 'incident.',
                'volunteer'  => 'volunteer.',
                'evacuation' => 'evacuation.',
                'donation'   => 'donation.',
                'matching'   => 'matching.',
                default      => '',
            };
            if ($prefix !== '') {
                $builder->like('audit_logs.action', $prefix, 'after');
            }
        }

        $search = $request->getVar('search');
        if (is_array($search) && ! empty($search['value'])) {
            $q = trim((string) $search['value']);
            if ($q !== '') {
                $builder->groupStart()
                    ->like('audit_logs.action', $q)
                    ->orLike('audit_logs.entity_type', $q)
                    ->orLike('audit_logs.meta_json', $q)
                    ->orLike('audit_logs.ip_address', $q)
                    ->orLike('users.email', $q)
                    ->orLike('users.username', $q)
                    ->groupEnd();
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        $today = 0;
        $week  = 0;
        $topAction = '—';
        $topUser   = '—';

        try {
            $base = $this->baseBuilder();
            $today = (int) (clone $base)->where('DATE(audit_logs.created_at)', date('Y-m-d'))->countAllResults();
            $week  = (int) (clone $base)->where('audit_logs.created_at >=', date('Y-m-d H:i:s', strtotime('-7 days')))->countAllResults();

            $actionRow = (clone $base)
                ->select('audit_logs.action, COUNT(*) AS cnt')
                ->groupBy('audit_logs.action')
                ->orderBy('cnt', 'DESC')
                ->limit(1)
                ->get()
                ->getRowArray();
            if ($actionRow) {
                $topAction = $this->actionLabel((string) ($actionRow['action'] ?? ''));
            }

            $userRow = (clone $base)
                ->select('audit_logs.user_id, users.username, users.email, COUNT(*) AS cnt')
                ->where('audit_logs.user_id IS NOT NULL')
                ->groupBy('audit_logs.user_id, users.username, users.email')
                ->orderBy('cnt', 'DESC')
                ->limit(1)
                ->get()
                ->getRowArray();
            if ($userRow) {
                $topUser = $this->userLabel($userRow);
            }
        } catch (Throwable $e) {
            log_message('error', 'AuditTrailService::getSummary — ' . $e->getMessage());
        }

        return [
            'today'      => $today,
            'week'       => $week,
            'top_action' => $topAction,
            'top_user'   => $topUser,
        ];
    }

    /**
     * @return array{labels:list<string>, values:list<int>}
     */
    public function activityTrend(int $days = 14): array
    {
        $labels = [];
        $values = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $day      = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = date('M j', strtotime($day));
            $values[] = 0;
        }

        try {
            $base = $this->baseBuilder();
            for ($i = $days - 1; $i >= 0; $i--) {
                $day = date('Y-m-d', strtotime("-{$i} days"));
                $values[$days - 1 - $i] = (int) (clone $base)
                    ->where('DATE(audit_logs.created_at)', $day)
                    ->countAllResults();
            }
        } catch (Throwable $e) {
            log_message('error', 'AuditTrailService::activityTrend — ' . $e->getMessage());
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function timeline(int $limit = 40, ?IncomingRequest $request = null): array
    {
        try {
            $builder = $this->baseBuilder();
            if ($request !== null) {
                $this->applyFilters($builder, $request);
            }
            $rows = $builder->orderBy('audit_logs.id', 'DESC')->limit($limit, 0)->get()->getResultArray();

            return array_map(fn (array $r): array => $this->enrichRow($r), $rows);
        } catch (Throwable $e) {
            log_message('error', 'AuditTrailService::timeline — ' . $e->getMessage());

            return [];
        }
    }

    /**
     * @return list<array<string, string>>
     */
    public function formatRowsForTable(array $rows): array
    {
        $data = [];
        foreach ($rows as $r) {
            $enriched = $this->enrichRow($r);
            $rowClass = $this->isDestructive((string) ($r['action'] ?? '')) ? 'drms-audit-row--danger' : '';

            $data[] = [
                $this->whenCell($enriched),
                esc($enriched['user_label']),
                $this->actionBadge((string) ($r['action'] ?? '')),
                $this->entityCell($enriched),
                $this->detailsButton((int) ($r['id'] ?? 0), $rowClass),
            ];
        }

        return $data;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findEnriched(int $id): ?array
    {
        try {
            $row = $this->baseBuilder()->where('audit_logs.id', $id)->get()->getRowArray();

            return $row ? $this->enrichRow($row, true) : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * @return list<array{id:int, label:string}>
     */
    public function filterUsers(): array
    {
        try {
            $rows = $this->baseBuilder()
                ->select('audit_logs.user_id, users.username, users.email')
                ->where('audit_logs.user_id IS NOT NULL')
                ->groupBy('audit_logs.user_id, users.username, users.email')
                ->orderBy('users.email', 'ASC')
                ->limit(100)
                ->get()
                ->getResultArray();

            $out = [];
            foreach ($rows as $r) {
                $uid = (int) ($r['user_id'] ?? 0);
                if ($uid > 0) {
                    $out[] = ['id' => $uid, 'label' => $this->userLabel($r)];
                }
            }

            return $out;
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * @return list<string>
     */
    public function filterEntityTypes(): array
    {
        try {
            $rows = $this->baseBuilder()
                ->distinct()
                ->select('audit_logs.entity_type')
                ->orderBy('audit_logs.entity_type', 'ASC')
                ->get()
                ->getResultArray();

            return array_values(array_filter(array_map(
                static fn (array $r): string => (string) ($r['entity_type'] ?? ''),
                $rows
            )));
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    public function toCsv(array $rows): string
    {
        $lines   = [];
        $lines[] = implode(',', ['When', 'User', 'Action', 'Entity Type', 'Entity ID', 'IP', 'Meta']);

        foreach ($rows as $r) {
            $enriched = $this->enrichRow($r, true);
            $lines[]  = implode(',', array_map(
                static fn (string $v): string => '"' . str_replace('"', '""', $v) . '"',
                [
                    (string) ($enriched['created_at'] ?? ''),
                    (string) ($enriched['user_label'] ?? ''),
                    (string) ($enriched['action_label'] ?? ''),
                    (string) ($enriched['entity_type'] ?? ''),
                    (string) ($enriched['entity_id'] ?? ''),
                    (string) ($enriched['ip_address'] ?? ''),
                    (string) ($enriched['meta_pretty'] ?? ''),
                ]
            ));
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function enrichRow(array $row, bool $fullMeta = false): array
    {
        $meta = [];
        if (! empty($row['meta_json'])) {
            $decoded = json_decode((string) $row['meta_json'], true);
            $meta    = is_array($decoded) ? $decoded : [];
        }

        $action = (string) ($row['action'] ?? '');
        $entity = $this->entityDescription($row, $meta);

        return array_merge($row, [
            'user_label'    => $this->userLabel($row),
            'action_label'  => $this->actionLabel($action),
            'entity_label'  => $entity['label'],
            'entity_url'    => $entity['url'],
            'meta'          => $meta,
            'meta_pretty'   => $meta !== [] ? json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '',
            'is_destructive'=> $this->isDestructive($action),
            'badge_class'   => $this->actionBadgeClass($action),
        ]);
    }

  /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $meta
     * @return array{label:string, url:?string}
     */
    private function entityDescription(array $row, array $meta): array
    {
        $type = (string) ($row['entity_type'] ?? '');
        $id   = (int) ($row['entity_id'] ?? 0);
        $ref  = (string) ($meta['reference'] ?? $meta['reference_code'] ?? '');

        if ($ref !== '') {
            return [
                'label' => ucwords(str_replace('_', ' ', $type)) . ' · ' . $ref,
                'url'   => $this->entityUrl($type, $id),
            ];
        }

        if ($type === 'incident' && $id > 0) {
            try {
                $inc = model(IncidentModel::class)->find($id);
                if ($inc) {
                    return [
                        'label' => 'Incident · ' . ($inc['reference_code'] ?? '#' . $id),
                        'url'   => base_url('admin/incidents'),
                    ];
                }
            } catch (Throwable $e) {
                // ignore
            }
        }

        $label = ucwords(str_replace('_', ' ', $type));
        if ($id > 0) {
            $label .= ' #' . $id;
        }

        return ['label' => $label, 'url' => $this->entityUrl($type, $id)];
    }

    private function entityUrl(string $type, int $id): ?string
    {
        return match ($type) {
            'incident'           => base_url('admin/incidents'),
            'volunteer'          => base_url('admin/volunteers'),
            'evacuation_center'  => base_url('admin/evacuation-centers'),
            'volunteer_deployment' => base_url('admin/matching'),
            default              => null,
        };
    }

    /**
     * @param array<string, mixed> $row
     */
    private function userLabel(array $row): string
    {
        $username = trim((string) ($row['username'] ?? ''));
        $email    = trim((string) ($row['email'] ?? ''));

        if ($username !== '') {
            return $username;
        }
        if ($email !== '') {
            return $email;
        }

        return (int) ($row['user_id'] ?? 0) > 0 ? 'User #' . (int) $row['user_id'] : 'System / public';
    }

    private function actionLabel(string $action): string
    {
        $map = [
            'incident.create'        => 'Created incident',
            'incident.verify'        => 'Verified incident',
            'incident.update'        => 'Updated incident',
            'incident.delete'        => 'Deleted incident',
            'volunteer.create'       => 'Created volunteer',
            'volunteer.update'       => 'Updated volunteer',
            'volunteer.delete'       => 'Deleted volunteer',
            'volunteer.deploy'       => 'Deployed volunteer',
            'evacuation.create'      => 'Created evacuation center',
            'evacuation.update'      => 'Updated evacuation center',
            'evacuation.delete'      => 'Deleted evacuation center',
            'donation.create'        => 'Recorded donation',
            'donation.update'        => 'Updated donation',
            'donation.delete'        => 'Deleted donation',
        ];

        return $map[$action] ?? ucwords(str_replace(['.', '_'], ' ', $action));
    }

    private function actionBadgeClass(string $action): string
    {
        if (str_contains($action, 'delete')) {
            return 'danger';
        }
        if (str_contains($action, 'create') || str_contains($action, 'register')) {
            return 'success';
        }
        if (str_contains($action, 'deploy')) {
            return 'warning';
        }
        if (str_contains($action, 'verify')) {
            return 'primary';
        }

        return 'info';
    }

    private function actionBadge(string $action): string
    {
        $class = $this->actionBadgeClass($action);

        return '<span class="badge badge-' . $class . ' drms-audit-badge">' . esc($this->actionLabel($action)) . '</span>';
    }

    private function isDestructive(string $action): bool
    {
        return str_contains($action, 'delete');
    }

    /**
     * @param array<string, mixed> $row
     */
    private function whenCell(array $row): string
    {
        $at = (string) ($row['created_at'] ?? '');

        return '<div class="drms-audit-when"><strong>' . esc(date('M j, Y', strtotime($at) ?: time())) . '</strong>'
            . '<div class="small text-muted">' . esc(date('g:i A', strtotime($at) ?: time())) . '</div></div>';
    }

    /**
     * @param array<string, mixed> $row
     */
    private function entityCell(array $row): string
    {
        $label = esc($row['entity_label'] ?? '');
        $url   = $row['entity_url'] ?? null;
        if ($url) {
            return '<a href="' . esc($url) . '">' . $label . '</a>';
        }

        return $label;
    }

    private function detailsButton(int $id, string $rowClass): string
    {
        return '<button type="button" class="btn btn-xs btn-outline-secondary drms-icon-action btn-audit-detail" data-id="' . $id . '" title="View details" aria-label="View details">'
            . '<i class="fas fa-search"></i></button>';
    }

    private function applyExclusions(BaseBuilder $builder): void
    {
        if ($this->excludedEntityTypes !== []) {
            $builder->whereNotIn('audit_logs.entity_type', $this->excludedEntityTypes);
        }
        foreach ($this->excludedActionPrefixes as $prefix) {
            $builder->notLike('audit_logs.action', $prefix, 'after');
        }
    }
}
