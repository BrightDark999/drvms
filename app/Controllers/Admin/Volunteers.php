<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\Admin\Traits\CrudResponses;
use App\Models\BarangayModel;
use App\Models\VolunteerModel;
use App\Services\VolunteerService;
use App\Validation\VolunteerRules;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

class Volunteers extends BaseController
{
    use CrudResponses;

    public function index(): string
    {
        $skillRequests = drms_user_can('volunteers.*', 'admin.*')
            ? (new \App\Services\VolunteerSkillRequestService())->listPending()
            : [];

        return view('admin/volunteers/index', [
            'title'          => 'Volunteers',
            'drms'           => drms_config(),
            'dataUrl'        => base_url('admin/volunteers/data'),
            'barangays'      => drms_barangay_list(),
            'volunteerSkills' => config('DrmsFeatures')->volunteerSkills,
            'skillRequests'  => $skillRequests,
        ]);
    }

    public function data(): ResponseInterface
    {
        helper(['datatable', 'crud']);
        $request = $this->request;
        $start   = max(0, (int) $request->getVar('start'));
        $length  = min(100, max(5, (int) ($request->getVar('length') ?: 10)));
        $search  = $request->getVar('search');
        $term    = is_array($search) ? trim((string) ($search['value'] ?? '')) : '';

        $model = model(VolunteerModel::class);
        $total = $model->countAllResults();

        $buildList = static function () use ($term) {
            $builder = db_connect()->table('volunteers')
                ->select('volunteers.id, volunteers.full_name, volunteers.email, volunteers.phone, volunteers.availability_status, volunteers.id_verification_status, volunteers.id_verification_path, volunteers.user_id, barangays.name AS barangay_name, user_profiles.id_document_path AS profile_id_path, user_profiles.id_verification_status AS profile_id_status')
                ->join('barangays', 'barangays.id = volunteers.barangay_id', 'left')
                ->join('user_profiles', 'user_profiles.user_id = volunteers.user_id', 'left')
                ->where('volunteers.deleted_at', null);

            if ($term !== '') {
                $builder->groupStart()
                    ->like('volunteers.full_name', $term)
                    ->orLike('volunteers.email', $term)
                    ->groupEnd();
            }

            return $builder;
        };

        $filtered = $buildList()->countAllResults(false);
        $rows     = $buildList()->orderBy('volunteers.id', 'DESC')->limit($length, $start)->get()->getResultArray();

        $canVerify = drms_user_can('admin.*', 'volunteers.*');
        $svc       = new VolunteerService();
        $data      = [];
        foreach ($rows as $r) {
            $id = (int) ($r['id'] ?? 0);
            if (empty($r['id_verification_path']) && ! empty($r['profile_id_path'])) {
                $r = $svc->mergeLinkedProfileId($r, true);
            }
            $idFields = $svc->resolveIdDisplayFields($r);
            $idStatus = $idFields['status'];
            $idPath   = $idFields['path'] !== '' ? $idFields['path'] : null;
            $idBadge = match ($idStatus) {
                'approved' => '<span class="badge badge-success">ID verified</span>',
                'pending'  => '<span class="badge badge-warning">ID pending</span>',
                'rejected' => '<span class="badge badge-danger">ID rejected</span>',
                default    => '<span class="badge badge-secondary">No ID</span>',
            };
            $actions = drms_volunteer_table_actions(
                $id,
                $idStatus,
                $canVerify,
                $idPath,
            );
            $data[] = [
                esc($r['full_name'] ?? ''),
                esc($r['email'] ?? ''),
                esc($r['phone'] ?? ''),
                esc(ucfirst(str_replace('_', ' ', $r['availability_status'] ?? ''))),
                esc($r['barangay_name'] ?? '—'),
                $idBadge,
                $actions,
            ];
        }

        return datatable_response($request, $data, $total, $filtered);
    }

    public function show(int $id): ResponseInterface
    {
        $row = (new VolunteerService())->findWithSkills($id);
        if (! $row) {
            return $this->crudFail('Volunteer not found.', 404);
        }

        return $this->crudOk('OK', $row);
    }

    public function store(): ResponseInterface
    {
        if (! $this->validate(VolunteerRules::save())) {
            return $this->crudValidationFail();
        }

        try {
            $svc = new VolunteerService();
            $row = $svc->create($this->request->getPost());
            $id  = (int) ($row['id'] ?? 0);
            $this->handleIdUpload($svc, $id);

            return $this->crudOk('Volunteer saved.', $svc->findWithSkills($id) ?? $row);
        } catch (RuntimeException $e) {
            return $this->crudFail($e->getMessage(), 500);
        }
    }

    public function update(int $id): ResponseInterface
    {
        if (! $this->validate(VolunteerRules::save())) {
            return $this->crudValidationFail();
        }

        try {
            $svc = new VolunteerService();
            if (! $svc->update($id, $this->request->getPost())) {
                return $this->crudFail('Could not update volunteer.', 400);
            }
            $this->handleIdUpload($svc, $id);

            return $this->crudOk('Volunteer updated.');
        } catch (RuntimeException $e) {
            return $this->crudFail($e->getMessage(), 400);
        }
    }

