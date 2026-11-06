<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\IncidentModel;
use RuntimeException;

/**
 * Business rules for disaster incidents (reference codes, verification).
 */
class IncidentService
{
    public function __construct(
        private IncidentModel $incidents = new IncidentModel(),
        private AuditLogService $audit = new AuditLogService(),
        private NotificationService $notifications = new NotificationService(),
        private IncidentClassificationService $classifier = new IncidentClassificationService(),
        private IncidentGeoService $geo = new IncidentGeoService(),
    ) {
    }

    /**
     * @param array<string, mixed> $data title, description, severity, incident_type, barangay_id, latitude, longitude
     * @return array<string, mixed> inserted row
     */
    public function create(array $data, ?int $reportedByUserId = null): array
    {
        $reference = $this->generateReferenceCode();
        $data      = $this->classifier->enrich($data);

        $row = [
            'reference_code'      => $reference,
            'title'               => trim((string) ($data['title'] ?? '')),
            'description'         => trim((string) ($data['description'] ?? '')),
            'incident_type'       => (string) ($data['incident_type'] ?? 'other'),
            'severity'            => strtolower((string) ($data['severity'] ?? 'moderate')),
            'priority_tag'        => (string) ($data['priority_tag'] ?? 'MEDIUM'),
            'needs_rescue'        => (int) ($data['needs_rescue'] ?? 0),
            'needs_medical'       => (int) ($data['needs_medical'] ?? 0),
            'needs_drivers'       => (int) ($data['needs_drivers'] ?? 0),
            'required_skills_json'=> $data['required_skills_json'] ?? null,
            'status'              => 'reported',
            'latitude'            => $this->nullableFloat($data['latitude'] ?? null),
            'longitude'           => $this->nullableFloat($data['longitude'] ?? null),
            'barangay_id'         => $this->nullableInt($data['barangay_id'] ?? null),
            'reported_by_user_id' => $reportedByUserId,
        ];

        $row = $this->geo->enrichPayload($row);

        if (! $this->incidents->insert($row)) {
            throw new RuntimeException(implode(' ', $this->incidents->errors()));
        }

        $id = (int) $this->incidents->getInsertID();
        $this->audit->log('incident.create', 'incident', $id, ['reference' => $reference]);

        $saved = $this->incidents->find($id);
        $saved = is_array($saved) ? $saved : $row;

        $snapshot = $saved;
        DeferredService::afterResponse(static function () use ($snapshot): void {
            $notifications = new NotificationService();
            $notifications->beginBatch();
            try {
                $notifications->onIncidentCreated($snapshot);
                (new NearbyVolunteerAlertService())->alertForIncident($snapshot);
                $notifications->onEvacuationAdvisory($snapshot);
            } finally {
                $notifications->endBatch();
            }
        });

        return $saved;
    }

    /**
     * Mark incident as verified (MDRRMO / LGU workflow).
     */
    public function verify(int $incidentId, int $verifiedByUserId): bool
    {
        $incident = $this->incidents->find($incidentId);
        if (! $incident) {
            return false;
        }

        $ok = $this->incidents->update($incidentId, [
            'status'              => 'verified',
            'verified_by_user_id' => $verifiedByUserId,
            'verified_at'         => date('Y-m-d H:i:s'),
        ]);

        if ($ok) {
            $this->audit->log('incident.verify', 'incident', $incidentId);
            $updated = $this->incidents->find($incidentId);
            if (is_array($updated)) {
                $this->notifications->onIncidentVerified($updated);
            }
        }

        return $ok;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        if (! $this->incidents->find($id)) {
            return false;
        }

        $data = $this->classifier->enrich(array_merge($this->incidents->find($id) ?? [], $data));

        $row = [
            'title'               => trim((string) ($data['title'] ?? '')),
            'description'         => trim((string) ($data['description'] ?? '')),
            'incident_type'       => (string) ($data['incident_type'] ?? 'other'),
            'severity'            => strtolower((string) ($data['severity'] ?? 'moderate')),
            'priority_tag'        => (string) ($data['priority_tag'] ?? 'MEDIUM'),
            'needs_rescue'        => (int) ($data['needs_rescue'] ?? 0),
            'needs_medical'       => (int) ($data['needs_medical'] ?? 0),
            'needs_drivers'       => (int) ($data['needs_drivers'] ?? 0),
            'required_skills_json'=> $data['required_skills_json'] ?? null,
            'status'              => strtolower((string) ($data['status'] ?? 'reported')),
            'latitude'    => $this->nullableFloat($data['latitude'] ?? null),
            'longitude'   => $this->nullableFloat($data['longitude'] ?? null),
            'barangay_id' => $this->nullableInt($data['barangay_id'] ?? null),
        ];

        $ok = $this->incidents->update($id, $row);
        if ($ok) {
            $this->audit->log('incident.update', 'incident', $id);
        }

        return $ok;
    }

    public function delete(int $id): bool
    {
        if (! $this->incidents->find($id)) {
            return false;
        }

        $ok = (bool) $this->incidents->delete($id);
        if ($ok) {
            $this->audit->log('incident.delete', 'incident', $id);
        }

        return $ok;
    }

    /**
     * Incidents submitted by a logged-in resident/volunteer (profile page).
     *
     * @return list<array<string, mixed>>
     */
    public function listByReporter(int $userId, int $limit = 20): array
    {
        if ($userId <= 0) {
            return [];
        }

        $limit = min(50, max(1, $limit));
        $rows  = $this->incidents->builder()
            ->select('incidents.*, barangays.name AS barangay_name')
            ->join('barangays', 'barangays.id = incidents.barangay_id', 'left')
            ->where('incidents.reported_by_user_id', $userId)
            ->where('incidents.deleted_at', null)
            ->orderBy('incidents.id', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();

        $mediaSvc = new IncidentMediaService();
        foreach ($rows as &$row) {
            $id           = (int) ($row['id'] ?? 0);
            $row          = $this->formatApiRow($row, $mediaSvc->countForIncident($id));
            $row['media'] = $mediaSvc->formatForApi($mediaSvc->listForIncident($id));
        }
        unset($row);

        return $rows;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function formatApiRow(array $row, ?int $mediaCount = null): array
    {
        helper('drms');

        $id = (int) ($row['id'] ?? 0);
        if ($mediaCount === null) {
            $mediaCount = (new IncidentMediaService())->countForIncident($id);
        }

        $row['reported_at']  = drms_format_datetime((string) ($row['created_at'] ?? ''));
        $row['created_at']   = drms_format_datetime((string) ($row['created_at'] ?? '')) ?: (string) ($row['created_at'] ?? '');
        $row['media_count']  = $mediaCount;
        $row['has_media']    = $mediaCount > 0;
        $row['barangay_name'] = (string) ($row['barangay_name'] ?? '');

        return $row;
    }

    /**
     * @return array<string, mixed>
     */
    public function formatApiDetail(int $id): array
    {
        $row = $this->incidents->builder()
            ->select('incidents.*, barangays.name AS barangay_name')
            ->join('barangays', 'barangays.id = incidents.barangay_id', 'left')
            ->where('incidents.id', $id)
            ->where('incidents.deleted_at', null)
            ->get()
            ->getRowArray();

        if ($row === null) {
            throw new RuntimeException('Incident not found.');
        }

        $mediaSvc       = new IncidentMediaService();
        $media          = $mediaSvc->listForIncident($id);
        $row            = $this->formatApiRow($row, count($media));
        $row['media']   = $mediaSvc->formatForApi($media);

        return $row;
    }

    private function generateReferenceCode(): string
    {
        return 'INC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
