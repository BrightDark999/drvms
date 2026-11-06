<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EvacuationCenterModel;
use App\Models\IncidentModel;
use App\Models\VolunteerDeploymentModel;
use App\Models\VolunteerModel;
use Throwable;

/**
 * KPIs and activity for the volunteer portal dashboard.
 */
class VolunteerDashboardService
{
    public function __construct(
        private VolunteerModel $volunteers = new VolunteerModel(),
        private VolunteerDeploymentModel $deployments = new VolunteerDeploymentModel(),
        private VolunteerSkillService $skills = new VolunteerSkillService(),
        private UserNotificationService $notifications = new UserNotificationService(),
        private IncidentModel $incidents = new IncidentModel(),
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findVolunteerForUser(int $userId): ?array
    {
        return (new ProfileService())->getVolunteerRecord($userId);
    }

    /**
     * @return array<string, mixed>
     */
    public function getOverviewForUser(int $userId): array
    {
        $defaults = [
            'has_profile'           => false,
            'volunteer_id'          => 0,
            'availability_status'   => 'unknown',
            'availability_label'    => 'Not registered',
            'fatigue_level'         => 0,
            'id_verification_status'=> 'none',
            'skills_count'          => 0,
            'active_assignments'    => 0,
            'total_assignments'     => 0,
            'unread_notifications'  => 0,
            'active_incidents'      => 0,
            'critical_incidents'    => 0,
            'evacuation_centers'    => 0,
            'evacuation_occupancy'  => '0%',
        ];

        try {
            $volunteer = $this->findVolunteerForUser($userId);
            if ($volunteer === null) {
                $defaults['unread_notifications'] = $this->notifications->unreadCount($userId);
                $defaults['active_incidents']     = $this->countActiveIncidents();
                $defaults['evacuation_centers']   = model(EvacuationCenterModel::class)->countAllResults();
                $defaults['evacuation_occupancy'] = (new DashboardStatsService())->getOverview()['evacuation_occupancy'] ?? '0%';

                return $defaults;
            }

            $volunteerId = (int) $volunteer['id'];
            $status      = strtolower((string) ($volunteer['availability_status'] ?? 'available'));
            $skills      = $this->skills->listForVolunteer($volunteerId);
            $active      = $this->deployments
                ->where('volunteer_id', $volunteerId)
                ->where('status', 'deployed')
                ->countAllResults();
            $total       = $this->deployments
                ->where('volunteer_id', $volunteerId)
                ->countAllResults();

            return [
                'has_profile'            => true,
                'volunteer_id'           => $volunteerId,
                'availability_status'    => $status,
                'availability_label'     => $this->availabilityLabel($status),
                'fatigue_level'          => (int) ($volunteer['fatigue_level'] ?? 0),
                'id_verification_status' => strtolower((string) ($volunteer['id_verification_status'] ?? 'none')),
                'skills_count'           => count($skills),
                'active_assignments'     => $active,
                'total_assignments'      => $total,
                'unread_notifications'   => $this->notifications->unreadCount($userId),
                'active_incidents'       => $this->countActiveIncidents(),
                'critical_incidents'     => $this->countCriticalIncidents(),
                'evacuation_centers'     => model(EvacuationCenterModel::class)->countAllResults(),
                'evacuation_occupancy'   => (new DashboardStatsService())->getOverview()['evacuation_occupancy'] ?? '0%',
            ];
        } catch (Throwable $e) {
            log_message('error', 'VolunteerDashboardService::getOverviewForUser — ' . $e->getMessage());

            return $defaults;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getAlertsForUser(int $userId): array
    {
        $alerts   = [];
        $overview = $this->getOverviewForUser($userId);
        $volunteer = $this->findVolunteerForUser($userId);

        if ($volunteer === null) {
            $alerts[] = [
                'level'   => 'warning',
                'title'   => 'Complete your volunteer profile',
                'message' => 'Add your skills and upload a valid ID so MDRRMO can verify and deploy you.',
                'url'     => base_url('profile'),
            ];

            return $alerts;
        }

        $approvedSkills = (new VolunteerSkillRequestService())->skillNamesForVolunteer((int) $volunteer['id']);
        if ($approvedSkills === []) {
            $pending = (new VolunteerSkillRequestService())->getPendingForVolunteer((int) $volunteer['id']);
            if ($pending) {
                $alerts[] = [
                    'level'   => 'info',
                    'title'   => 'Skill request pending',
                    'message' => 'MDRRMO is reviewing your requested skills. My Dispatch will update after approval.',
                    'url'     => base_url('profile'),
                ];
            } else {
                $alerts[] = [
                    'level'   => 'warning',
                    'title'   => 'No approved skills',
                    'message' => 'Submit your skills on your profile for admin approval to see matching incidents.',
                    'url'     => base_url('profile'),
                ];
            }
        }

        $idStatus = strtolower((string) ($overview['id_verification_status'] ?? 'none'));
        if ($idStatus === 'pending') {
            $alerts[] = [
                'level'   => 'info',
                'title'   => 'ID verification pending',
                'message' => 'MDRRMO is reviewing your uploaded ID. You will be notified when approved.',
                'url'     => base_url('profile'),
            ];
        } elseif (! drms_id_is_verified($idStatus)) {
            $alerts[] = [
                'level'   => 'warning',
                'title'   => 'ID not verified',
                'message' => 'Upload a clear government ID on your profile to be eligible for deployment.',
                'url'     => base_url('profile'),
            ];
        }

        if ((int) ($overview['active_assignments'] ?? 0) > 0) {
            $alerts[] = [
                'level'   => 'danger',
                'title'   => 'Active deployment',
                'message' => 'You have ' . (int) $overview['active_assignments'] . ' active assignment(s). Check details below and contact MDRRMO if you need support.',
                'url'     => base_url('admin/matching'),
            ];
        }

        if ((int) ($overview['fatigue_level'] ?? 0) >= 70) {
            $alerts[] = [
                'level'   => 'warning',
                'title'   => 'High fatigue level',
                'message' => 'Your fatigue score is elevated. Rest when possible and update availability if you need a break.',
                'url'     => base_url('profile'),
            ];
        }

        if ((int) ($overview['critical_incidents'] ?? 0) > 0) {
            $alerts[] = [
                'level'   => 'danger',
                'title'   => 'Critical incidents active',
                'message' => (int) $overview['critical_incidents'] . ' critical incident(s) in the service area. Stay alert for deployment requests.',
                'url'     => base_url('admin/matching'),
            ];
        }

        foreach ($this->notifications->unread($userId, 3) as $note) {
            $alerts[] = [
                'level'   => 'info',
                'title'   => (string) ($note['title'] ?? 'Notification'),
                'message' => (string) ($note['body'] ?? ''),
                'url'     => ! empty($note['url']) ? (string) $note['url'] : base_url('profile'),
            ];
        }

        return $alerts;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listAssignmentsForUser(int $userId, int $limit = 15): array
    {
        $volunteer = $this->findVolunteerForUser($userId);
        if ($volunteer === null) {
            return [];
        }

        return (new VolunteerDeploymentService())->listForVolunteer((int) $volunteer['id'], $limit);
    }

    /**
     * @return array{labels:list<string>, values:list<int>}
     */
    public function deploymentsChart7d(int $userId): array
    {
        $labels = [];
        $values = [];

        for ($i = 6; $i >= 0; $i--) {
            $day      = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = date('D', strtotime($day));
            $values[] = 0;
        }

        $volunteer = $this->findVolunteerForUser($userId);
        if ($volunteer === null) {
            return ['labels' => $labels, 'values' => $values];
        }

        try {
            $volunteerId = (int) $volunteer['id'];
            for ($i = 6; $i >= 0; $i--) {
                $day = date('Y-m-d', strtotime("-{$i} days"));
                $cnt = $this->deployments->builder()
                    ->where('volunteer_id', $volunteerId)
                    ->where('DATE(deployed_at)', $day)
                    ->countAllResults();
                $values[6 - $i] = (int) $cnt;
            }
        } catch (Throwable $e) {
            log_message('error', 'VolunteerDashboardService::deploymentsChart7d — ' . $e->getMessage());
        }

        return ['labels' => $labels, 'values' => $values];
    }

    private function countActiveIncidents(): int
    {
        return $this->incidents
            ->whereIn('status', ['reported', 'verified', 'in_progress'])
            ->countAllResults();
    }

    private function countCriticalIncidents(): int
    {
        return $this->incidents
            ->where('severity', 'critical')
            ->whereIn('status', ['reported', 'verified', 'in_progress'])
            ->countAllResults();
    }

    private function availabilityLabel(string $status): string
    {
        return match ($status) {
            'available' => 'Available',
            'on_duty'   => 'On duty',
            'deployed'  => 'Deployed',
            'unavailable', 'off_duty' => 'Unavailable',
            default     => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}
