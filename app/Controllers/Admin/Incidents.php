<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\Admin\Traits\CrudResponses;
use App\Models\BarangayModel;
use App\Models\IncidentModel;
use App\Services\IncidentDataTableService;
use App\Services\IncidentService;
use App\Validation\IncidentRules;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;
use Throwable;

/**
 * Disaster incident management — list (DataTables), create (AJAX), verify.
 */
class Incidents extends BaseController
{
    use CrudResponses;

    public function index(): string
    {
        $drms = drms_config();

        return view('admin/incidents/index', [
            'title'         => 'Disaster Incidents',
            'drms'          => $drms,
            'dataUrl'       => base_url('admin/incidents/data'),
            'barangays'     => drms_barangay_list(),
            'incidentTypes' => config('DrmsFeatures')->incidentTypes,
            'mapConfig'     => drms_leaflet_map_config(),
        ]);
    }

    /**
     * DataTables JSON — reads real rows from `incidents` table (not demo HTML).
     */
    public function data(): ResponseInterface
    {
        helper(['datatable', 'crud']);

        try {
            $service = new IncidentDataTableService();
            $result      = $service->fetch($this->request);
            $withActions = $this->request->getVar('no_actions') !== '1';
            $data        = $service->formatRows($result['rows'], $withActions);

            return datatable_response(
                $this->request,
                $data,
                $result['total'],
                $result['filtered'],
            );
        } catch (Throwable $e) {
            log_message('error', 'Incidents::data — ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

            return $this->response->setStatusCode(500)->setJSON([
                'draw'            => (int) ($this->request->getVar('draw') ?? 0),
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
                'error'           => ENVIRONMENT === 'development' ? $e->getMessage() : 'Could not load incidents.',
            ]);
        }
    }

    /**
     * Create incident via AJAX — persists to MySQL.
     */
    public function store(): ResponseInterface
    {
        $rules = IncidentRules::create();

        if (! $this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $this->validator->getErrors(),
            ]);
        }

        try {
            $service = new IncidentService();
            $row     = $service->create($this->request->getPost(), auth()->id());

            return $this->response->setJSON([
                'status'  => 'ok',
                'message' => 'Incident saved to database.',
                'data'    => $row,
            ]);
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verify incident (AJAX) — updates status in database.
     */
    public function show(int $id): ResponseInterface
    {
        $row = model(IncidentModel::class)->find($id);
        if (! $row) {
            return $this->crudFail('Incident not found.', 404);
        }
        $row['media'] = (new \App\Services\IncidentMediaService())->listForIncident($id);

        return $this->crudOk('OK', $row);
    }

    public function update(int $id): ResponseInterface
    {
        if (! $this->validate(IncidentRules::update())) {
            return $this->crudValidationFail();
        }

        if (! (new IncidentService())->update($id, $this->request->getPost())) {
            return $this->crudFail('Could not update incident.', 400);
        }

        return $this->crudOk('Incident updated in database.');
    }

    public function delete(int $id): ResponseInterface
    {
        if (! (new IncidentService())->delete($id)) {
            return $this->crudFail('Could not delete incident.', 400);
        }

        return $this->crudOk('Incident removed.');
    }

    public function verify(int $id): ResponseInterface
    {
        $service = new IncidentService();
        $uid     = auth()->id();

        if (! $uid || ! $service->verify($id, (int) $uid)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Could not verify incident.',
            ]);
        }

        return $this->response->setJSON([
            'status'  => 'ok',
            'message' => 'Incident verified in database.',
        ]);
    }

    public function uploadMedia(int $id): ResponseInterface
    {
        if (! model(IncidentModel::class)->find($id)) {
            return $this->crudFail('Incident not found.', 404);
        }

        $files = $this->request->getFileMultiple('media');
        if ($files === []) {
            return $this->crudFail('No media files were received.', 400);
        }

        try {
            $saved = (new \App\Services\IncidentMediaService())->attachToIncident($id, $files);
            if ($saved === []) {
                return $this->crudFail('No valid media files were uploaded.', 400);
            }

            return $this->crudOk('Media uploaded.', ['media' => $saved]);
        } catch (RuntimeException $e) {
            return $this->crudFail($e->getMessage(), 400);
        }
    }
}
