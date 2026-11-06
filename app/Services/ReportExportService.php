<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\IncidentModel;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class ReportExportService
{
    /**
     * @return array{filename:string, content:string, mime:string}
     */
    public function incidentsPdf(): array
    {
        $rows = model(IncidentModel::class)->orderBy('id', 'DESC')->findAll(500);
        $html = $this->wrapHtml('Incident report', $this->incidentsTableHtml($rows));

        return [
            'filename' => 'drms-incidents-' . date('Y-m-d') . '.pdf',
            'content'  => $this->renderPdf($html),
            'mime'     => 'application/pdf',
        ];
    }

    /**
     * @return array{filename:string, content:string, mime:string}
     */
    public function incidentsExcel(): array
    {
        $rows = model(IncidentModel::class)->orderBy('id', 'DESC')->findAll(5000);
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Incidents');
        $headers = ['Reference', 'Title', 'Severity', 'Status', 'Reported at', 'Description'];
        foreach ($headers as $col => $label) {
            $sheet->setCellValue([$col + 1, 1], $label);
        }
        $r = 2;
        foreach ($rows as $row) {
            $sheet->setCellValue([1, $r], $row['reference_code'] ?? '');
            $sheet->setCellValue([2, $r], $row['title'] ?? '');
            $sheet->setCellValue([3, $r], $row['severity'] ?? '');
            $sheet->setCellValue([4, $r], $row['status'] ?? '');
            $sheet->setCellValue([5, $r], $row['reported_at'] ?? $row['created_at'] ?? '');
            $sheet->setCellValue([6, $r], mb_substr((string) ($row['description'] ?? ''), 0, 500));
            $r++;
        }

        $tmp = WRITEPATH . 'uploads/drms-export-' . uniqid('', true) . '.xlsx';
        (new Xlsx($spreadsheet))->save($tmp);
        $content = (string) file_get_contents($tmp);
        @unlink($tmp);

        if ($content === '') {
            throw new RuntimeException('Could not generate Excel file.');
        }

        return [
            'filename' => 'drms-incidents-' . date('Y-m-d') . '.xlsx',
            'content'  => $content,
            'mime'     => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
    }

    /**
     * @return array{filename:string, content:string, mime:string}
     */
    public function operationsOverviewPdf(): array
    {
        $stats  = (new DashboardStatsService())->getOverview();
        $alerts = (new AlertsService())->getDashboardAlerts();
        $html   = '<h1>DRMS operations overview</h1><p>Generated ' . esc(date('Y-m-d H:i')) . '</p>';
        $html  .= '<table border="1" cellpadding="6" cellspacing="0" width="100%">';
        foreach ($stats as $key => $value) {
            $html .= '<tr><td><strong>' . esc(ucwords(str_replace('_', ' ', (string) $key))) . '</strong></td><td>' . esc((string) $value) . '</td></tr>';
        }
        $html .= '</table><h2>Active alerts</h2>';
        if ($alerts === []) {
            $html .= '<p>No active rule-based alerts.</p>';
        } else {
            $html .= '<ul>';
            foreach ($alerts as $a) {
                $html .= '<li><strong>' . esc($a['title'] ?? '') . '</strong>: ' . esc($a['message'] ?? '') . '</li>';
            }
            $html .= '</ul>';
        }

        return [
            'filename' => 'drms-overview-' . date('Y-m-d') . '.pdf',
            'content'  => $this->renderPdf($this->wrapHtml('Operations overview', $html)),
            'mime'     => 'application/pdf',
        ];
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function incidentsTableHtml(array $rows): string
    {
        $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%"><thead><tr>'
            . '<th>Reference</th><th>Title</th><th>Severity</th><th>Status</th><th>Reported</th></tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr><td>' . esc($row['reference_code'] ?? '') . '</td>'
                . '<td>' . esc($row['title'] ?? '') . '</td>'
                . '<td>' . esc($row['severity'] ?? '') . '</td>'
                . '<td>' . esc($row['status'] ?? '') . '</td>'
                . '<td>' . esc($row['reported_at'] ?? $row['created_at'] ?? '') . '</td></tr>';
        }
        $html .= '</tbody></table>';

        return $html;
    }

    private function wrapHtml(string $title, string $body): string
    {
        return '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' . esc($title) . '</title>'
            . '<style>body{font-family:DejaVu Sans,sans-serif;font-size:12px;}h1{font-size:18px;}</style></head><body>'
            . '<h1>' . esc($title) . '</h1>' . $body . '</body></html>';
    }

    private function renderPdf(string $html): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
