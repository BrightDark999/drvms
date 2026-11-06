<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Models\EvacuationCenterModel;
use App\Services\EvacFamilyService;
use App\Services\EvacuationCenterService;
use App\Services\EvacuationLookupService;
use App\Services\ReportsAnalyticsService;
use App\Validation\EvacuationCenterRules;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use RuntimeException;

class EvacuationCentersApi extends ResourceController
{
    protected $format = 'json';

    public function index(): ResponseInterface
    {
        $rows = (new EvacuationLookupService())->enrichCenters(
            model(EvacuationCenterModel::class)->orderBy('name', 'ASC')->findAll(100)
        );

        return $this->respond([
            'data' => $rows,
            'meta' => ['count' => count($rows)],
        ]);
    }

    public function summary(): ResponseInterface
    {
        return $this->respond([
            'data' => (new ReportsAnalyticsService())->evacuationSummary(),
        ]);
    }

    public function show($id = null): ResponseInterface
    {
        $id = (int) $id;
        $row = model(EvacuationCenterModel::class)->find($id);
        if (! $row) {
            return $this->failNotFound('Evacuation center not found.');
        }

        return $this->respond(['data' => $row]);
    }

    public function create(): ResponseInterface
    {
        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        if (! $this->validate(EvacuationCenterRules::save())) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        try {
            $row = (new EvacuationCenterService())->create($input);

            return $this->respondCreated(['data' => $row, 'message' => 'Evacuation center created.']);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    public function update($id = null): ResponseInterface
    {
        $id = (int) $id;
        if ($id <= 0 || ! model(EvacuationCenterModel::class)->find($id)) {
            return $this->failNotFound('Evacuation center not found.');
        }

        $input = $this->request->getJSON(true) ?? $this->request->getRawInput();
        if (! $this->validate(EvacuationCenterRules::save())) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        if (! (new EvacuationCenterService())->update($id, $input)) {
            return $this->fail('Could not update center.', 400);
        }

        return $this->respond([
            'data'    => model(EvacuationCenterModel::class)->find($id),
            'message' => 'Evacuation center updated.',
        ]);
    }

    public function delete($id = null): ResponseInterface
    {
        $id = (int) $id;
        if ($id <= 0) {
            return $this->failNotFound('Evacuation center not found.');
        }

        if (! (new EvacuationCenterService())->delete($id)) {
            return $this->fail('Could not delete center.', 400);
        }

        return $this->respondDeleted(['message' => 'Evacuation center deleted.']);
    }

    public function families($centerId = null): ResponseInterface
    {
        $centerId = (int) $centerId;
        $center = model(EvacuationCenterModel::class)->find($centerId);
        if (! $center) {
            return $this->failNotFound('Center not found.');
        }

        $families = (new EvacFamilyService())->listEnrichedForCenter($centerId);
        $checkedIn = 0;
        $checkedOut = 0;
        $pending = 0;
        foreach ($families as $family) {
            match ($family['check_in_status'] ?? '') {
                'checked_in'  => $checkedIn++,
                'checked_out' => $checkedOut++,
                default       => $pending++,
            };
        }

        return $this->respond([
            'data' => [
                'center'   => ['id' => $centerId, 'name' => (string) ($center['name'] ?? '')],
                'families' => $families,
                'meta'     => [
                    'count'       => count($families),
                    'checked_in'  => $checkedIn,
                    'checked_out' => $checkedOut,
                    'pending'     => $pending,
                ],
            ],
        ]);
    }

    public function registerFamily($centerId = null): ResponseInterface
    {
        $centerId = (int) $centerId;
        if (! model(EvacuationCenterModel::class)->find($centerId)) {
            return $this->failNotFound('Center not found.');
        }

        try {
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $data['evacuation_center_id'] = $centerId;
            $family = (new EvacFamilyService())->register($data);

            return $this->respondCreated(['data' => $family, 'message' => 'Family registered.']);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        }
    }

    public function checkoutFamily($centerId = null, $familyId = null): ResponseInterface
    {
        try {
            $result = (new EvacFamilyService())->checkOut((int) $familyId, (int) $centerId);

            return $this->respond($result);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        }
    }
}
