<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EvacuationCenterModel;
use App\Models\IncidentModel;
use App\Models\VolunteerModel;
use Throwable;

/**
 * Aggregates dashboard KPIs from core operational tables.
 */
class DashboardStatsService
{
    public function getOverview(): array
    {
        if (function_exists('drms_runtime_ready') && ! drms_runtime_ready()) {
            return [
                'active_incidents'      => 0,
                'volunteers_on_duty'    => 0,
                'evacuation_centers'    => 0,
                'evacuation_occupancy'  => '0%',
                'pending_verifications' => 0,
                'total_volunteers'      => 0,
            ];
        }

        $defaults = [
            'active_incidents'      => 0,
            'volunteers_on_duty'    => 0,
            'evacuation_centers'    => 0,
            'evacuation_occupancy'  => '0%',
            'pending_verifications' => 0,
            'total_volunteers'      => 0,
        ];

        try {
            $evacModel = model(EvacuationCenterModel::class);

            $activeStatuses = ['reported', 'verified', 'in_progress'];
            $activeIncidents = (new IncidentModel())
                ->whereIn('status', $activeStatuses)
                ->countAllResults();

            $availableVolunteers = (new VolunteerModel())
                ->where('availability_status', 'available')
                ->countAllResults();

            $totalVolunteers = (new VolunteerModel())->countAllResults();
            $evacCount       = $evacModel->countAllResults();
            $occupancy       = $this->averageEvacuationOccupancy($evacModel);

            $pendingVerify = (new IncidentModel())
                ->where('status', 'reported')
                ->countAllResults();

            return [
                'active_incidents'      => $activeIncidents,
                'volunteers_on_duty'    => $availableVolunteers,
                'evacuation_centers'    => $evacCount,
                'evacuation_occupancy'  => $occupancy,
                'pending_verifications' => $pendingVerify,
                'total_volunteers'      => $totalVolunteers,
            ];
        } catch (Throwable $e) {
            log_message('error', 'DashboardStatsService: ' . $e->getMessage());

            return $defaults;
        }
    }

    private function averageEvacuationOccupancy(EvacuationCenterModel $model): string
    {
        try {
            $rows = $model->select('SUM(current_occupancy) AS occ, SUM(capacity) AS cap')->first();
            if (! $rows || empty($rows['cap']) || (float) $rows['cap'] <= 0) {
                return '0%';
            }
            $pct = round(((float) $rows['occ'] / (float) $rows['cap']) * 100);

            return $pct . '%';
        } catch (Throwable $e) {
            return '0%';
        }
    }
}
