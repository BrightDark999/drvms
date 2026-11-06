<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\VolunteerModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Shield\Models\UserModel;

class ProfileService
{
    public function __construct(
        private UserModel $users = new UserModel(),
        private VolunteerModel $volunteers = new VolunteerModel(),
        private VolunteerSkillService $skills = new VolunteerSkillService(),
        private FileUploadService $uploader = new FileUploadService(),
        private UserProfileService $profiles = new UserProfileService(),
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getProfile(int $userId): array
    {
        $user = $this->users->findById($userId);
        if (! $user) {
            throw new \RuntimeException('User not found.');
        }

        $profileRow = $this->profiles->getProfileRow($userId) ?? [];
        $volunteer  = $this->resolveVolunteerForUser($userId, $user);
        $skillRequest = null;
        $approvedSkills = [];

        if ($volunteer) {
            $volunteerId    = (int) $volunteer['id'];
            $approvedSkills = (new VolunteerSkillRequestService())->skillNamesForVolunteer($volunteerId);
            if ($user->inGroup('volunteer')) {
                $skillRequest = (new VolunteerSkillRequestService())->getPendingForVolunteer($volunteerId);
            }
        }

        return [
            'user'      => [
                'id'       => (int) $user->id,
                'username' => (string) $user->username,
                'email'    => (string) $user->email,
            ],
            'groups'           => $user->getGroups(),
            'profile'          => $this->buildDisplayProfile($profileRow, $volunteer, $user),
            'volunteer'        => $volunteer,
            'is_volunteer'     => $volunteer !== null || $user->inGroup('volunteer'),
            'approved_skills'  => $approvedSkills,
            'skill_request'    => $skillRequest,
            'skills_locked'    => $user->inGroup('volunteer'),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getVolunteerRecord(int $userId): ?array
    {
        $user = $this->users->findById($userId);

        return $user ? $this->resolveVolunteerForUser($userId, $user) : null;
    }

    /**
     * Ensure volunteers table row exists for volunteer-role accounts.
     *
     * @return array<string, mixed>|null
     */
    private function resolveVolunteerForUser(int $userId, ?object $user = null): ?array
    {
        $user ??= $this->users->findById($userId);
        if (! $user || ! $user->inGroup('volunteer')) {
            return $this->volunteers->where('user_id', $userId)->first() ?: null;
        }

        (new UserRegistrationService())->ensureVolunteerProfile($userId);

        $volunteer = $this->volunteers->where('user_id', $userId)->first();
        if (! $volunteer) {
            return null;
        }

        $volunteer = (new VolunteerService())->mergeLinkedProfileId($volunteer, true);
        $volunteer['skills'] = $this->skills->listForVolunteer((int) $volunteer['id']);

        return $volunteer;
    }

    /**
     * Merge user_profiles, volunteer row, and account identity for form display.
     *
     * @param array<string, mixed> $profileRow
     * @param array<string, mixed>|null $volunteer
     * @return array<string, mixed>
     */
    private function buildDisplayProfile(array $profileRow, ?array $volunteer, object $user): array
    {
        $volPath  = $volunteer ? trim((string) ($volunteer['id_verification_path'] ?? '')) : '';
        $profPath = trim((string) ($profileRow['id_document_path'] ?? ''));
        $idPath   = $profPath !== '' ? $profPath : $volPath;
        $profStat = strtolower((string) ($profileRow['id_verification_status'] ?? 'none'));
        $volStat  = $volunteer ? strtolower((string) ($volunteer['id_verification_status'] ?? 'none')) : 'none';
        $idStatus = $profStat !== 'none' && $profStat !== '' ? $profStat : $volStat;

        $fullName = trim((string) ($profileRow['full_name'] ?? ''));
        if ($fullName === '') {
            $fullName = trim((string) ($volunteer['full_name'] ?? ''));
        }
        if ($fullName === '') {
            $fullName = (string) ($user->username ?? '');
        }

        $phone = trim((string) ($profileRow['phone'] ?? ''));
        if ($phone === '') {
            $phone = trim((string) ($volunteer['phone'] ?? ''));
        }

        $address = trim((string) ($profileRow['address'] ?? ''));
        if ($address === '') {
            $address = trim((string) ($volunteer['address'] ?? ''));
        }

        return [
            'full_name'              => $fullName,
            'phone'                  => $phone,
            'address'                => $address,
            'barangay_id'            => $profileRow['barangay_id'] ?? $volunteer['barangay_id'] ?? null,
            'profile_photo'          => (string) ($profileRow['profile_photo'] ?? ''),
            'id_document_path'       => $idPath,
            'id_verification_status' => $idStatus !== '' ? $idStatus : 'none',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateProfile(int $userId, array $data): void
    {
        $user = $this->users->findById($userId);
        if (! $user) {
            throw new \RuntimeException('User not found.');
        }

        $email = trim((string) ($data['email'] ?? $user->email ?? ''));
        if ($email === '') {
            throw new \RuntimeException('Email is required.');
        }

        $user->email = $email;
        if (! empty($data['password'])) {
            $user->password = (string) $data['password'];
        }
        if (! $this->users->save($user)) {
            throw new \RuntimeException(implode(' ', $this->users->errors()));
        }

        $profilePayload = [
            'full_name' => trim((string) ($data['full_name'] ?? '')),
            'phone'     => trim((string) ($data['phone'] ?? '')),
            'address'   => trim((string) ($data['address'] ?? '')),
        ];
        if ($profilePayload['full_name'] === '') {
            $profilePayload['full_name'] = (string) ($user->username ?? '');
        }
        $this->profiles->updateFields($userId, $profilePayload);

        $userEntity = $this->users->findById($userId);
        $volunteer = $userEntity ? $this->resolveVolunteerForUser($userId, $userEntity) : null;
        if ($volunteer) {
            $volunteerUpdate = [
                'full_name'        => $profilePayload['full_name'],
                'email'            => $email,
                'phone'            => $profilePayload['phone'],
                'address'          => $profilePayload['address'],
                'experience_years' => max(0, (int) ($data['experience_years'] ?? $volunteer['experience_years'] ?? 0)),
                'preferred_hours'  => in_array((int) ($data['preferred_hours'] ?? 0), [8, 12, 24], true) ? (int) $data['preferred_hours'] : ((int) ($volunteer['preferred_hours'] ?? 8) ?: 8),
                'fatigue_level'    => min(100, max(0, (int) ($data['fatigue_level'] ?? $data['preferred_hours'] ?? $volunteer['fatigue_level'] ?? 0))),
            ];

            if ($userEntity->inGroup('volunteer')) {
                unset($volunteerUpdate['skills']);
            } else {
                $volunteerUpdate['skills'] = is_array($data['skills'] ?? null) ? $data['skills'] : [];
            }

            if ($userEntity->inGroup('volunteer') && isset($data['availability_status'])) {
                $status  = strtolower(trim((string) $data['availability_status']));
                $current = strtolower((string) ($volunteer['availability_status'] ?? ''));
                if ($current === 'deployed') {
                    throw new \RuntimeException('You are currently deployed. Contact MDRRMO before changing availability.');
                }
                if (in_array($status, ['available', 'on_duty', 'unavailable'], true)) {
                    $volunteerUpdate['availability_status'] = $status;
                }
            }

            $ok = (new VolunteerService())->update((int) $volunteer['id'], $volunteerUpdate);
            if (! $ok) {
                throw new \RuntimeException('Could not save volunteer profile. Please try again.');
            }
        }
    }

    /**
     * Volunteer skill changes require MDRRMO / admin approval (not applied immediately).
     *
     * @param list<string> $skillNames
     */
    public function requestSkillChange(int $userId, array $skillNames): void
    {
        $user = $this->users->findById($userId);
        if (! $user || ! $user->inGroup('volunteer')) {
            throw new \RuntimeException('Only volunteers can request skill changes here.');
        }

        $volunteer = $this->resolveVolunteerForUser($userId, $user);
        if (! $volunteer) {
            throw new \RuntimeException('Volunteer profile could not be found. Save your profile details first.');
        }

        (new VolunteerSkillRequestService())->submit((int) $volunteer['id'], $userId, $skillNames);
    }

    /**
     * Volunteer self-service availability (available / on_duty / unavailable).
     */
    public function updateVolunteerAvailability(int $userId, string $status): array
    {
        $user = $this->users->findById($userId);
        if (! $user || ! $user->inGroup('volunteer')) {
            throw new \RuntimeException('Only volunteers can update availability here.');
        }

        $status = strtolower(trim($status));
        if (! in_array($status, ['available', 'on_duty', 'unavailable'], true)) {
            throw new \RuntimeException('Invalid availability status.');
        }

        $volunteer = $this->resolveVolunteerForUser($userId, $user);
        if (! $volunteer) {
            throw new \RuntimeException('Volunteer profile could not be created. Save your profile details first.');
        }

        $current = strtolower((string) ($volunteer['availability_status'] ?? ''));
        if ($current === 'deployed') {
            throw new \RuntimeException('You are currently deployed. Contact MDRRMO before changing availability.');
        }

        $volunteerId = (int) $volunteer['id'];
        $ok = (new VolunteerService())->update($volunteerId, [
            'availability_status' => $status,
        ]);
        if (! $ok) {
            throw new \RuntimeException('Could not update availability.');
        }

        (new AuditLogService())->log('volunteer.availability', 'volunteer', $volunteerId, [
            'from' => $current,
            'to'   => $status,
        ]);

        return $this->volunteers->find($volunteerId) ?? [];
    }

    public function uploadProfilePhoto(int $userId, UploadedFile $file): string
    {
        return $this->profiles->storeProfilePhoto($userId, $file);
    }

    public function uploadIdVerification(int $userId, UploadedFile $file): string
    {
        $path = $this->profiles->storeIdDocument($userId, $file, 'pending');

        $user = $this->users->findById($userId);
        $volunteer = $user ? $this->resolveVolunteerForUser($userId, $user) : null;
        if ($volunteer) {
            $this->volunteers->update($volunteer['id'], [
                'id_verification_path'   => $path,
                'id_verification_status' => 'pending',
            ]);
        }

        return $path;
    }
}
