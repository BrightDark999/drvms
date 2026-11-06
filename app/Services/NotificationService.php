<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\NotificationQueueModel;
use CodeIgniter\Shield\Models\UserModel;
use Config\Automation;
use Throwable;

/**
 * Queues and sends operational notifications (email first; SMS row = future adapter).
 */
class NotificationService
{
    private int $batchDepth = 0;

    private static bool $flushScheduled = false;

    public function __construct(
        private NotificationQueueModel $queue = new NotificationQueueModel(),
        private Automation $config = new Automation(),
    ) {
    }

    public function beginBatch(): void
    {
        $this->batchDepth++;
    }

    public function endBatch(): void
    {
        if ($this->batchDepth > 0) {
            $this->batchDepth--;
        }

        if ($this->batchDepth === 0) {
            $this->scheduleFlush();
        }
    }

    /**
     * Queue a message; delivery is batched and deferred (see /cron/notifications).
     */
    public function queue(
        string $channel,
        string $recipient,
        string $subject,
        string $body,
        ?array $meta = null,
    ): int {
        $this->queue->insert([
            'channel'   => $channel,
            'recipient' => $recipient,
            'subject'   => $subject,
            'body'      => $body,
            'status'    => 'pending',
            'meta_json' => $meta ? json_encode($meta) : null,
            'created_at'=> date('Y-m-d H:i:s'),
        ]);

        $id = (int) $this->queue->getInsertID();

        if ($this->batchDepth === 0) {
            $this->scheduleFlush();
        }

        return $id;
    }

    private function scheduleFlush(): void
    {
        if (! $this->config->autoSendQueue || self::$flushScheduled) {
            return;
        }

        self::$flushScheduled = true;

        DeferredService::afterResponse(function (): void {
            self::$flushScheduled = false;
            (new self())->processPending(50);
        });
    }

    /**
     * Notify all operations emails (env list + MDRRMO/admin Shield users).
     */
    public function notifyOperations(string $subject, string $body, ?array $meta = null): void
    {
        foreach ($this->getOperationsEmails() as $email) {
            $this->queue('email', $email, $subject, $body, $meta);
        }
    }

    public function onIncidentCreated(array $incident): void
    {
        $ref  = $incident['reference_code'] ?? 'N/A';
        $title = $incident['title'] ?? 'Untitled';
        $sev  = strtoupper((string) ($incident['severity'] ?? ''));
        $link = base_url('admin/incidents');

        $body = "A new disaster incident was reported.\n\n"
            . "Reference: {$ref}\n"
            . "Title: {$title}\n"
            . "Severity: {$sev}\n"
            . "Status: reported\n\n"
            . "Review and verify: {$link}";

        $this->notifyOperations("[DRMS] New incident {$ref}", $body, ['event' => 'incident.created', 'id' => $incident['id'] ?? null]);

        if (($incident['severity'] ?? '') === 'critical') {
            $this->notifyOperations(
                "[DRMS] CRITICAL incident {$ref}",
                "CRITICAL severity incident requires immediate action.\n\n{$body}",
                ['event' => 'incident.critical', 'id' => $incident['id'] ?? null],
            );

            (new WebhookService())->send(
                "[DRMS] CRITICAL incident {$ref}",
                "{$title}\nSeverity: {$sev}\n{$link}",
                ['event' => 'incident.critical', 'reference' => $ref],
            );

            if ($this->config->smsEnabled) {
                $sms = new SmsChannelService();
                $msg = "DRMS CRITICAL: {$ref} — {$title}. Review: {$link}";
                foreach ($sms->getAlertNumbers() as $number) {
                    $this->queue('sms', $number, 'DRMS Critical Alert', $msg, ['event' => 'incident.critical']);
                }
            }
        }
    }

    public function onIncidentVerified(array $incident): void
    {
        $ref = $incident['reference_code'] ?? 'N/A';
        $this->notifyOperations(
            "[DRMS] Incident verified {$ref}",
            "Incident {$ref} has been verified. Coordinate volunteers: " . base_url('admin/matching?incident_id=' . ($incident['id'] ?? '')),
            ['event' => 'incident.verified'],
        );
    }

