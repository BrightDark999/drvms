<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\UserProfileModel;
use CodeIgniter\HTTP\Files\UploadedFile;

class UserProfileService
{
    private const ID_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
    private const PHOTO_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct(
        private UserProfileModel $profiles = new UserProfileModel(),
        private FileUploadService $uploader = new FileUploadService(),
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getProfileRow(int $userId): ?array
    {
        return $this->profiles->where('user_id', $userId)->first();
    }

    public function getBarangayIdForUser(int $userId): ?int
    {
        $row = $this->getProfileRow($userId);
        if ($row && ! empty($row['barangay_id'])) {
            return (int) $row['barangay_id'];
        }

        $vol = model(\App\Models\VolunteerModel::class)->where('user_id', $userId)->first();
        if ($vol && ! empty($vol['barangay_id'])) {
            return (int) $vol['barangay_id'];
        }

        return null;
    }

    public function getAssignedEvacuationCenterId(int $userId): ?int
    {
        $row = $this->getProfileRow($userId);
        $id  = (int) ($row['assigned_evacuation_center_id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    public function setBarangay(int $userId, ?int $barangayId): void
    {
        $this->ensureProfile($userId, ['barangay_id' => $barangayId]);
    }

    /**
     * @param array<string, mixed> $fields
     */
    public function ensureProfile(int $userId, array $fields = []): void
    {
        $row = $this->getProfileRow($userId);
        $payload = array_intersect_key($fields, array_flip([
            'full_name', 'phone', 'address', 'barangay_id', 'assigned_evacuation_center_id', 'profile_photo',
            'id_document_path', 'id_verification_status',
        ]));

        if ($row) {
            if ($payload !== []) {
                $this->profiles->update($row['id'], $payload);
            }

            return;
        }

        $payload['user_id'] = $userId;
        $this->profiles->insert($payload);
    }

    /**
     * @param array<string, mixed> $fields
     */
    public function updateFields(int $userId, array $fields): void
    {
        $allowed = ['full_name', 'phone', 'address', 'barangay_id', 'assigned_evacuation_center_id'];
        $payload   = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $fields)) {
                $payload[$key] = in_array($key, ['barangay_id', 'assigned_evacuation_center_id'], true)
                    ? ($fields[$key] !== '' && $fields[$key] !== null ? (int) $fields[$key] : null)
                    : trim((string) $fields[$key]);
            }
        }

        if ($payload === []) {
            return;
        }

        $this->ensureProfile($userId, $payload);
    }

    public function storeIdDocument(int $userId, UploadedFile $file, string $status = 'pending'): string
    {
        $meta = $this->uploader->store($file, 'id-verification', self::ID_MIMES, 4096);
        $this->ensureProfile($userId, [
            'id_document_path'       => $meta['path'],
            'id_verification_status' => $status,
        ]);

        return $meta['path'];
    }

    public function storeProfilePhoto(int $userId, UploadedFile $file): string
    {
        $meta = $this->uploader->store($file, 'profile-photos', self::PHOTO_MIMES, 2048);
        $this->ensureProfile($userId, ['profile_photo' => $meta['path']]);

        return $meta['path'];
    }

    public function verifyIdDocument(int $userId): void
    {
        $row = $this->getProfileRow($userId);
        if (! $row || empty($row['id_document_path'])) {
            throw new \RuntimeException('No ID document on file.');
        }

        $this->profiles->update($row['id'], ['id_verification_status' => 'approved']);
    }

    public function rejectIdDocument(int $userId): void
    {
        $row = $this->getProfileRow($userId);
        if (! $row) {
            throw new \RuntimeException('Profile not found.');
        }

        $this->profiles->update($row['id'], ['id_verification_status' => 'rejected']);
    }
}
