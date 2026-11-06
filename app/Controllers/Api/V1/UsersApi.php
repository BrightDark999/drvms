<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Services\UserManagementService;
use App\Validation\UserRules;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use RuntimeException;

/**
 * REST CRUD for users (staff tokens with users.* or admin.*).
 */
class UsersApi extends ResourceController
{
    protected $format = 'json';

    public function index(): ResponseInterface
    {
        if (! $this->canManage()) {
            return $this->failForbidden('Requires users.* or admin.* permission.');
        }

        $limit  = min(100, max(1, (int) ($this->request->getGet('limit') ?? 50)));
        $offset = max(0, (int) ($this->request->getGet('offset') ?? 0));
        $search = trim((string) ($this->request->getGet('search') ?? ''));
        $status = trim((string) ($this->request->getGet('status') ?? ''));
        $role   = trim((string) ($this->request->getGet('role') ?? ''));

        $payload = (new UserManagementService())->listForApi(
            $limit,
            $offset,
            $status !== '' ? $status : null,
            $role !== '' ? $role : null,
            $search,
        );

        return $this->respond($payload);
    }

    public function show($id = null): ResponseInterface
    {
        $id = (int) $id;
        if ($id <= 0) {
            return $this->failNotFound('User not found.');
        }
        if (! $this->canManage()) {
            return $this->failForbidden('Requires users.* or admin.* permission.');
        }

        try {
            $user = (new UserManagementService())->find($id);
        } catch (RuntimeException $e) {
            return $this->failNotFound($e->getMessage());
        }

        return $this->respond([
            'data' => (new UserManagementService())->formatApiDetail($user),
        ]);
    }

    public function create(): ResponseInterface
    {
        if (! $this->canManage()) {
            return $this->failForbidden('Requires users.* or admin.* permission.');
        }

        $input = $this->request->getJSON(true) ?? $this->request->getPost();

        if (! $this->validate(UserRules::createApi())) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        try {
            $id  = (new UserManagementService())->create($input, auth()->id());
            $row = (new UserManagementService())->find($id);

            return $this->respondCreated([
                'data'    => (new UserManagementService())->formatApiDetail($row),
                'message' => 'User created.',
            ]);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        }
    }

    public function update($id = null): ResponseInterface
    {
        $id = (int) $id;
        if ($id <= 0) {
            return $this->failNotFound('User not found.');
        }
        if (! $this->canManage()) {
            return $this->failForbidden('Requires users.* or admin.* permission.');
        }

        $input = $this->request->getJSON(true) ?? $this->request->getRawInput();

        if (! $this->validate(UserRules::updateApi($id))) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        try {
            (new UserManagementService())->update($id, $input, auth()->id());
            $row = (new UserManagementService())->find($id);

            return $this->respond([
                'data'    => (new UserManagementService())->formatApiDetail($row),
                'message' => 'User updated.',
            ]);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        }
    }

    public function delete($id = null): ResponseInterface
    {
        $id = (int) $id;
        if ($id <= 0) {
            return $this->fail('User not found.', 400);
        }
        if (! $this->canManage()) {
            return $this->failForbidden('Requires users.* or admin.* permission.');
        }

        try {
            (new UserManagementService())->delete($id, auth()->id());
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        }

        return $this->respondDeleted(['message' => 'User deactivated (soft delete).']);
    }

    public function approve($id = null): ResponseInterface
    {
        $id = (int) $id;
        if (! $this->canManage()) {
            return $this->failForbidden('Requires users.* or admin.* permission.');
        }

        try {
            (new UserManagementService())->approve($id, auth()->id());

            return $this->respond(['message' => 'User approved.']);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        }
    }

    public function reject($id = null): ResponseInterface
    {
        $id = (int) $id;
        if (! $this->canManage()) {
            return $this->failForbidden('Requires users.* or admin.* permission.');
        }

        $input = $this->request->getJSON(true) ?? $this->request->getPost();

        try {
            (new UserManagementService())->reject(
                $id,
                trim((string) ($input['message'] ?? '')),
                auth()->id(),
            );

            return $this->respond(['message' => 'User rejected.']);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        }
    }

    public function verifyId($id = null): ResponseInterface
    {
        $id = (int) $id;
        if (! $this->canManage()) {
            return $this->failForbidden('Requires users.* or admin.* permission.');
        }

        try {
            (new UserManagementService())->verifyIdDocument($id, auth()->id());

            return $this->respond(['message' => 'ID document verified.']);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        }
    }

    private function canManage(): bool
    {
        return drms_user_can('users.*', 'admin.*');
    }
}
