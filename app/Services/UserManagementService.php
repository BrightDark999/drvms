<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Shield\Models\UserModel;
use RuntimeException;

class UserManagementService
{
    /** @var list<string> */
    private array $assignableGroups = [
        'resident', 'volunteer', 'donor', 'mdrrmo', 'evac_manager',
    ];

    /** @var list<string> */
    private array $accountStatuses = ['pending', 'approved', 'inactive', 'disabled', 'rejected'];

    public function __construct(
        private UserModel $users = new UserModel(),
        private UserProfileService $profiles = new UserProfileService(),
    ) {
    }

    /**
     * @return array{rows:list<array<string,mixed>>, total:int, filtered:int}
     */
    public function listForApi(
        int $limit = 50,
        int $offset = 0,
        ?string $statusFilter = null,
        ?string $groupFilter = null,
        string $search = '',
    ): array {
        $limit  = min(100, max(1, $limit));
        $offset = max(0, $offset);
        $result = $this->fetchTable($search, $offset, $limit, $statusFilter, $groupFilter);

        $rows = [];
        foreach ($result['rows'] as $row) {
            $rows[] = $this->formatApiSummary($row);
        }

        return [
            'data' => $rows,
            'meta' => [
                'count'    => count($rows),
                'total'    => $result['total'],
                'filtered' => $result['filtered'],
                'limit'    => $limit,
                'offset'   => $offset,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function formatApiSummary(array $row): array
    {
        helper('drms');

        return [
            'id'                      => (int) ($row['id'] ?? 0),
            'username'                => (string) ($row['username'] ?? ''),
            'email'                   => (string) ($row['email'] ?? ''),
            'role'                    => (string) ($row['group'] ?? ''),
            'account_status'          => (string) ($row['account_status'] ?? ''),
            'id_verification_status'  => (string) ($row['id_verification_status'] ?? 'none'),
            'active'                  => (bool) ($row['active'] ?? false),
            'created_at'              => drms_format_datetime((string) ($row['created_at'] ?? '')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function formatApiDetail(array $user): array
    {
        helper('drms');

        return [
            'id'                      => (int) ($user['id'] ?? 0),
            'username'                => (string) ($user['username'] ?? ''),
            'email'                   => (string) ($user['email'] ?? ''),
            'role'                    => (string) ($user['group'] ?? ''),
            'group'                   => (string) ($user['group'] ?? ''),
            'account_status'          => (string) ($user['account_status'] ?? ''),
            'status_message'          => (string) ($user['status_message'] ?? ''),
            'full_name'               => (string) ($user['full_name'] ?? ''),
            'phone'                   => (string) ($user['phone'] ?? ''),
            'address'                 => (string) ($user['address'] ?? ''),
            'barangay_id'             => $user['barangay_id'] ?? null,
            'assigned_evacuation_center_id' => $user['assigned_evacuation_center_id'] ?? null,
            'id_verification_status'  => (string) ($user['id_verification_status'] ?? 'none'),
            'id_document_path'        => (string) ($user['id_document_path'] ?? ''),
            'has_id_document'         => ($user['id_document_path'] ?? '') !== '',
            'created_at'              => drms_format_datetime((string) ($user['created_at'] ?? '')),
        ];
    }

    /**
     * @return array{rows:list<array<string,mixed>>, total:int, filtered:int}
     */
    public function fetchTable(string $term, int $start, int $length, ?string $statusFilter = null, ?string $groupFilter = null): array
    {
        $db = db_connect();

        $build = static function () use ($db, $term, $statusFilter, $groupFilter) {
            $builder = $db->table('users')
                ->select('users.id, users.username, users.active, users.status, users.status_message, users.created_at, users.deleted_at')
                ->where('users.deleted_at', null);

            if ($groupFilter !== null && $groupFilter !== '') {
                $builder->join('auth_groups_users', 'auth_groups_users.user_id = users.id', 'inner')
                    ->where('auth_groups_users.group', $groupFilter);
            }

            if ($statusFilter === 'pending') {
                $builder->where('users.active', 0)->where('users.status', 'pending');
            } elseif ($statusFilter === 'approved') {
                $builder->where('users.active', 1)
                    ->groupStart()
                    ->where('users.status', null)
                    ->orWhere('users.status', '')
                    ->groupEnd();
            } elseif ($statusFilter === 'rejected') {
                $builder->where('users.status', 'rejected');
            } elseif ($statusFilter === 'inactive') {
                $builder->where('users.status', 'inactive');
            } elseif ($statusFilter === 'disabled') {
                $builder->where('users.status', 'disabled');
            }

            if ($term !== '') {
                $builder->groupStart()
                    ->like('users.username', $term)
                    ->groupEnd();
            }

            return $builder;
        };

        $total    = (int) $db->table('users')->where('deleted_at', null)->countAllResults();
        $filtered = $build()->countAllResults(false);
        $rows     = $build()->orderBy('users.id', 'DESC')->limit($length, $start)->get()->getResultArray();

        foreach ($rows as &$row) {
            $uid = (int) ($row['id'] ?? 0);
            $row['email'] = $this->emailForUser($uid);
            $row['group'] = $this->primaryGroup($uid);
            $row['account_status'] = $this->resolveAccountStatus($row);
            $profile = $this->profiles->getProfileRow($uid);
            $row['id_verification_status'] = (string) ($profile['id_verification_status'] ?? 'none');
            $row['id_document_path']       = (string) ($profile['id_document_path'] ?? '');
        }
        unset($row);

        return ['rows' => $rows, 'total' => $total, 'filtered' => $filtered];
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int $userId): array
    {
        $user = $this->users->withDeleted()->find($userId);
        if (! $user) {
            throw new RuntimeException('User not found.');
        }

        $profile = $this->profiles->getProfileRow($userId) ?? [];
        $volunteer = model(\App\Models\VolunteerModel::class)->where('user_id', $userId)->first();

        $fullName = trim((string) ($profile['full_name'] ?? ''));
        $phone    = trim((string) ($profile['phone'] ?? ''));
        $address  = trim((string) ($profile['address'] ?? ''));
        if ($volunteer) {
            if ($fullName === '') {
                $fullName = trim((string) ($volunteer['full_name'] ?? ''));
            }
            if ($phone === '') {
                $phone = trim((string) ($volunteer['phone'] ?? ''));
            }
            if ($address === '') {
                $address = trim((string) ($volunteer['address'] ?? ''));
            }
        }

        $idDocPath = trim((string) ($profile['id_document_path'] ?? ''));
        $idStatus  = strtolower((string) ($profile['id_verification_status'] ?? 'none'));
        if ($volunteer) {
            if ($idDocPath === '' && ! empty($volunteer['id_verification_path'])) {
                $idDocPath = trim((string) $volunteer['id_verification_path']);
            }
            if (($idStatus === 'none' || $idStatus === '') && ! empty($volunteer['id_verification_status'])) {
                $idStatus = strtolower((string) $volunteer['id_verification_status']);
            }
        }

        return [
            'id'                      => (int) $user->id,
            'username'                => (string) ($user->username ?? ''),
            'email'                   => (string) ($user->email ?? ''),
            'active'                  => (bool) $user->active,
            'status'                  => (string) ($user->status ?? ''),
            'status_message'          => (string) ($user->status_message ?? ''),
            'account_status'          => $this->resolveAccountStatus([
                'active' => $user->active,
                'status' => $user->status,
            ]),
            'group'                   => $this->primaryGroup($userId),
            'barangay_id'             => $profile['barangay_id'] ?? $this->profiles->getBarangayIdForUser($userId),
            'assigned_evacuation_center_id' => $profile['assigned_evacuation_center_id'] ?? $this->profiles->getAssignedEvacuationCenterId($userId),
            'full_name'               => $fullName,
            'phone'                   => $phone,
            'address'                 => $address,
            'profile_photo'           => (string) ($profile['profile_photo'] ?? ''),
            'id_document_path'        => $idDocPath,
            'id_verification_status'  => $idStatus !== '' ? $idStatus : 'none',
            'created_at'              => (string) ($user->created_at ?? ''),
            'volunteer_group'         => (new \App\Services\VolunteerGroupService())->getGroupByUserId($userId),
            'volunteer_group_members' => ($groupRow = (new \App\Services\VolunteerGroupService())->getGroupByUserId($userId))
                ? (new \App\Services\VolunteerGroupService())->getMembers((int) $groupRow['id'])
                : [],
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data, ?int $adminId = null): int
    {
        $username = trim((string) ($data['username'] ?? ''));
        $email    = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($username === '' || $email === '' || strlen($password) < 8) {
            throw new RuntimeException('Username, email, and password (min 8 chars) are required.');
        }

        $user = $this->users->createNewUser([
            'username' => $username,
            'email'    => $email,
            'password' => $password,
        ]);

        if (! $this->users->save($user)) {
            throw new RuntimeException(implode(' ', $this->users->errors()));
        }

        $userId = (int) $this->users->getInsertID();
        $user   = $this->users->findById($userId);
        if ($user === null) {
            throw new RuntimeException('Could not create user.');
        }

        $group = strtolower(trim((string) ($data['group'] ?? 'resident')));
        if (! in_array($group, $this->assignableGroups, true)) {
            $group = 'resident';
        }
        $user->syncGroups($group);

        $barangayId = (int) ($data['barangay_id'] ?? 0);
        $barangayId = $barangayId > 0 ? $barangayId : null;
        $assignedCenterId = (int) ($data['assigned_evacuation_center_id'] ?? 0);
        $assignedCenterId = $assignedCenterId > 0 ? $assignedCenterId : null;
        $this->profiles->ensureProfile($userId, [
            'full_name'   => trim((string) ($data['full_name'] ?? $username)),
            'phone'       => trim((string) ($data['phone'] ?? '')),
            'address'     => trim((string) ($data['address'] ?? '')),
            'barangay_id' => $barangayId,
            'assigned_evacuation_center_id' => $group === 'evac_manager' ? $assignedCenterId : null,
        ]);

        $this->applyRoleSideEffects($userId, $group, $barangayId);

        $accountStatus = strtolower(trim((string) ($data['account_status'] ?? 'approved')));
        $this->applyAccountStatus($userId, $accountStatus, trim((string) ($data['status_message'] ?? '')));

        return $userId;
    }

    public function approve(int $userId, ?int $adminId = null): void
    {
        $user = $this->requireUser($userId);
        $this->guardProtectedUser($userId, $adminId);

        $this->applyAccountStatus($userId, 'approved');

        $email = (string) ($user->email ?? '');
        if ($email !== '') {
            (new NotificationService())->queue(
                'email',
                $email,
                '[DRMS] Account approved',
                'Your DRMS account has been approved. You may now sign in at ' . base_url('login'),
                ['event' => 'user.approved', 'user_id' => $userId],
            );
        }
    }

    public function reject(int $userId, ?string $message = null, ?int $adminId = null): void
    {
        $this->requireUser($userId);
        $this->guardProtectedUser($userId, $adminId);
        $this->applyAccountStatus($userId, 'rejected', $message ?: 'Registration not approved by administrator.');
    }

    public function delete(int $userId, ?int $adminId = null): void
    {
        $this->guardProtectedUser($userId, $adminId);
        $this->requireUser($userId);

        if (! $this->users->delete($userId)) {
            throw new RuntimeException('Could not deactivate user.');
        }
    }

    public function verifyIdDocument(int $userId, ?int $adminId = null): void
    {
        $this->guardProtectedUser($userId, $adminId);
        $this->profiles->verifyIdDocument($userId);

        $profile = $this->profiles->getProfileRow($userId) ?? [];
        $vol     = model(\App\Models\VolunteerModel::class)->where('user_id', $userId)->first();
        if ($vol) {
            $payload = ['id_verification_status' => 'approved'];
            $path    = trim((string) ($vol['id_verification_path'] ?? ''));
            if ($path === '' && ! empty($profile['id_document_path'])) {
                $payload['id_verification_path'] = (string) $profile['id_document_path'];
            }
            if (! empty($payload['id_verification_path']) || ! empty($vol['id_verification_path'])) {
                model(\App\Models\VolunteerModel::class)->update($vol['id'], $payload);
            }
        }
    }

    public function rejectIdDocument(int $userId, ?int $adminId = null): void
    {
        $this->guardProtectedUser($userId, $adminId);
        $this->profiles->rejectIdDocument($userId);

        $vol = model(\App\Models\VolunteerModel::class)->where('user_id', $userId)->first();
        if ($vol) {
            model(\App\Models\VolunteerModel::class)->update($vol['id'], ['id_verification_status' => 'rejected']);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $userId, array $data, ?int $adminId = null): void
    {
        $user = $this->requireUser($userId);
        $this->guardProtectedUser($userId, $adminId);

        $username = trim((string) ($data['username'] ?? $user->username ?? ''));
        $email    = trim((string) ($data['email'] ?? $user->email ?? ''));
        if ($username === '' || $email === '') {
            throw new RuntimeException('Username and email are required.');
        }

        $user->username = $username;
        $user->email    = $email;
        if (! empty($data['password'])) {
            $user->password = (string) $data['password'];
        }
        $this->users->save($user);

        $group = strtolower(trim((string) ($data['group'] ?? '')));
        if ($group !== '' && in_array($group, $this->assignableGroups, true)) {
            $user->syncGroups($group);
        }

        $this->profiles->updateFields($userId, $data);

        $barangayId = (int) ($data['barangay_id'] ?? 0);
        $barangayId = $barangayId > 0 ? $barangayId : null;
        $assignedCenterId = (int) ($data['assigned_evacuation_center_id'] ?? 0);
        $assignedCenterId = $assignedCenterId > 0 ? $assignedCenterId : null;
        $effectiveGroup = $group !== '' ? $group : $this->primaryGroup($userId);
        if ($effectiveGroup === 'evac_manager') {
            $this->profiles->ensureProfile($userId, ['assigned_evacuation_center_id' => $assignedCenterId]);
        } elseif ($group !== '' && $group !== 'evac_manager') {
            $this->profiles->ensureProfile($userId, ['assigned_evacuation_center_id' => null]);
        }
        if ($group !== '') {
            $this->applyRoleSideEffects($userId, $group, $barangayId);
        }

        $accountStatus = strtolower(trim((string) ($data['account_status'] ?? '')));
        if ($accountStatus !== '' && in_array($accountStatus, $this->accountStatuses, true)) {
            $this->applyAccountStatus($userId, $accountStatus, trim((string) ($data['status_message'] ?? '')));
        }
    }

    public function applyAccountStatus(int $userId, string $status, ?string $message = null): void
    {
        $status = strtolower(trim($status));
        if (! in_array($status, $this->accountStatuses, true)) {
            throw new RuntimeException('Invalid account status.');
        }

        $payload = match ($status) {
            'approved'  => ['active' => 1, 'status' => null, 'status_message' => null],
            'pending'   => ['active' => 0, 'status' => 'pending', 'status_message' => $message ?: 'Awaiting administrator approval.'],
            'rejected'  => ['active' => 0, 'status' => 'rejected', 'status_message' => $message ?: 'Registration rejected.'],
            'inactive'  => ['active' => 0, 'status' => 'inactive', 'status_message' => $message ?: 'Account marked inactive.'],
            'disabled'  => ['active' => 0, 'status' => 'disabled', 'status_message' => $message ?: 'Account disabled by administrator.'],
            default     => ['active' => 0, 'status' => $status, 'status_message' => $message],
        };

        $this->users->update($userId, $payload);
    }

    /**
     * @param array<string, mixed> $row
     */
    public function resolveAccountStatus(array $row): string
    {
        $status = strtolower((string) ($row['status'] ?? ''));
        $active = (bool) ($row['active'] ?? false);

        if ($status === 'rejected') {
            return 'rejected';
        }
        if ($status === 'inactive') {
            return 'inactive';
        }
        if ($status === 'disabled') {
            return 'disabled';
        }
        if ($status === 'pending' || (! $active && $status === '')) {
            return 'pending';
        }
        if ($active) {
            return 'approved';
        }

        return $status !== '' ? $status : 'pending';
    }

    private function requireUser(int $userId): object
    {
        $user = $this->users->withDeleted()->find($userId);
        if (! $user) {
            throw new RuntimeException('User not found.');
        }

        return $user;
    }

    private function guardProtectedUser(int $userId, ?int $adminId): void
    {
        if ($adminId !== null && $userId === $adminId) {
            throw new RuntimeException('You cannot modify your own account from this screen.');
        }

        $group = $this->primaryGroup($userId);
        if ($group === 'admin') {
            throw new RuntimeException('Admin accounts cannot be modified here.');
        }
    }

    private function emailForUser(int $userId): string
    {
        $row = db_connect()->table('auth_identities')
            ->select('secret')
            ->where('user_id', $userId)
            ->where('type', 'email_password')
            ->get()
            ->getRowArray();

        return (string) ($row['secret'] ?? '');
    }

    private function primaryGroup(int $userId): string
    {
        $row = db_connect()->table('auth_groups_users')
            ->select('group')
            ->where('user_id', $userId)
            ->orderBy('id', 'ASC')
            ->get()
            ->getRowArray();

        return (string) ($row['group'] ?? '');
    }

    private function applyRoleSideEffects(int $userId, string $group, ?int $barangayId = null): void
    {
        if ($group === 'volunteer') {
            (new UserRegistrationService())->ensureVolunteerProfile($userId, $barangayId);
        }
    }
}
