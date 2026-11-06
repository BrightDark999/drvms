<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\VolunteerGroupMemberModel;
use App\Models\VolunteerGroupModel;
use App\Models\VolunteerModel;
use RuntimeException;

class VolunteerGroupService
{
    private VolunteerGroupModel $groups;
    private VolunteerGroupMemberModel $members;

    public function __construct()
    {
        $this->groups = model(VolunteerGroupModel::class);
        $this->members = model(VolunteerGroupMemberModel::class);
    }

    /**
     * Create volunteer group with at least 7 members
     *
     * @param array<string, mixed> $groupData
     * @param list<array<string, mixed>> $membersList
     * @return array<string, mixed>
     */
    public function registerGroup(int $userId, array $groupData, array $membersList): array
    {
        // Filter out empty rows
        $validMembers = [];
        foreach ($membersList as $m) {
            $name = trim((string) ($m['full_name'] ?? ''));
            if ($name !== '') {
                $validMembers[] = [
                    'full_name'      => $name,
                    'contact_number' => trim((string) ($m['contact_number'] ?? '')),
                    'email'          => trim((string) ($m['email'] ?? '')),
                    'role_or_skills' => trim((string) ($m['role_or_skills'] ?? '')),
                    'address'        => trim((string) ($m['address'] ?? '')),
                    'status'         => 'pending',
                    'change_type'    => 'none',
                ];
            }
        }

        if (count($validMembers) < 7) {
            throw new RuntimeException('Group registration requires a minimum of 7 group members.');
        }

        $groupId = $this->groups->insert([
            'user_id'           => $userId,
            'organization_name' => trim((string) ($groupData['organization_name'] ?? '')),
            'head_name'         => trim((string) ($groupData['head_name'] ?? '')),
            'head_contact'      => trim((string) ($groupData['head_contact'] ?? '')),
            'head_email'        => trim((string) ($groupData['head_email'] ?? '')),
            'barangay_id'       => ! empty($groupData['barangay_id']) ? (int) $groupData['barangay_id'] : null,
            'address'           => trim((string) ($groupData['address'] ?? '')),
            'description'       => trim((string) ($groupData['description'] ?? '')),
            'status'            => 'pending',
        ]);

        foreach ($validMembers as $m) {
            $m['group_id'] = $groupId;
            $this->members->insert($m);
        }

        return $this->groups->find($groupId) ?? [];
    }

    public function getGroupByUserId(int $userId): ?array
    {
        $group = $this->groups->where('user_id', $userId)->first();
        return is_array($group) ? $group : null;
    }

    public function getMembers(int $groupId): array
    {
        return $this->members->where('group_id', $groupId)->findAll();
    }

    public function addMemberRequest(int $groupId, array $data, bool $isAdmin = false): int
    {
        $name = trim((string) ($data['full_name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('Member full name is required.');
        }

        return (int) $this->members->insert([
            'group_id'       => $groupId,
            'full_name'      => $name,
            'contact_number' => trim((string) ($data['contact_number'] ?? '')),
            'email'          => trim((string) ($data['email'] ?? '')),
            'role_or_skills' => trim((string) ($data['role_or_skills'] ?? '')),
            'address'        => trim((string) ($data['address'] ?? '')),
            'status'         => $isAdmin ? 'approved' : 'pending',
            'change_type'    => $isAdmin ? 'none' : 'add',
        ]);
    }

    public function updateMemberRequest(int $memberId, array $data, bool $isAdmin = false): bool
    {
        $member = $this->members->find($memberId);
        if (! $member) {
            throw new RuntimeException('Member not found.');
        }

        if ($isAdmin) {
            return (bool) $this->members->update($memberId, [
                'full_name'      => trim((string) ($data['full_name'] ?? $member['full_name'])),
                'contact_number' => trim((string) ($data['contact_number'] ?? $member['contact_number'])),
                'email'          => trim((string) ($data['email'] ?? $member['email'])),
                'role_or_skills' => trim((string) ($data['role_or_skills'] ?? $member['role_or_skills'])),
                'address'        => trim((string) ($data['address'] ?? $member['address'])),
                'status'         => 'approved',
                'change_type'    => 'none',
                'pending_data'   => null,
            ]);
        }

        // Group head request: store proposed changes in pending_data
        return (bool) $this->members->update($memberId, [
            'change_type'  => 'edit',
            'pending_data' => json_encode($data),
        ]);
    }

    public function deleteMemberRequest(int $memberId, bool $isAdmin = false): bool
    {
        $member = $this->members->find($memberId);
        if (! $member) {
            throw new RuntimeException('Member not found.');
        }

        if ($isAdmin) {
            return (bool) $this->members->delete($memberId);
        }

        // Group head request: mark change_type as delete awaiting approval
        return (bool) $this->members->update($memberId, [
            'change_type' => 'delete',
        ]);
    }

    public function approveMemberChange(int $memberId): bool
    {
        $member = $this->members->find($memberId);
        if (! $member) {
            return false;
        }

        if ($member['change_type'] === 'delete') {
            return (bool) $this->members->delete($memberId);
        }

        if ($member['change_type'] === 'edit' && ! empty($member['pending_data'])) {
            $data = json_decode((string) $member['pending_data'], true) ?: [];
            return (bool) $this->members->update($memberId, [
                'full_name'      => trim((string) ($data['full_name'] ?? $member['full_name'])),
                'contact_number' => trim((string) ($data['contact_number'] ?? $member['contact_number'])),
                'email'          => trim((string) ($data['email'] ?? $member['email'])),
                'role_or_skills' => trim((string) ($data['role_or_skills'] ?? $member['role_or_skills'])),
                'address'        => trim((string) ($data['address'] ?? $member['address'])),
                'status'         => 'approved',
                'change_type'    => 'none',
                'pending_data'   => null,
            ]);
        }

        return (bool) $this->members->update($memberId, [
            'status'      => 'approved',
            'change_type' => 'none',
        ]);
    }

    public function rejectMemberChange(int $memberId): bool
    {
        $member = $this->members->find($memberId);
        if (! $member) {
            return false;
        }

        if ($member['change_type'] === 'add') {
            return (bool) $this->members->delete($memberId);
        }

        return (bool) $this->members->update($memberId, [
            'change_type'  => 'none',
            'pending_data' => null,
        ]);
    }
}
