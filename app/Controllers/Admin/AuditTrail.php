<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\AuditTrailService;
use CodeIgniter\HTTP\ResponseInterface;

class AuditTrail extends BaseController
{
    public function index(): string
    {
        $svc = new AuditTrailService();

        return view('admin/audit_trail/index', [
            'title'       => 'Audit Trail',
            'drms'        => drms_config(),
            'dataUrl'     => base_url('admin/audit-trail/data'),
            'summary'     => $svc->getSummary(),
            'trend'       => $svc->activityTrend(14),
            'timeline'    => $svc->timeline(30),
            'filterUsers' => $svc->filterUsers(),
            'entityTypes' => $svc->filterEntityTypes(),
            'summaryUrl'  => base_url('admin/audit-trail/summary'),
            'timelineUrl' => base_url('admin/audit-trail/timeline'),
            'exportUrl'   => base_url('admin/audit-trail/export'),
        ]);
    }

    public function data(): ResponseInterface
    {
        helper('datatable');
        $request = $this->request;
        $svc     = new AuditTrailService();
        $start   = max(0, (int) $request->getVar('start'));
        $length  = min(100, max(5, (int) ($request->getVar('length') ?: 25)));

        $totalBuilder = $svc->baseBuilder();
        $total        = (int) $totalBuilder->countAllResults();

        $filteredBuilder = $svc->baseBuilder();
        $svc->applyFilters($filteredBuilder, $request);
        $filtered = (int) $filteredBuilder->countAllResults();

        $rows = $filteredBuilder->orderBy('audit_logs.id', 'DESC')->limit($length, $start)->get()->getResultArray();

        return datatable_response(
            $request,
            $svc->formatRowsForTable($rows),
            $total,
            $filtered
        );
    }

    public function summary(): ResponseInterface
    {
        $svc = new AuditTrailService();

        return $this->response->setJSON([
            'status'  => 'ok',
            'summary' => $svc->getSummary(),
            'trend'   => $svc->activityTrend(14),
        ]);
    }

    public function timeline(): ResponseInterface
    {
        return $this->response->setJSON([
            'status'   => 'ok',
            'timeline' => (new AuditTrailService())->timeline(50, $this->request),
        ]);
    }

    public function show(int $id): ResponseInterface
    {
        $row = (new AuditTrailService())->findEnriched($id);
        if (! $row) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Audit entry not found.',
            ]);
        }

        return $this->response->setJSON([
            'status' => 'ok',
            'data'   => $row,
        ]);
    }

    public function export(): ResponseInterface
    {
        $svc     = new AuditTrailService();
        $builder = $svc->baseBuilder();
        $svc->applyFilters($builder, $this->request);
        $rows = $builder->orderBy('audit_logs.id', 'DESC')->limit(5000, 0)->get()->getResultArray();
        $csv  = $svc->toCsv($rows);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="drms-audit-' . date('Y-m-d') . '.csv"')
            ->setBody($csv);
    }
}
