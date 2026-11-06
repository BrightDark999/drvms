<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Models\IncidentModel;
use App\Services\IncidentMediaService;
use App\Services\IncidentService;
use App\Validation\IncidentRules;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use RuntimeException;

/**
 * REST CRUD for incidents (JWT / token / session via chain auth).
 */
class IncidentsApi extends ResourceController
{
    protected $format = 'json';

    public function index(): ResponseInterface
    {
        if (! $this->canRead()) {
            return $this->failForbidden('Requires admin.access or incidents.* permission.');
        }

        $limit = min(100, max(1, (int) ($this->request->getGet('limit') ?? 50)));

        $rows = model(IncidentModel::class)->builder()
            ->select('incidents.*, b.name AS barangay_name, (SELECT COUNT(*) FROM incident_media im WHERE im.incident_id = incidents.id) AS media_count')
            ->join('barangays b', 'b.id = incidents.barangay_id', 'left')
            ->where('incidents.deleted_at', null)
            ->orderBy('incidents.id', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();

        $service = new IncidentService();
        $data    = [];
        foreach ($rows as $row) {
            $data[] = $service->formatApiRow($row, (int) ($row['media_count'] ?? 0));
        }

        return $this->respond([
            'data' => $data,
            'meta' => ['count' => count($data)],
        ]);
    }

    public function show($id = null): ResponseInterface
    {
        $id = (int) $id;
        if ($id <= 0) {
            return $this->failNotFound('Incident not found.');
        }
        if (! $this->canRead()) {
            return $this->failForbidden('Requires admin.access or incidents.* permission.');
        }

        try {
            $row = (new IncidentService())->formatApiDetail($id);
        } catch (RuntimeException $e) {
            return $this->failNotFound($e->getMessage());
        }

        return $this->respond(['data' => $row]);
    }

    public function create(): ResponseInterface
    {
        if (! $this->canWrite()) {
            return $this->failForbidden('Requires incidents.* or admin.* permission.');
        }

        $input = $this->request->getJSON(true) ?? $this->request->getPost();

        if (! $this->validate(IncidentRules::create())) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        try {
            $row = (new IncidentService())->create($input, auth()->loggedIn() ? auth()->id() : null);

            return $this->respondCreated(['data' => (new IncidentService())->formatApiDetail((int) $row['id'])]);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    public function update($id = null): ResponseInterface
    {
        $id = (int) $id;
        if ($id <= 0) {
            return $this->failNotFound('Incident not found.');
        }
        if (! $this->canWrite()) {
            return $this->failForbidden('Requires incidents.* or admin.* permission.');
        }

        if (! model(IncidentModel::class)->find($id)) {
            return $this->failNotFound('Incident not found.');
        }

        $input = $this->request->getJSON(true) ?? $this->request->getRawInput();

        if (! $this->validate(IncidentRules::update())) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        if (! (new IncidentService())->update($id, $input)) {
            return $this->fail('Could not update incident.', 400);
        }

        try {
            $row = (new IncidentService())->formatApiDetail($id);
        } catch (RuntimeException $e) {
            return $this->failNotFound($e->getMessage());
        }

        return $this->respond(['data' => $row, 'message' => 'Incident updated.']);
    }

    public function delete($id = null): ResponseInterface
    {
        $id = (int) $id;
        if ($id <= 0) {
            return $this->failNotFound('Incident not found or could not be deleted.');
        }
        if (! $this->canWrite()) {
            return $this->failForbidden('Requires incidents.* or admin.* permission.');
        }

        if (! (new IncidentService())->delete($id)) {
            return $this->failNotFound('Incident not found or could not be deleted.');
        }

        return $this->respondDeleted(['message' => 'Incident deleted.']);
    }

    public function verify($id = null): ResponseInterface
    {
        $id = (int) $id;
        if ($id <= 0) {
            return $this->failNotFound('Incident not found.');
        }
        if (! $this->canWrite()) {
            return $this->failForbidden('Requires incidents.* or admin.* permission.');
        }

        $uid = auth()->id();
        if (! $uid || ! (new IncidentService())->verify($id, (int) $uid)) {
            return $this->fail('Could not verify incident.', 400);
        }

        return $this->respond(['message' => 'Incident verified.']);
    }

    public function uploadMedia($id = null): ResponseInterface
    {
        $id = (int) $id;
        if ($id <= 0) {
            return $this->failNotFound('Incident not found.');
        }
        if (! $this->canWrite()) {
            return $this->failForbidden('Requires incidents.* or admin.* permission.');
        }
        if (! model(IncidentModel::class)->find($id)) {
            return $this->failNotFound('Incident not found.');
        }

        $files = $this->request->getFileMultiple('media');
        if ($files === []) {
            return $this->fail('No media files were received.', 400);
        }

        try {
            $saved = (new IncidentMediaService())->attachToIncident($id, $files);
            if ($saved === []) {
                return $this->fail('No valid media files were uploaded. Use JPG, PNG, GIF, WEBP, MP4, WEBM, or MOV.', 400);
            }

            return $this->respond([
                'message' => 'Media uploaded.',
                'data'    => [
                    'media' => (new IncidentMediaService())->formatForApi($saved),
                ],
            ]);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        }
    }

    /**
     * Public incident report (no auth) — same data as web /report form.
     */
    public function publicReport(): ResponseInterface
    {
        $input = $this->request->getJSON(true) ?? $this->request->getPost();

        if (! $this->validate(IncidentRules::create())) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        try {
            $row = (new IncidentService())->create($input, null);

            return $this->respondCreated(['data' => (new IncidentService())->formatApiDetail((int) $row['id'])]);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    private function canRead(): bool
    {
        return drms_user_can('admin.access', 'incidents.*', 'admin.*');
    }

    private function canWrite(): bool
    {
        return drms_user_can('incidents.*', 'admin.*');
    }
}
