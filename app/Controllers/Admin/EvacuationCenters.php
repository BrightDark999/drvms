<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\Admin\Traits\CrudResponses;
use App\Models\EvacuationCenterModel;
use App\Services\EvacuationCenterService;
use App\Services\EvacuationLookupService;
use App\Services\ReportsAnalyticsService;
use App\Validation\EvacuationCenterRules;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

class EvacuationCenters extends BaseController
{
    use CrudResponses;

    public function index(): string
    {
        helper('drms');

        $svc      = new EvacuationLookupService();
        $centers  = $svc->enrichCenters(model(EvacuationCenterModel::class)->orderBy('name', 'ASC')->findAll(100));
        $summary  = (new ReportsAnalyticsService())->evacuationSummary();
        $mapCenters = [];

        foreach ($centers as $c) {
            if ($c['latitude'] === null || $c['longitude'] === null) {
                continue;
            }
            $mapCenters[] = [
                'id'              => (int) ($c['id'] ?? 0),
                'name'            => $c['name'] ?? '',
                'latitude'        => (float) $c['latitude'],
                'longitude'       => (float) $c['longitude'],
                'capacity'        => (int) ($c['capacity'] ?? 0),
                'occupancy'       => (int) ($c['current_occupancy'] ?? 0),
                'available_slots' => (int) ($c['available_slots'] ?? 0),
                'is_full'         => ! empty($c['is_full']),
                'status'          => (string) ($c['status'] ?? ''),
                'marker'          => drms_evac_map_marker(false, ! empty($c['is_full'])),
            ];
        }

        return view('admin/evacuation_centers/index', [
            'title'      => 'Evacuation Centers',
            'drms'       => drms_config(),
            'dataUrl'    => base_url('admin/evacuation-centers/data'),
            'summaryUrl' => base_url('admin/evacuation-centers/summary'),
            'barangays'  => drms_barangay_list(),
            'summary'    => $summary,
            'mapConfig'  => drms_leaflet_map_config(),
            'mapCenters' => $mapCenters,
            'centers'    => $centers,
        ]);
    }

    public function summary(): ResponseInterface
    {
        return $this->response->setJSON([
            'status'  => 'ok',
            'summary' => (new ReportsAnalyticsService())->evacuationSummary(),
        ]);
    }

    public function data(): ResponseInterface
    {
        helper('datatable');
        $request = $this->request;
        $start   = max(0, (int) $request->getVar('start'));
        $length  = min(100, max(5, (int) ($request->getVar('length') ?: 10)));

        $model   = model(EvacuationCenterModel::class);
        $total   = $model->countAllResults();
        $builder = $model->builder();
        $builder->select('evacuation_centers.*, b.name AS barangay_name')
            ->join('barangays b', 'b.id = evacuation_centers.barangay_id', 'left');

        $rows = $builder->orderBy('evacuation_centers.id', 'DESC')->limit($length, $start)->get()->getResultArray();

        $data = [];
        foreach ($rows as $r) {
            $id  = (int) ($r['id'] ?? 0);
            $cap = (int) ($r['capacity'] ?? 0);
            $occ = (int) ($r['current_occupancy'] ?? 0);
            $pct = $cap > 0 ? (int) round(($occ / $cap) * 100) : 0;
            $bar = $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success');
            $status = strtolower((string) ($r['status'] ?? ''));
            $statusBadge = match ($status) {
                'open'   => 'success',
                'full'   => 'danger',
                'closed' => 'secondary',
                default  => 'light',
            };

            $data[] = [
                esc($r['name'] ?? ''),
                esc($r['barangay_name'] ?? '—'),
                '<div class="drms-occ-cell">'
                    . '<div class="d-flex justify-content-between small mb-1"><span>' . $occ . ' / ' . $cap . '</span><strong>' . $pct . '%</strong></div>'
                    . '<div class="progress progress-xs"><div class="progress-bar ' . $bar . '" style="width:' . min(100, $pct) . '%"></div></div>'
                    . '<div class="small text-muted mt-1">' . (int) ($r['families_registered'] ?? 0) . ' families · ' . (int) ($r['medical_needs_count'] ?? 0) . ' medical</div>'
                    . '</div>',
                '<span class="badge badge-' . $statusBadge . '">' . esc(ucfirst($status)) . '</span>',
                drms_evac_table_actions($id),
            ];
        }

        return datatable_response($request, $data, $total, $total);
    }

    public function show(int $id): ResponseInterface
    {
        $row = model(EvacuationCenterModel::class)->find($id);
        if (! $row) {
            return $this->crudFail('Center not found.', 404);
        }

        return $this->crudOk('OK', $row);
    }

    public function store(): ResponseInterface
    {
        if (! $this->validate(EvacuationCenterRules::save())) {
            return $this->crudValidationFail();
        }

        try {
            $row = (new EvacuationCenterService())->create($this->request->getPost());

            return $this->crudOk('Evacuation center saved.', $row);
        } catch (RuntimeException $e) {
            return $this->crudFail($e->getMessage(), 500);
        }
    }

    public function update(int $id): ResponseInterface
    {
        if (! $this->validate(EvacuationCenterRules::save())) {
            return $this->crudValidationFail();
        }

        if (! (new EvacuationCenterService())->update($id, $this->request->getPost())) {
            return $this->crudFail('Could not update center.', 400);
        }

        return $this->crudOk('Evacuation center updated.');
    }

    public function delete(int $id): ResponseInterface
    {
        if (! (new EvacuationCenterService())->delete($id)) {
            return $this->crudFail('Could not delete center.', 400);
        }

        return $this->crudOk('Evacuation center deleted.');
    }

    public function families(int $centerId): ResponseInterface
    {
        if (! drms_evac_can_manage_center($centerId)) {
            return $this->crudFail('You can only manage families at your assigned evacuation center.', 403);
        }

        $center = model(EvacuationCenterModel::class)->find($centerId);
        if (! $center) {
            return $this->crudFail('Center not found.', 404);
        }

        $families = (new \App\Services\EvacFamilyService())->listEnrichedForCenter($centerId);
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

        return $this->response->setJSON([
            'status'   => 'ok',
            'center'   => [
                'id'                  => $centerId,
                'name'                => (string) ($center['name'] ?? ''),
                'families_registered' => (int) ($center['families_registered'] ?? 0),
            ],
            'families' => $families,
            'meta'     => [
                'count'       => count($families),
                'checked_in'  => $checkedIn,
                'checked_out' => $checkedOut,
                'pending'     => $pending,
            ],
        ]);
    }

    public function checkoutFamily(int $centerId, int $familyId): ResponseInterface
    {
        if (! drms_evac_can_manage_center($centerId)) {
            return $this->crudFail('You can only manage families at your assigned evacuation center.', 403);
        }

        try {
            $result = (new \App\Services\EvacFamilyService())->checkOut($familyId, $centerId);

            return $this->response->setJSON($result);
        } catch (RuntimeException $e) {
            return $this->crudFail($e->getMessage(), 400);
        }
    }

    public function registerFamily(int $centerId): ResponseInterface
    {
        if (! drms_evac_can_manage_center($centerId)) {
            return $this->crudFail('You can only register families at your assigned evacuation center.', 403);
        }

        try {
            $data = $this->request->getPost();
            $data['evacuation_center_id'] = $centerId;
            $family = (new \App\Services\EvacFamilyService())->register($data);

            return $this->response->setJSON(['status' => 'ok', 'message' => 'Family registered.', 'data' => $family]);
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
