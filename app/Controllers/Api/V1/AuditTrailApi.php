<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Services\AuditTrailService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class AuditTrailApi extends ResourceController
{
    protected $format = 'json';

    public function index(): ResponseInterface
    {
        $svc    = new AuditTrailService();
        $limit  = min(100, max(1, (int) ($this->request->getGet('limit') ?? 50)));
        $offset = max(0, (int) ($this->request->getGet('offset') ?? 0));

        $builder = $svc->baseBuilder();
        $svc->applyFilters($builder, $this->request);
        $total = (int) $builder->countAllResults(false);
        $rows  = $builder->orderBy('audit_logs.id', 'DESC')->limit($limit, $offset)->get()->getResultArray();

        return $this->respond([
            'data' => $rows,
            'meta' => ['count' => count($rows), 'total' => $total, 'offset' => $offset, 'limit' => $limit],
        ]);
    }

    public function show($id = null): ResponseInterface
    {
        $row = (new AuditTrailService())->findEnriched((int) $id);
        if (! $row) {
            return $this->failNotFound('Audit entry not found.');
        }

        return $this->respond(['data' => $row]);
    }

    public function summary(): ResponseInterface
    {
        $svc = new AuditTrailService();

        return $this->respond([
            'data' => [
                'summary' => $svc->getSummary(),
                'trend'   => $svc->activityTrend(14),
            ],
        ]);
    }

    public function timeline(): ResponseInterface
    {
        return $this->respond([
            'data' => (new AuditTrailService())->timeline(50, $this->request),
        ]);
    }
}
