<?php

declare(strict_types=1);

namespace App\Commands;

use App\Services\NotificationService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Process pending rows in notification_queue (run via Task Scheduler or cron).
 *
 * php spark notifications:process
 */
class ProcessNotifications extends BaseCommand
{
    protected $group       = 'DRMS';
    protected $name        = 'notifications:process';
    protected $description = 'Send pending email/SMS notifications from the queue.';
    protected $usage       = 'notifications:process [limit]';

    public function run(array $params): void
    {
        $limit  = isset($params[0]) ? (int) $params[0] : 25;
        $result = (new NotificationService())->processPending($limit);

        CLI::write('Sent: ' . $result['sent'] . ', Failed: ' . $result['failed'], 'green');
    }
}
