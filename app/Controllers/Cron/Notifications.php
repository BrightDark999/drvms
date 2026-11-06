<?php

declare(strict_types=1);

namespace App\Controllers\Cron;

use App\Controllers\BaseController;
use App\Services\NotificationService;
use Config\Automation;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Web-accessible cron endpoint for shared hosting (protect with secret key).
 *
 * GET /cron/notifications?key=YOUR_SECRET
 */
class Notifications extends BaseController
{
    public function process(): ResponseInterface
    {
        $config = config(Automation::class);
        $key    = (string) $this->request->getGet('key');

        if ($config->cronSecret === '' || ! hash_equals($config->cronSecret, $key)) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $result = (new NotificationService())->processPending(50);

        return $this->response->setJSON([
            'status' => 'ok',
            'sent'   => $result['sent'],
            'failed' => $result['failed'],
        ]);
    }
}
