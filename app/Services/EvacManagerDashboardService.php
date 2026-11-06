<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EvacuationCenterModel;
use Throwable;

/**
 * KPIs and family snapshot for the evacuation manager dashboard (scoped to assigned center).
 */
class EvacManagerDashboardService
{
    /**
     * @return array<string, int|string>
     */
    public function getOverviewForUser(int $userId): array
    {
        $centerId = $this->assignedCenterId($userId);
        $summary  = $this->summaryForScope($centerId);

        $cap = (int) ($summary['total_capacity'] ?? 0);
        $occ = (int) ($summary['total_occupancy'] ?? 0);

        return [
            'assigned_center_id' => $centerId ?? 0,
            'has_assignment'     => $centerId !== null,
            'total_centers'      => $centerId !== null ? 1 : count($summary['centers'] ?? []),
            'open_centers'       => (int) ($summary['open_count'] ?? 0),
            'full_centers'       => (int) ($summary['full_count'] ?? 0),
            'total_capacity'     => $cap,
            'total_occupancy'    => $occ,
            'occupancy_rate'     => $cap > 0 ? (int) round(($occ / $cap) * 100) . '%' : '0%',
            'families_total'     => (int) ($summary['families_total'] ?? 0),
            'medical_needs'      => (int) ($summary['medical_needs'] ?? 0),
            'families_checked_in'=> (int) ($summary['families_checked_in'] ?? 0),
            'families_pending'   => (int) ($summary['families_pending'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getAssignedCenter(int $userId): ?array
    {
        $centerId = $this->assignedCenterId($userId);
        if ($centerId === null) {
            return null;
        }

        $center = model(EvacuationCenterModel::class)->find($centerId);
        if (! $center) {
            return null;
        }

        $cap = (int) ($center['capacity'] ?? 0);
        $occ = (int) ($center['current_occupancy'] ?? 0);

        return [
            'id'                  => $centerId,
            'name'                => (string) ($center['name'] ?? ''),
            'status'              => (string) ($center['status'] ?? ''),
            'capacity'            => $cap,
            'occupancy'           => $occ,
            'occupancy_pct'       => $cap > 0 ? (int) round(($occ / $cap) * 100) : 0,
            'families_registered' => (int) ($center['families_registered'] ?? 0),
            'medical_needs_count' => (int) ($center['medical_needs_count'] ?? 0),
            'contact_person'      => (string) ($center['contact_person'] ?? ''),
            'contact_phone'       => (string) ($center['contact_phone'] ?? ''),
            'address'             => (string) ($center['address'] ?? ''),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listCentersForUser(int $userId): array
    {
        $centerId = $this->assignedCenterId($userId);
        $summary  = $this->summaryForScope($centerId);

        return $summary['centers'] ?? [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listFamiliesForUser(int $userId): array
    {
        $centerId = $this->assignedCenterId($userId);
        if ($centerId === null) {
            return [];
        }

        return (new EvacFamilyService())->listEnrichedForCenter($centerId);
    }

    public function assignedCenterId(int $userId): ?int
    {
        $row = model(\App\Models\UserProfileModel::class)->where('user_id', $userId)->first();
        $id  = (int) ($row['assigned_evacuation_center_id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function summaryForScope(?int $centerId): array
    {
        try {
            $summary = (new ReportsAnalyticsService())->evacuationSummary();
            if ($centerId === null) {
                return $this->appendFamilyStatusCounts($summary);
            }

            $centers = array_values(array_filter(
                $summary['centers'] ?? [],
                static fn (array $c): bool => (int) ($c['id'] ?? 0) === $centerId
            ));

            $center = model(EvacuationCenterModel::class)->find($centerId);
            if (! $center) {
                return $this->emptySummary();
            }

            $cap = (int) ($center['capacity'] ?? 0);
            $occ = (int) ($center['current_occupancy'] ?? 0);
            $status = strtolower((string) ($center['status'] ?? ''));

            return $this->appendFamilyStatusCounts([
                'centers'         => $centers,
                'total_capacity'  => $cap,
                'total_occupancy' => $occ,
                'open_count'      => $status === 'open' ? 1 : 0,
                'full_count'      => ($status === 'full' || ($cap > 0 && $occ >= $cap)) ? 1 : 0,
                'families_total'  => (int) ($center['families_registered'] ?? 0),
                'medical_needs'   => (int) ($center['medical_needs_count'] ?? 0),
            ], $centerId);
        } catch (Throwable $e) {
            log_message('error', 'EvacManagerDashboardService::summaryForScope — ' . $e->getMessage());

            return $this->emptySummary();
        }
    }

    /**
     * @param array<string, mixed> $summary
     * @return array<string, mixed>
     */
    private function appendFamilyStatusCounts(array $summary, ?int $centerId = null): array
    {
        $checkedIn = 0;
        $pending = 0;
        $families = $centerId !== null
            ? (new EvacFamilyService())->listEnrichedForCenter($centerId)
            : [];

        foreach ($families as $family) {
            if (($family['check_in_status'] ?? '') === 'checked_in') {
                $checkedIn++;
            } elseif (($family['check_in_status'] ?? '') === 'pending') {
                $pending++;
            }
        }

        $summary['families_checked_in'] = $checkedIn;
        $summary['families_pending']    = $pending;

        return $summary;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptySummary(): array
    {
        return [
            'centers'              => [],
            'total_capacity'       => 0,
            'total_occupancy'      => 0,
            'open_count'           => 0,
            'full_count'           => 0,
            'families_total'       => 0,
            'medical_needs'        => 0,
            'families_checked_in'  => 0,
            'families_pending'     => 0,
        ];
    }
}