    public function delete(int $id): ResponseInterface
    {
        if (! (new VolunteerService())->delete($id)) {
            return $this->crudFail('Could not delete volunteer.', 400);
        }

        return $this->crudOk('Volunteer removed.');
    }

    public function verifyId(int $id): ResponseInterface
    {
        if (! drms_user_can('admin.*', 'volunteers.*')) {
            return $this->crudFail('Unauthorized.', 403);
        }

        $svc = new VolunteerService();
        $row = $svc->findWithSkills($id);
        if (! $row) {
            return $this->crudFail('Volunteer not found.', 404);
        }
        $idFields = $svc->resolveIdDisplayFields($row);
        if ($idFields['path'] === '') {
            return $this->crudFail('No ID document uploaded.', 422);
        }

        model(VolunteerModel::class)->update($id, [
            'id_verification_path'   => $idFields['path'],
            'id_verification_status' => 'approved',
        ]);

        if (! empty($row['user_id'])) {
            $profile = (new \App\Services\UserProfileService())->getProfileRow((int) $row['user_id']);
            if ($profile && ! empty($profile['id_document_path'])) {
                (new \App\Services\UserProfileService())->ensureProfile((int) $row['user_id'], [
                    'id_verification_status' => 'approved',
                ]);
            }
        }

        return $this->crudOk('ID verification approved.');
    }

    public function rejectId(int $id): ResponseInterface
    {
        if (! drms_user_can('admin.*', 'volunteers.*')) {
            return $this->crudFail('Unauthorized.', 403);
        }

        $model = model(VolunteerModel::class);
        if (! $model->find($id)) {
            return $this->crudFail('Volunteer not found.', 404);
        }

        $model->update($id, ['id_verification_status' => 'rejected']);

        return $this->crudOk('ID verification rejected.');
    }

    public function uploadId(int $id): ResponseInterface
    {
        if (! drms_user_can('admin.*', 'volunteers.*')) {
            return $this->crudFail('Unauthorized.', 403);
        }

        try {
            $svc = new VolunteerService();
            if (! $svc->findWithSkills($id)) {
                return $this->crudFail('Volunteer not found.', 404);
            }
            $this->handleIdUpload($svc, $id);

            return $this->crudOk('ID document uploaded.', $svc->findWithSkills($id));
        } catch (RuntimeException $e) {
            return $this->crudFail($e->getMessage(), 400);
        }
    }

    public function skillRequests(): ResponseInterface
    {
        if (! drms_user_can('admin.*', 'volunteers.*')) {
            return $this->crudFail('Unauthorized.', 403);
        }

        return $this->crudOk('OK', (new \App\Services\VolunteerSkillRequestService())->listPending());
    }

    public function approveSkills(int $id): ResponseInterface
    {
        if (! drms_user_can('admin.*', 'volunteers.*')) {
            return $this->crudFail('Unauthorized.', 403);
        }

        try {
            (new \App\Services\VolunteerSkillRequestService())->approve($id, auth()->id() ? (int) auth()->id() : null);

            return $this->crudOk('Volunteer skills approved and updated.');
        } catch (RuntimeException $e) {
            return $this->crudFail($e->getMessage(), 400);
        }
    }

    public function rejectSkills(int $id): ResponseInterface
    {
        if (! drms_user_can('admin.*', 'volunteers.*')) {
            return $this->crudFail('Unauthorized.', 403);
        }

        try {
            (new \App\Services\VolunteerSkillRequestService())->reject(
                $id,
                auth()->id() ? (int) auth()->id() : null,
                trim((string) $this->request->getPost('notes'))
            );

            return $this->crudOk('Skill change request rejected.');
        } catch (RuntimeException $e) {
            return $this->crudFail($e->getMessage(), 400);
        }
    }

    private function handleIdUpload(VolunteerService $svc, int $volunteerId): void
    {
        if ($volunteerId <= 0) {
            return;
        }

        $file = $this->request->getFile('id_document');
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return;
        }

        if (! $file->isValid()) {
            throw new RuntimeException('ID upload failed: ' . ($file->getErrorString() ?: 'Invalid file.'));
        }

        $svc->storeIdDocument($volunteerId, $file);
    }

    public function approveMember(int $id): ResponseInterface
    {
        if (! drms_user_can('admin.*', 'volunteers.*')) {
            return $this->crudFail('Unauthorized.', 403);
        }

        if ((new \App\Services\VolunteerGroupService())->approveMemberChange($id)) {
            return $this->crudOk('Group member approved.');
        }

        return $this->crudFail('Could not approve group member change.', 400);
    }

    public function rejectMember(int $id): ResponseInterface
    {
        if (! drms_user_can('admin.*', 'volunteers.*')) {
            return $this->crudFail('Unauthorized.', 403);
        }

        if ((new \App\Services\VolunteerGroupService())->rejectMemberChange($id)) {
            return $this->crudOk('Group member change rejected.');
        }

        return $this->crudFail('Could not reject group member change.', 400);
    }
}
