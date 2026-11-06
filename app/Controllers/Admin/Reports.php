<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\ReportExportService;
use App\Services\ReportsAnalyticsService;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

class Reports extends BaseController
{
    public function index(): string
    {
        helper('drms');

        $analytics = (new ReportsAnalyticsService())->getBundle();

        return view('admin/reports/index', [
            'title'     => 'Reports & Analytics',
            'drms'      => drms_config(),
            'analytics' => $analytics,
            'overview'  => $analytics['overview'] ?? [],
            'statsUrl'  => base_url('admin/reports/analytics'),
        ]);
    }

    public function analytics(): ResponseInterface
    {
        return $this->response->setJSON([
            'status'    => 'ok',
            'analytics' => (new ReportsAnalyticsService())->getBundle(),
        ]);
    }

    public function stats(): ResponseInterface
    {
        return $this->analytics();
    }

    public function export(string $type): ResponseInterface
    {
        try {
            $export = new ReportExportService();
            $file   = match ($type) {
                'incidents-pdf'  => $export->incidentsPdf(),
                'incidents-xlsx' => $export->incidentsExcel(),
                'overview-pdf'   => $export->operationsOverviewPdf(),
                default          => throw new RuntimeException('Unknown export type.'),
            };
        } catch (RuntimeException $e) {
            return redirect()->to(base_url('admin/reports'))->with('error', $e->getMessage());
        }

        return $this->response
            ->setHeader('Content-Type', $file['mime'])
            ->setHeader('Content-Disposition', 'attachment; filename="' . $file['filename'] . '"')
            ->setBody($file['content']);
    }
}
