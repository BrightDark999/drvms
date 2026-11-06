<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\Admin\Traits\CrudResponses;
use App\Models\BarangayModel;
use App\Services\UserManagementService;
use CodeIgniter\HTTP\ResponseInterface;
use Config\AuthGroups;
use RuntimeException;

class Users extends BaseController
{
    use CrudResponses;

    public function index()
    {
        if (! drms_user_can('users.*', 'admin.*')) {
            return redirect()->to(base_url('admin/dashboard'))->with('error', 'Access denied.');
        }

        return view('admin/users/index', [
            'title'           => 'User Management',
            'drms'            => drms_config(),
            'dataUrl'         => base_url('admin/users/data'),
            'barangays'       => drms_barangay_list(),
            'evacCenters'     => model(\App\Models\EvacuationCenterModel::class)->orderBy('name', 'ASC')->findAll(100),
            'groups'          => config(AuthGroups::class)->groups,
            'accountStatuses' => config('DrmsFeatures')->accountStatuses,
        ]);
    }

    public function data(): ResponseInterface
    {
        helper(['datatable', 'crud']);
        if (! drms_user_can('users.*', 'admin.*')) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $request = $this->request;
        $start   = max(0, (int) $request->getVar('start'));
        $length  = min(100, max(5, (int) ($request->getVar('length') ?: 10)));
        $search  = $request->getVar('search');
        $term    = is_array($search) ? trim((string) ($search['value'] ?? '')) : '';
        $filter  = trim((string) ($request->getVar('status_filter') ?? ''));

        $svc    = new UserManagementService();
        $result = $svc->fetchTable($term, $start, $length, $filter !== '' ? $filter : null);
        $data   = [];

        foreach ($result['rows'] as $r) {
            $id            = (int) ($r['id'] ?? 0);
            $accountStatus = (string) ($r['account_status'] ?? 'pending');
            $group         = (string) ($r['group'] ?? '');
            $idStatus      = strtolower((string) ($r['id_verification_status'] ?? 'none'));

            $badge = match ($accountStatus) {
                'rejected' => '<span class="badge badge-danger">Rejected</span>',
                'inactive' => '<span class="badge badge-secondary">Inactive</span>',
                'disabled' => '<span class="badge badge-dark">Disabled</span>',
                'pending'  => '<span class="badge badge-warning">Pending</span>',
                default    => '<span class="badge badge-success">Approved</span>',
            };

            $idBadge = match ($idStatus) {
                'approved' => '<span class="badge badge-success ml-1">ID OK</span>',
                'pending'  => '<span class="badge badge-warning ml-1">ID pending</span>',
                'rejected' => '<span class="badge badge-danger ml-1">ID rejected</span>',
                default    => '',
            };

            $actions = drms_user_table_actions(
                $id,
                $accountStatus,
                $group,
                $idStatus,
                ! empty($r['id_document_path']),
            );

            $displayName = trim((string) ($r['username'] ?? ''));
            if ($displayName === '') {
                $displayName = (string) ($r['email'] ?? '');
            }

            $data[] = [
                esc($displayName),
                esc($r['email'] ?? ''),
                esc(ucfirst(str_replace('_', ' ', (string) ($r['group'] ?? '')))),
                $badge . $idBadge,
                esc($r['created_at'] ?? ''),
                $actions,
            ];
        }

        return datatable_response($request, $data, $result['total'], $result['filtered']);
    }

    public function show(int $id): ResponseInterface
    {
        if (! drms_user_can('users.*', 'admin.*')) {
            return $this->crudFail('Forbidden.', 403);
        }

        try {
            return $this->crudOk('OK', (new UserManagementService())->find($id));
        } catch (RuntimeException $e) {
            return $this->crudFail($e->getMessage(), 404);
        }
    }

    public function store(): ResponseInterface
    {
        if (! drms_user_can('users.*', 'admin.*')) {
            return $this->crudFail('Forbidden.', 403);
        }

        try {
            $id = (new UserManagementService())->create($this->request->getPost(), auth()->id());

            return $this->crudOk('User created.', ['id' => $id]);
        } catch (RuntimeException $e) {
            return $this->crudFail($e->getMessage(), 400);
        }
    }

    public function update(int $id): ResponseInterface
    {
        if (! drms_user_can('users.*', 'admin.*')) {
            return $this->crudFail('Forbidden.', 403);
        }

        try {
            (new UserManagementService())->update($id, $this->request->getPost(), auth()->id());

            return $this->crudOk('User updated.');
        } catch (RuntimeException $e) {
            return $this->crudFail($e->getMessage(), 400);
        }
    }

    public function approve(int $id): ResponseInterface
    {
        if (! drms_user_can('users.*', 'admin.*')) {
            return $this->crudFail('Forbidden.', 403);
        }

        try {
            (new UserManagementService())->approve($id, auth()->id());

            return $this->crudOk('User approved and notified by email (if configured).');
        } catch (RuntimeException $e) {
            return $this->crudFail($e->getMessage(), 400);
        }
    }

    public function reject(int $id): ResponseInterface
    {
        if (! drms_user_can('users.*', 'admin.*')) {
            return $this->crudFail('Forbidden.', 403);
        }

        try {
            (new UserManagementService())->reject(
                $id,
                trim((string) $this->request->getPost('message')),
                auth()->id(),
            );

            return $this->crudOk('User rejected.');
        } catch (RuntimeException $e) {
            return $this->crudFail($e->getMessage(), 400);
        }
    }

    public function verifyId(int $id): ResponseInterface
    {
        if (! drms_user_can('users.*', 'admin.*')) {
            return $this->crudFail('Forbidden.', 403);
        }

        try {
            (new UserManagementService())->verifyIdDocument($id, auth()->id());

            return $this->crudOk('ID document verified.');
        } catch (RuntimeException $e) {
            return $this->crudFail($e->getMessage(), 400);
        }
    }

    public function delete(int $id): ResponseInterface
    {
        if (! drms_user_can('users.*', 'admin.*')) {
            return $this->crudFail('Forbidden.', 403);
        }

        try {
            (new UserManagementService())->delete($id, auth()->id());

            return $this->crudOk('User deactivated (soft delete — record kept in database).');
        } catch (RuntimeException $e) {
            return $this->crudFail($e->getMessage(), 400);
        }
    }
}
