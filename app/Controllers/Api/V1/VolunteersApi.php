<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Models\VolunteerModel;
use App\Services\VolunteerService;
use App\Validation\VolunteerRules;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use RuntimeException;

class VolunteersApi extends ResourceController
{
    protected $format = 'json';

    public function index(): ResponseInterface
    {
        if (! $this->canRead()) {
            return $this->failForbidden('Requires admin.access or volunteers.* permission.');
        }

        $limit  = min(200, max(1, (int) ($this->request->getGet('limit') ?? 100)));
        $search = trim((string) ($this->request->getGet('search') ?? ''));

        $builder = model(VolunteerModel::class)->builder()
            ->select('volunteers.*, barangays.name AS barangay_name')
            ->join('barangays', 'barangays.id = volunteers.barangay_id', 'left')
            ->where('volunteers.deleted_at', null)
            ->orderBy('volunteers.id', 'DESC');

        if ($search !== '') {
            $builder->groupStart()
                ->like('full_name', $search)
                ->orLike('email', $search)
                ->groupEnd();
        }

        $rows = $builder->limit($limit)->get()->getResultArray();

        $svc      = new VolunteerService();
        $enriched = [];
        foreach ($rows as $row) {
            $idFields = $svc->resolveIdDisplayFields($row);
            $row['id_verification_status'] = $idFields['status'];
            $row['id_verification_path']   = $idFields['path'];
            $enriched[]                    = $row;
        }

        return $this->respond([
            'data' => $enriched,
            'meta' => ['count' => count($enriched)],
        ]);
    }

    public function show($id = null): ResponseInterface
    {
        $id = (int) $id;
        if (! $this->canRead()) {
            return $this->failForbidden('Requires admin.access or volunteers.* permission.');
        }

        $row = (new VolunteerService())->findWithSkills($id);
        if (! $row) {
            return $this->failNotFound('Volunteer not found.');
        }

        return $this->respond(['data' => $row]);
    }

    public function create(): ResponseInterface
    {
        if (! $this->canWrite()) {
            return $this->failForbidden('Requires volunteers.* or admin.* permission.');
        }

        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        if (! $this->validate(VolunteerRules::save())) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        try {
            $svc = new VolunteerService();
            $row = $svc->create($input);

            return $this->respondCreated([
                'data'    => $svc->findWithSkills((int) ($row['id'] ?? 0)) ?? $row,
                'message' => 'Volunteer created.',
            ]);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        }
    }

    public function update($id = null): ResponseInterface
    {
        $id = (int) $id;
        if ($id <= 0) {
            return $this->failNotFound('Volunteer not found.');
        }
        if (! $this->canWrite()) {
            return $this->failForbidden('Requires volunteers.* or admin.* permission.');
        }

        $input = $this->request->getJSON(true) ?? $this->request->getRawInput();
        if (! $this->validate(VolunteerRules::save())) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        try {
            $svc = new VolunteerService();
            if (! $svc->update($id, $input)) {
                return $this->fail('Could not update volunteer.', 400);
            }

            return $this->respond([
                'data'    => $svc->findWithSkills($id),
                'message' => 'Volunteer updated.',
            ]);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        }
    }

    public function delete($id = null): ResponseInterface
    {
        $id = (int) $id;
        if ($id <= 0) {
            return $this->failNotFound('Volunteer not found.');
        }
        if (! $this->canWrite()) {
            return $this->failForbidden('Requires volunteers.* or admin.* permission.');
        }

        if (! (new VolunteerService())->delete($id)) {
            return $this->fail('Could not delete volunteer.', 400);
        }

        return $this->respondDeleted(['message' => 'Volunteer removed.']);
    }

    public function verifyId($id = null): ResponseInterface
    {
        $id = (int) $id;
        if (! $this->canWrite()) {
            return $this->failForbidden('Requires volunteers.* or admin.* permission.');
        }

        $svc = new VolunteerService();
        $row = $svc->findWithSkills($id);
        if (! $row) {
            return $this->failNotFound('Volunteer not found.');
        }

        $idFields = $svc->resolveIdDisplayFields($row);
        if ($idFields['path'] === '') {
            return $this->fail('No ID document uploaded.', 422);
        }

        model(VolunteerModel::class)->update($id, [
            'id_verification_path'   => $idFields['path'],
            'id_verification_status' => 'approved',
        ]);

        return $this->respond(['message' => 'ID verification approved.']);
    }

    public function rejectId($id = null): ResponseInterface
    {
        $id = (int) $id;
        if (! $this->canWrite()) {
            return $this->failForbidden('Requires volunteers.* or admin.* permission.');
        }

        if (! model(VolunteerModel::class)->find($id)) {
            return $this->failNotFound('Volunteer not found.');
        }

        model(VolunteerModel::class)->update($id, ['id_verification_status' => 'rejected']);

        return $this->respond(['message' => 'ID verification rejected.']);
    }

    public function uploadIdDocument($id = null): ResponseInterface
    {
        $id = (int) $id;
        if ($id <= 0) {
            return $this->failNotFound('Volunteer not found.');
        }
        if (! $this->canWrite()) {
            return $this->failForbidden('Requires volunteers.* or admin.* permission.');
        }

        if (! model(VolunteerModel::class)->find($id)) {
            return $this->failNotFound('Volunteer not found.');
        }

        $file = $this->request->getFile('id_document');
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return $this->fail('No ID document uploaded.', 400);
        }

        try {
            $path = (new VolunteerService())->storeIdDocument($id, $file);

            return $this->respond([
                'message' => 'ID document uploaded.',
                'data'    => [
                    'id_verification_path'   => $path,
                    'id_verification_status' => 'pending',
                ],
            ]);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        }
    }

    private function canRead(): bool
    {
        return drms_user_can('admin.access', 'volunteers.*', 'admin.*');
    }

    private function canWrite(): bool
    {
        return drms_user_can('volunteers.*', 'admin.*');
    }
}