    public function onEvacuationAdvisory(array $incident): void
    {
        $priority = strtoupper((string) ($incident['priority_tag'] ?? ''));
        $types    = ['flood', 'typhoon', 'landslide', 'earthquake'];
        $type     = (string) ($incident['incident_type'] ?? '');

        if ($priority !== 'HIGH' && ! in_array($type, $types, true)) {
            return;
        }

        $ref  = $incident['reference_code'] ?? 'N/A';
        $link = base_url('evac-centers');
        $body = "Evacuation advisory for {$ref}: " . ($incident['title'] ?? '') . "\n\n"
            . "Check nearest open centers: {$link}\n"
            . "MDRRMO should monitor occupancy and redirect families if needed.";

        $this->notifyOperations("[DRMS] Evacuation advisory {$ref}", $body, ['event' => 'evacuation.advisory']);

        if ($this->config->smsEnabled) {
            $sms = new SmsChannelService();
            $msg = "DRMS EVAC ADVISORY: {$ref}. Nearest centers: {$link}";
            foreach ($sms->getAlertNumbers() as $number) {
                $this->queue('sms', $number, 'DRMS Evacuation Advisory', $msg, ['event' => 'evacuation.advisory']);
            }
        }
    }

    public function onVolunteerDeployed(array $volunteer, array $incident): void
    {
        $email = $volunteer['email'] ?? '';
        if ($email === '') {
            return;
        }

        $ref = $incident['reference_code'] ?? '';
        $this->queue(
            'email',
            $email,
            '[DRMS] Deployment assignment',
            "You are assigned to incident {$ref}: " . ($incident['title'] ?? '') . "\n\n"
            . 'Contact MDRRMO for details. Thank you for volunteering.',
            ['event' => 'volunteer.deployed'],
        );
    }

    /**
     * @return array{sent:int, failed:int}
     */
    public function processPending(int $limit = 25): array
    {
        $sent   = 0;
        $failed = 0;

        $rows = $this->queue->where('status', 'pending')->orderBy('id', 'ASC')->findAll($limit);

        foreach ($rows as $row) {
            $ok = false;
            try {
                if (($row['channel'] ?? '') === 'email') {
                    $ok = $this->sendEmail((string) $row['recipient'], (string) $row['subject'], (string) ($row['body'] ?? ''));
                } elseif (($row['channel'] ?? '') === 'sms') {
                    $ok = (new SmsChannelService())->send(
                        (string) $row['recipient'],
                        (string) ($row['body'] ?? $row['subject'] ?? ''),
                    );
                } else {
                    $ok = true;
                    log_message('info', 'Notification channel not implemented: ' . $row['channel']);
                }
            } catch (Throwable $e) {
                log_message('error', 'Notification send failed: ' . $e->getMessage());
            }

            $this->queue->update($row['id'], [
                'status'  => $ok ? 'sent' : 'failed',
                'sent_at' => $ok ? date('Y-m-d H:i:s') : null,
            ]);

            $ok ? $sent++ : $failed++;
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * @return list<string>
     */
    public function getOperationsEmails(): array
    {
        $emails = [];
        $raw    = $this->config->alertEmails;
        if ($raw !== '') {
            foreach (explode(',', $raw) as $e) {
                $e = trim($e);
                if ($e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL)) {
                    $emails[] = $e;
                }
            }
        }

        try {
            $db    = db_connect();
            $groups = ['mdrrmo', 'admin'];
            $userIds = $db->table('auth_groups_users')
                ->select('user_id')
                ->whereIn('group', $groups)
                ->get()
                ->getResultArray();

            $userModel = model(UserModel::class);
            foreach ($userIds as $row) {
                $user = $userModel->findById((int) $row['user_id']);
                if ($user && $user->email && filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                    $emails[] = $user->email;
                }
            }
        } catch (Throwable $e) {
            log_message('error', 'getOperationsEmails: ' . $e->getMessage());
        }

        return array_values(array_unique($emails));
    }

    private function sendEmail(string $to, string $subject, string $body): bool
    {
        $email = service('email');
        $email->setTo($to);
        $email->setSubject($subject);
        $email->setMailType('html');
        $email->setMessage(nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8')));

        return $email->send(false);
    }
}
