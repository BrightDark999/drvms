<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\VolunteerModel;
use RuntimeException;

class VolunteerService
{
    public function __construct(
        private VolunteerModel $volunteers = new VolunteerModel(),
        private AuditLogService $audit = new AuditLogService(),
        private VolunteerSkillService $skills = new VolunteerSkillService(),
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $row = $this->mapFields($data);
        if (! $this->volunteers->insert($row)) {
            throw new RuntimeException(implode(' ', $this->volunteers->errors()));
        }
        $id = (int) $this->volunteers->getInsertID();
        $this->syncSkillsFromData($id, $data);
        $this->audit->log('volunteer.create', 'volunteer', $id);

        return $this->findWithSkills($id) ?? $row;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        $existing = $this->volunteers->find($id);
        if (! $existing) {
            return false;
        }

        $merged = array_merge($existing, $data);
        $ok     = $this->volunteers->update($id, $this->mapFields($merged));
        if ($ok) {
            $this->syncSkillsFromData($id, $data);
            $this->audit->log('volunteer.update', 'volunteer', $id);
        }

        return $ok;
    }

    public function delete(int $id): bool
    {
        if (! $this->volunteers->find($id)) {
            return false;
        }
        $ok = (bool) $this->volunteers->delete($id);
        if ($ok) {
            $this->audit->log('volunteer.delete', 'volunteer', $id);
        }

        return $ok;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function mapFields(array $data): array
    {
        return [
            'full_name'           => trim((string) ($data['full_name'] ?? '')),
            'email'               => trim((string) ($data['email'] ?? '')),
            'phone'               => trim((string) ($data['phone'] ?? '')),
            'barangay_id'         => $this->nullableInt($data['barangay_id'] ?? null),
            'address'             => trim((string) ($data['address'] ?? '')),
            'availability_status' => strtolower((string) ($data['availability_status'] ?? 'available')),
            'experience_years'    => max(0, (int) ($data['experience_years'] ?? 0)),
            'fatigue_level'       => min(100, max(0, (int) ($data['fatigue_level'] ?? 0))),
            'preferred_hours'     => in_array((int) ($data['preferred_hours'] ?? 0), [8, 12, 24], true) ? (int) $data['preferred_hours'] : 8,
            'volunteer_type'      => in_array((string) ($data['volunteer_type'] ?? ''), ['individual', 'group'], true) ? (string) $data['volunteer_type'] : 'individual',
            'notes'               => trim((string) ($data['notes'] ?? '')),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findWithSkills(int $id): ?array
    {
        $row = $this->volunteers->find($id);
        if (! $row) {
            return null;
        }
        $row           = $this->mergeLinkedProfileId($row, true);
        $row['skills'] = $this->skills->listForVolunteer($id);

        return $row;
    }

    /**
     * Resolve ID path/status from linked user profile when volunteer row is empty.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function mergeLinkedProfileId(array $row, bool $persistIfMissing = false): array
    {
        $path   = trim((string) ($row['id_verification_path'] ?? ''));
        $status = strtolower((string) ($row['id_verification_status'] ?? 'none'));
        $userId = (int) ($row['user_id'] ?? 0);

        if ($userId <= 0) {
            return $row;
        }

        $profile = (new UserProfileService())->getProfileRow($userId);
        if ($profile === null) {
            return $row;
        }

        $profilePath   = trim((string) ($profile['id_document_path'] ?? ''));
        $profileStatus = strtolower((string) ($profile['id_verification_status'] ?? 'none'));

        if ($profilePath === '') {
            return $row;
        }

        $sync = [];
        if ($path === '') {
            $path        = $profilePath;
            $row['id_verification_path'] = $profilePath;
            $sync['id_verification_path'] = $profilePath;
        }
        if ($status === 'none' || $status === '') {
            $status = in_array($profileStatus, ['approved', 'pending', 'rejected'], true)
                ? $profileStatus
                : 'pending';
            $row['id_verification_status'] = $status;
            $sync['id_verification_status'] = $status;
        }

        if ($persistIfMissing && $sync !== [] && ! empty($row['id'])) {
            $this->volunteers->update((int) $row['id'], $sync);
        }

        return $row;
    }

    /**
     * @return array{path: string, status: string}
     */
    public function resolveIdDisplayFields(array $row): array
    {
        $row = $this->mergeLinkedProfileId($row, false);

        return [
            'path'   => trim((string) ($row['id_verification_path'] ?? '')),
            'status' => strtolower((string) ($row['id_verification_status'] ?? 'none')),
        ];
    }

    public function storeIdDocument(int $volunteerId, \CodeIgniter\HTTP\Files\UploadedFile $file): string
    {
        $row = $this->volunteers->find($volunteerId);
        if (! $row) {
            throw new RuntimeException('Volunteer not found.');
        }

        $uploader = new FileUploadService();
        $meta     = $uploader->store($file, 'id-verification', ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], 4096);
        $this->volunteers->update($volunteerId, [
            'id_verification_path'   => $meta['path'],
            'id_verification_status' => 'pending',
        ]);

        if (! empty($row['user_id'])) {
            (new UserProfileService())->ensureProfile((int) $row['user_id'], [
                'id_document_path'       => $meta['path'],
                'id_verification_status' => 'pending',
            ]);
        }

        return $meta['path'];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function syncSkillsFromData(int $volunteerId, array $data): void
    {
        if (! array_key_exists('skills', $data) && ! array_key_exists('skill_names', $data)) {
            return;
        }
        $raw = $data['skills'] ?? $data['skill_names'] ?? [];
        if (is_string($raw)) {
            $raw = explode(',', $raw);
        }
        if (is_array($raw)) {
            $this->skills->syncSkills($volunteerId, $this->skills->normalizeSkillNames($raw));
        }
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
