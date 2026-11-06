<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\VolunteerModel;
use App\Models\VolunteerSkillRequestModel;
use Config\DrmsFeatures;
use RuntimeException;
use Throwable;

class VolunteerSkillRequestService
{
    public function __construct(
        private VolunteerSkillRequestModel $requests = new VolunteerSkillRequestModel(),
        private VolunteerSkillService $skills = new VolunteerSkillService(),
        private DrmsFeatures $features = new DrmsFeatures(),
        private AuditLogService $audit = new AuditLogService(),
    ) {
    }

    /**
     * @param list<string> $skillNames
     * @return array<string, mixed>
     */
    public function submit(int $volunteerId, int $userId, array $skillNames): array
    {
        if (! $this->isReady()) {
            throw new RuntimeException('Skill requests are not available yet. Ask the administrator to run database migrations.');
        }

        $volunteer = model(VolunteerModel::class)->find($volunteerId);
        if (! $volunteer) {
            throw new RuntimeException('Volunteer profile not found.');
        }

        $normalized = $this->normalizeCatalogSkills($skillNames);
        if ($normalized === []) {
            throw new RuntimeException('Select at least one skill from the approved list.');
        }

        $current = $this->skillNamesForVolunteer($volunteerId);
        sort($current);
        $sortedNew = $normalized;
        sort($sortedNew);
        if ($current === $sortedNew) {
            throw new RuntimeException('Your requested skills match what is already approved. No change needed.');
        }

        $pending = $this->getPendingForVolunteer($volunteerId);
        if ($pending) {
            throw new RuntimeException('You already have a skill change waiting for admin approval.');
        }

        $payload = [
            'volunteer_id'          => $volunteerId,
            'user_id'               => $userId,
            'requested_skills_json' => json_encode($normalized),
            'previous_skills_json'  => json_encode($current),
            'status'                => 'pending',
        ];
        if (! $this->requests->insert($payload)) {
            throw new RuntimeException('Could not save your skill request.');
        }

        $id = (int) $this->requests->getInsertID();
        $this->audit->log('volunteer.skill_request', 'volunteer_skill_request', $id, [
            'volunteer_id' => $volunteerId,
            'skills'       => $normalized,
        ]);

        return $this->requests->find($id) ?? $payload;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPendingForVolunteer(int $volunteerId): ?array
    {
        if (! $this->isReady()) {
            return null;
        }

        try {
            $row = $this->requests
                ->where('volunteer_id', $volunteerId)
                ->where('status', 'pending')
                ->orderBy('id', 'DESC')
                ->first();

            return $row ? $this->hydrateRow($row) : null;
        } catch (Throwable $e) {
            log_message('error', 'VolunteerSkillRequestService::getPendingForVolunteer — ' . $e->getMessage());

            return null;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPending(int $limit = 50): array
    {
        if (! $this->isReady()) {
            return [];
        }

        try {
            $rows = $this->requests
                ->where('status', 'pending')
                ->orderBy('id', 'ASC')
                ->findAll($limit);
        } catch (Throwable $e) {
            log_message('error', 'VolunteerSkillRequestService::listPending — ' . $e->getMessage());

            return [];
        }

        if ($rows === []) {
            return [];
        }

        $volunteerIds = array_values(array_unique(array_map(
            static fn (array $r): int => (int) ($r['volunteer_id'] ?? 0),
            $rows
        )));
        $names = [];
        foreach (model(VolunteerModel::class)->whereIn('id', $volunteerIds)->findAll() as $v) {
            $names[(int) $v['id']] = (string) ($v['full_name'] ?? 'Volunteer');
        }

        $out = [];
        foreach ($rows as $row) {
            $hydrated = $this->hydrateRow($row);
            $hydrated['volunteer_name'] = $names[(int) ($row['volunteer_id'] ?? 0)] ?? 'Volunteer';
            $out[] = $hydrated;
        }

        return $out;
    }

    public function approve(int $requestId, ?int $reviewerId = null, ?string $notes = null): void
    {
        $row = $this->requests->find($requestId);
        if (! $row || ($row['status'] ?? '') !== 'pending') {
            throw new RuntimeException('Skill request not found or already reviewed.');
        }

        $volunteerId = (int) ($row['volunteer_id'] ?? 0);
        $skills      = $this->decodeSkills((string) ($row['requested_skills_json'] ?? '[]'));
        $this->skills->syncSkills($volunteerId, $skills);

        $this->requests->update($requestId, [
            'status'         => 'approved',
            'reviewer_id'    => $reviewerId,
            'reviewer_notes' => $notes,
            'reviewed_at'    => date('Y-m-d H:i:s'),
        ]);

        $this->audit->log('volunteer.skill_request.approved', 'volunteer_skill_request', $requestId, [
            'volunteer_id' => $volunteerId,
            'skills'       => $skills,
        ]);
    }

    public function reject(int $requestId, ?int $reviewerId = null, ?string $notes = null): void
    {
        $row = $this->requests->find($requestId);
        if (! $row || ($row['status'] ?? '') !== 'pending') {
            throw new RuntimeException('Skill request not found or already reviewed.');
        }

        $this->requests->update($requestId, [
            'status'         => 'rejected',
            'reviewer_id'    => $reviewerId,
            'reviewer_notes' => $notes,
            'reviewed_at'    => date('Y-m-d H:i:s'),
        ]);

        $this->audit->log('volunteer.skill_request.rejected', 'volunteer_skill_request', $requestId, [
            'volunteer_id' => (int) ($row['volunteer_id'] ?? 0),
        ]);
    }

    /**
     * @return list<string>
     */
    public function skillNamesForVolunteer(int $volunteerId): array
    {
        return array_values(array_map(
            static fn (array $row): string => (string) ($row['skill_name'] ?? ''),
            $this->skills->listForVolunteer($volunteerId)
        ));
    }

    /**
     * @param list<string> $volunteerSkillNames
     * @param list<string> $requiredSkillNames
     */
    public function incidentMatchesVolunteerSkills(array $requiredSkillNames, array $volunteerSkillNames): bool
    {
        if ($requiredSkillNames === []) {
            return true;
        }
        if ($volunteerSkillNames === []) {
            return false;
        }

        foreach ($requiredSkillNames as $req) {
            foreach ($volunteerSkillNames as $skill) {
                if (strcasecmp($req, $skill) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param list<string> $skillNames
     * @return list<string>
     */
    public function normalizeCatalogSkills(array $skillNames): array
    {
        $allowed = $this->features->volunteerSkills;
        $out     = [];
        foreach ($this->skills->normalizeSkillNames($skillNames) as $name) {
            foreach ($allowed as $catalog) {
                if (strcasecmp($catalog, $name) === 0) {
                    $out[] = $catalog;
                    break;
                }
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @return array<string, mixed>
     */
    private function hydrateRow(array $row): array
    {
        $row['requested_skills'] = $this->decodeSkills((string) ($row['requested_skills_json'] ?? '[]'));
        $row['previous_skills']  = $this->decodeSkills((string) ($row['previous_skills_json'] ?? '[]'));

        return $row;
    }

    /**
     * @return list<string>
     */
    private function decodeSkills(string $json): array
    {
        $decoded = json_decode($json, true);

        return is_array($decoded) ? array_values($decoded) : [];
    }

    private function isReady(): bool
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }

        try {
            $ready = db_connect()->tableExists('volunteer_skill_requests');
        } catch (Throwable) {
            $ready = false;
        }

        return $ready;
    }
}
