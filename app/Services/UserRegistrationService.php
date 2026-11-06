<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\VolunteerModel;
use CodeIgniter\Shield\Entities\User;

class UserRegistrationService
{
    /** All public self-registrations start as pending volunteers; admin assigns operational roles later. */
    public function handleRegisteredUser(User $user): void
    {
        $user->syncGroups('volunteer');
    }

    /**
     * Create volunteer row when admin promotes a user to the volunteer role.
     */
    public function ensureVolunteerProfile(int $userId, ?int $barangayId = null): void
    {
        $model    = model(VolunteerModel::class);
        $existing = $model->where('user_id', $userId)->first();

        $user    = model(\CodeIgniter\Shield\Models\UserModel::class)->findById($userId);
        $profile = (new UserProfileService())->getProfileRow($userId) ?? [];

        if ($barangayId === null && ! empty($profile['barangay_id'])) {
            $barangayId = (int) $profile['barangay_id'];
        }

        $fullName = trim((string) ($profile['full_name'] ?? $user->username ?? 'Volunteer'));
        $phone    = trim((string) ($profile['phone'] ?? ''));
        $address  = trim((string) ($profile['address'] ?? ''));

        if ($existing) {
            $updates = [];
            if ($barangayId !== null && empty($existing['barangay_id'])) {
                $updates['barangay_id'] = $barangayId;
            }
            if ($fullName !== '' && empty($existing['full_name'])) {
                $updates['full_name'] = $fullName;
            }

            $idPath   = trim((string) ($profile['id_document_path'] ?? ''));
            $idStatus = strtolower((string) ($profile['id_verification_status'] ?? 'none'));
            if ($idPath !== '' && empty($existing['id_verification_path'])) {
                $updates['id_verification_path']   = $idPath;
                $updates['id_verification_status']   = in_array($idStatus, ['approved', 'pending', 'rejected'], true)
                    ? $idStatus
                    : 'pending';
            }

            if ($updates !== []) {
                $model->update($existing['id'], $updates);
            }

            return;
        }

        $idStatus = (string) ($profile['id_verification_status'] ?? 'none');
        $idPath   = (string) ($profile['id_document_path'] ?? '');

        $model->insert([
            'user_id'                => $userId,
            'full_name'              => $fullName,
            'email'                  => (string) ($user->email ?? ''),
            'phone'                  => $phone,
            'address'                => $address,
            'barangay_id'            => $barangayId,
            'availability_status'    => 'available',
            'experience_years'       => 0,
            'fatigue_level'          => 0,
            'id_verification_path'   => $idPath !== '' ? $idPath : null,
            'id_verification_status' => $idPath !== '' ? ($idStatus !== 'none' ? $idStatus : 'pending') : 'none',
        ]);
    }
}
