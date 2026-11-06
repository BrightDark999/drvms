<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\Admin\Traits\CrudResponses;
use App\Models\NotificationQueueModel;
use App\Services\NotificationService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Outbound notification queue — email/SMS jobs with manual or cron processing.
 */
class Notifications extends BaseController
{
    use CrudResponses;

    public function index(): string
    {
        return view('admin/notifications/index', [
            'title'   => 'Notifications',
            'drms'    => drms_config(),
            'dataUrl' => base_url('admin/notifications/data'),
        ]);
    }

    public function data(): ResponseInterface
    {
        helper('datatable');
        $request = $this->request;
        $start   = max(0, (int) $request->getVar('start'));
        $length  = min(100, max(5, (int) ($request->getVar('length') ?: 10)));

        $model = model(NotificationQueueModel::class);
        $total = $model->countAllResults();
        $rows  = $model->orderBy('id', 'DESC')->findAll($length, $start);

        $data = [];
        foreach ($rows as $r) {
            $id     = (int) ($r['id'] ?? 0);
            $status = (string) ($r['status'] ?? '');
            $data[] = [
                esc($r['channel'] ?? ''),
                esc($r['recipient'] ?? ''),
                esc($r['subject'] ?? '—'),
                esc(ucfirst($status)),
                esc($r['created_at'] ?? '—'),
                drms_table_actions($id, $status === 'pending', false),
            ];
        }

        return datatable_response($request, $data, $total, $total);
    }

    /**
     * Process pending queue now (AJAX).
     */
    public function delete(int $id): ResponseInterface
    {
        $model = model(NotificationQueueModel::class);
        $row   = $model->find($id);
        if (! $row) {
            return $this->crudFail('Notification not found.', 404);
        }
        if (($row['status'] ?? '') !== 'pending') {
            return $this->crudFail('Only pending notifications can be cancelled.', 400);
        }

        if (! $model->delete($id)) {
            return $this->crudFail('Could not cancel notification.', 400);
        }

        return $this->crudOk('Pending notification cancelled.');
    }

    public function processNow(): ResponseInterface
    {
        $result = (new NotificationService())->processPending(50);

        return $this->response->setJSON([
            'status' => 'ok',
            'message'=> "Processed queue: {$result['sent']} sent, {$result['failed']} failed.",
            'data'   => $result,
        ]);
    }
}
