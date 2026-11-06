<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\IncidentModel;
use App\Models\VolunteerDeploymentModel;
use App\Models\VolunteerModel;
use RuntimeException;

/**
 * Deploy volunteers to incidents and trigger notifications.
 */
class VolunteerDeploymentService
{
    public function __construct(
        private VolunteerModel $volunteers = new VolunteerModel(),
        private VolunteerDeploymentModel $deployments = new VolunteerDeploymentModel(),
        private IncidentModel $incidents = new IncidentModel(),
        private NotificationService $notifications = new NotificationService(),
        private AuditLogService $audit = new AuditLogService(),
    ) {
    }

    public function deploy(int $volunteerId, int $incidentId, ?int $deployedByUserId = null, ?string $notes = null): array
    {
        $volunteer = $this->volunteers->find($volunteerId);
        $incident  = $this->incidents->find($incidentId);

        if (! $volunteer || ! $incident) {
            throw new RuntimeException('Volunteer or incident not found.');
        }

        if (($volunteer['availability_status'] ?? '') === 'deployed') {
            throw new RuntimeException('Volunteer is already deployed.');
        }

        $this->deployments->insert([
            'volunteer_id'  => $volunteerId,
            'incident_id'   => $incidentId,
            'status'        => 'deployed',
            'deployed_at'   => date('Y-m-d H:i:s'),
            'notes'         => $notes,
        ]);

        $this->volunteers->update($volunteerId, [
            'availability_status' => 'deployed',
            'fatigue_level'       => min(100, (int) ($volunteer['fatigue_level'] ?? 0) + 15),
        ]);

        $deploymentId = (int) $this->deployments->getInsertID();
        $this->audit->log('volunteer.deploy', 'volunteer_deployment', $deploymentId, [
            'volunteer_id' => $volunteerId,
            'incident_id'  => $incidentId,
        ]);

        $this->notifications->onVolunteerDeployed($volunteer, $incident);

        return [
            'deployment_id' => $deploymentId,
            'volunteer'     => $volunteer,
            'incident'      => $incident,
        ];
    }

    /**
     * @param list<int> $volunteerIds
     * @return array{deployed:int, skipped:int, errors:list<string>}
     */
    public function deployMany(array $volunteerIds, int $incidentId, ?int $deployedByUserId = null): array
    {
        $deployed = 0;
        $skipped  = 0;
        $errors   = [];

        foreach (array_unique(array_filter($volunteerIds)) as $vid) {
            $volunteerId = (int) $vid;
            if ($volunteerId < 1) {
                continue;
            }
            try {
                $this->deploy($volunteerId, $incidentId, $deployedByUserId);
                $deployed++;
            } catch (RuntimeException $e) {
                $skipped++;
                $errors[] = $e->getMessage();
            }
        }

        return ['deployed' => $deployed, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function timelineForIncident(int $incidentId): array
    {
        $rows = $this->deployments
            ->where('incident_id', $incidentId)
            ->orderBy('deployed_at', 'DESC')
            ->findAll(50);

        if ($rows === []) {
            return [];
        }

        $volunteerIds = array_values(array_unique(array_map(static fn (array $r): int => (int) ($r['volunteer_id'] ?? 0), $rows)));
        $names        = [];
        foreach ($this->volunteers->whereIn('id', $volunteerIds)->findAll() as $v) {
            $names[(int) $v['id']] = (string) ($v['full_name'] ?? 'Volunteer');
        }

        $timeline = [];
        foreach ($rows as $row) {
            $vid = (int) ($row['volunteer_id'] ?? 0);
            $timeline[] = [
                'id'           => (int) ($row['id'] ?? 0),
                'volunteer_id' => $vid,
                'full_name'    => $names[$vid] ?? 'Volunteer #' . $vid,
                'status'       => (string) ($row['status'] ?? ''),
                'deployed_at'  => (string) ($row['deployed_at'] ?? $row['created_at'] ?? ''),
            ];
        }

        return $timeline;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForVolunteerIncident(int $volunteerId, int $incidentId): ?array
    {
        if ($volunteerId < 1 || $incidentId < 1) {
            return null;
        }

        return $this->deployments
            ->where('volunteer_id', $volunteerId)
            ->where('incident_id', $incidentId)
            ->orderBy('deployed_at', 'DESC')
            ->first() ?: null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForVolunteer(int $volunteerId, int $limit = 20): array
    {
        helper('drms');
        $rows = $this->deployments
            ->where('volunteer_id', $volunteerId)
            ->orderBy('deployed_at', 'DESC')
            ->findAll($limit);

        if ($rows === []) {
            return [];
        }

        $incidentIds = array_values(array_unique(array_map(static fn (array $r): int => (int) ($r['incident_id'] ?? 0), $rows)));
        $incidents   = [];
        foreach ($this->incidents->whereIn('id', $incidentIds)->findAll() as $inc) {
            $incidents[(int) $inc['id']] = $inc;
        }

        $out = [];
        foreach ($rows as $row) {
            $incidentId = (int) ($row['incident_id'] ?? 0);
            $incident   = $incidents[$incidentId] ?? [];
            $out[]      = [
                'deployment_id'  => (int) ($row['id'] ?? 0),
                'status'         => (string) ($row['status'] ?? ''),
                'deployed_at'    => drms_format_datetime($row['deployed_at'] ?? $row['created_at'] ?? null),
                'returned_at'    => drms_format_datetime($row['returned_at'] ?? null),
                'notes'          => (string) ($row['notes'] ?? ''),
                'incident_id'    => $incidentId,
                'reference_code' => (string) ($incident['reference_code'] ?? ''),
                'title'          => (string) ($incident['title'] ?? ''),
                'incident_type'  => (string) ($incident['incident_type'] ?? ''),
                'severity'       => (string) ($incident['severity'] ?? ''),
                'incident_status'=> (string) ($incident['status'] ?? ''),
                'barangay_id'    => $incident['barangay_id'] ?? null,
            ];
        }

        return $out;
    }
}
