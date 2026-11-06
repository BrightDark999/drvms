<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ProfileService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

class Profile extends BaseController
{
    public function index(): string
    {
        $userId = (int) auth()->id();
        $profile = (new ProfileService())->getProfile($userId);
        $groupSvc = new \App\Services\VolunteerGroupService();
        $volGroup = $groupSvc->getGroupByUserId($userId);

        return view('profile/index', [
            'title'                 => 'My Profile',
            'drms'                  => drms_config(),
            'profile'               => $profile,
            'skills'                => config('DrmsFeatures')->volunteerSkills,
            'reports'               => (new \App\Services\IncidentService())->listByReporter($userId),
            'volunteerGroup'        => $volGroup,
            'volunteerGroupMembers' => $volGroup ? $groupSvc->getMembers((int) $volGroup['id']) : [],
        ]);
    }

    public function update(): RedirectResponse
    {
        $rules = [
            'email'     => 'required|valid_email',
            'full_name' => 'permit_empty|max_length[160]',
            'phone'     => 'permit_empty|max_length[48]',
            'address'   => 'permit_empty|max_length[500]',
            'password'        => 'permit_empty|min_length[8]',
            'preferred_hours' => 'permit_empty|in_list[8,12,24]',
            'fatigue_level'   => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if (auth()->user()->inGroup('volunteer')) {
            $avail = $this->request->getPost('availability_status');
            if ($avail !== null && ! in_array(strtolower((string) $avail), ['available', 'on_duty', 'unavailable'], true)) {
                return redirect()->back()->withInput()->with('error', 'Invalid availability status.');
            }
        }

        try {
            (new ProfileService())->updateProfile((int) auth()->id(), $this->request->getPost());

            return redirect()->to(base_url('profile'))->with('message', 'Profile updated.');
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function requestSkills(): RedirectResponse
    {
        if (! auth()->loggedIn() || ! auth()->user()->inGroup('volunteer')) {
            return redirect()->to(base_url('login'));
        }

        $skills = $this->request->getPost('skills');
        if (! is_array($skills)) {
            $skills = [];
        }

        try {
            (new ProfileService())->requestSkillChange((int) auth()->id(), $skills);

            return redirect()->to(base_url('profile'))->with('message', 'Skill change submitted for admin approval.');
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function uploadId(): RedirectResponse
    {
        $file = $this->request->getFile('id_document');
        if (! $file || ! $file->isValid()) {
            return redirect()->back()->with('error', 'Select a valid ID document (JPG, PNG, or PDF).');
        }

        try {
            (new ProfileService())->uploadIdVerification((int) auth()->id(), $file);

            return redirect()->to(base_url('profile'))->with('message', 'ID document uploaded for verification.');
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function uploadPhoto(): RedirectResponse
    {
        $file = $this->request->getFile('profile_photo');
        if (! $file || ! $file->isValid()) {
            return redirect()->back()->with('error', 'Select a valid profile photo (JPG or PNG).');
        }

        try {
            (new ProfileService())->uploadProfilePhoto((int) auth()->id(), $file);

            return redirect()->to(base_url('profile'))->with('message', 'Profile photo updated.');
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function notifications(): ResponseInterface
    {
        $uid = (int) auth()->id();
        $svc = new \App\Services\UserNotificationService();

        return $this->response->setJSON([
            'status' => 'ok',
            'count'  => $svc->unreadCount($uid),
            'items'  => $svc->unread($uid, 10),
        ]);
    }

    public function markNotificationRead(int $id): ResponseInterface
    {
        (new \App\Services\UserNotificationService())->markRead($id, (int) auth()->id());

        return $this->response->setJSON(['status' => 'ok']);
    }

    public function updateAvailability(): ResponseInterface
    {
        if (! auth()->loggedIn() || ! auth()->user()->inGroup('volunteer')) {
            return $this->response->setStatusCode(403)->setJSON(['message' => 'Volunteer access only.']);
        }

        $status = strtolower(trim((string) ($this->request->getPost('availability_status')
            ?? $this->request->getJSON(true)['availability_status'] ?? '')));

        if (! in_array($status, ['available', 'on_duty', 'unavailable'], true)) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Choose available, on_duty, or unavailable.']);
        }

        try {
            $row = (new ProfileService())->updateVolunteerAvailability((int) auth()->id(), $status);
            $label = match ($status) {
                'available'   => 'Available',
                'on_duty'     => 'On duty',
                'unavailable' => 'Unavailable',
                default       => ucfirst($status),
            };

            if ($this->request->isAJAX() || $this->request->getHeaderLine('Accept') === 'application/json') {
                return $this->response->setJSON([
                    'status'  => 'ok',
                    'message' => 'Availability updated to ' . $label . '.',
                    'data'    => [
                        'availability_status' => $status,
                        'availability_label'  => $label,
                    ],
                ]);
            }

            return redirect()->to(base_url('profile'))->with('message', 'Availability updated to ' . $label . '.');
        } catch (RuntimeException $e) {
            if ($this->request->isAJAX() || $this->request->getHeaderLine('Accept') === 'application/json') {
                return $this->response->setStatusCode(400)->setJSON(['message' => $e->getMessage()]);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function addGroupMember(): RedirectResponse
    {
        $userId = (int) auth()->id();
        $group = (new \App\Services\VolunteerGroupService())->getGroupByUserId($userId);
        if (! $group) {
            return redirect()->back()->with('error', 'You do not have an active volunteer group.');
        }

        $fullName = trim((string) $this->request->getPost('full_name'));
        if ($fullName === '') {
            return redirect()->back()->with('error', 'Member full name is required.');
        }

        try {
            (new \App\Services\VolunteerGroupService())->addMemberRequest((int) $group['id'], [
                'full_name'      => $fullName,
                'contact_number' => trim((string) $this->request->getPost('contact_number')),
                'email'          => trim((string) $this->request->getPost('email')),
                'role_or_skills' => trim((string) $this->request->getPost('role_or_skills')),
                'address'        => trim((string) $this->request->getPost('address')),
            ], false);

            return redirect()->to(base_url('profile'))->with('message', 'New group member submitted for MDRRMO approval.');
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function deleteGroupMember(int $memberId): RedirectResponse
    {
        $userId = (int) auth()->id();
        $group = (new \App\Services\VolunteerGroupService())->getGroupByUserId($userId);
        if (! $group) {
            return redirect()->back()->with('error', 'Unauthorized.');
        }

        try {
            (new \App\Services\VolunteerGroupService())->deleteMemberRequest($memberId, false);

            return redirect()->to(base_url('profile'))->with('message', 'Member removal requested. Pending MDRRMO approval.');
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
