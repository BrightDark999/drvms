<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Automation, alerts, webhooks, SMS, and optional AI settings (loaded from .env).
 */
class Automation extends BaseConfig
{
    /** Comma-separated emails for MDRRMO/LGU operational alerts */
    public string $alertEmails = 'mdrrmo@drms.local';

    /** Send queued notifications after the HTTP response (cron can also process the queue) */
    public bool $autoSendQueue = false;

    /** Secret for web cron: GET /cron/notifications?key=... */
    public string $cronSecret = '';

    /** Optional AI (OpenAI-compatible chat API) */
    public bool $aiEnabled = false;
    public string $aiApiKey = '';
    public string $aiApiUrl = 'https://api.openai.com/v1/chat/completions';
    public string $aiModel = 'gpt-4o-mini';

    /** Hours before a reported incident triggers a stale alert */
    public int $staleIncidentHours = 48;

    /** Evacuation occupancy percent to trigger alert */
    public int $evacOccupancyAlertPercent = 85;

    /** Incoming webhook URL (Slack, Teams, Discord, or generic JSON POST) */
    public string $webhookUrl = '';

    /** slack | teams | generic */
    public string $webhookFormat = 'slack';

    /** Queue SMS rows for critical alerts (adapter logs or POSTs to smsApiUrl) */
    public bool $smsEnabled = false;

    /** When true, SMS is logged as sent without an external gateway */
    public bool $smsDemoMode = true;

    /** Comma-separated E.164 or local numbers for critical SMS alerts */
    public string $smsAlertNumbers = '';

    /** Optional HTTP endpoint for SMS gateway (POST JSON: to, message) */
    public string $smsApiUrl = '';

    /** demo | semaphore | gateway */
    public string $smsProvider = 'demo';

    /** Semaphore SMS API key (Philippines) — https://semaphore.co */
    public string $semaphoreApiKey = '';

    /** Comma-separated keywords for social media flagging (flood, landslide, etc.) */
    public string $socialKeywords = 'flood,landslide,fire,earthquake,rescue,baras';

    /** Optional RSS/Atom feed URL for social monitor cron */
    public string $socialFeedUrl = '';

    /** Send daily operations digest via spark drms:digest */
    public bool $digestEnabled = true;

    public function __construct()
    {
        parent::__construct();

        $this->alertEmails           = (string) (env('drms.alertEmails') ?? $this->alertEmails);
        $this->autoSendQueue         = filter_var(env('drms.notifications.autoSend'), FILTER_VALIDATE_BOOLEAN)
            ?? $this->autoSendQueue;
        $this->cronSecret            = (string) (env('drms.cronSecret') ?? '');
        $this->aiEnabled             = filter_var(env('ai.enabled'), FILTER_VALIDATE_BOOLEAN) ?? false;
        $this->aiApiKey              = (string) (env('ai.apiKey') ?? '');
        $this->aiApiUrl              = (string) (env('ai.apiUrl') ?? $this->aiApiUrl);
        $this->aiModel               = (string) (env('ai.model') ?? $this->aiModel);
        $this->staleIncidentHours    = (int) (env('drms.staleIncidentHours') ?? $this->staleIncidentHours);
        $this->evacOccupancyAlertPercent = (int) (env('drms.evacOccupancyAlertPercent') ?? $this->evacOccupancyAlertPercent);
        $this->webhookUrl            = (string) (env('drms.webhookUrl') ?? '');
        $this->webhookFormat         = (string) (env('drms.webhookFormat') ?? $this->webhookFormat);
        $this->smsEnabled            = filter_var(env('drms.smsEnabled'), FILTER_VALIDATE_BOOLEAN) ?? false;
        $this->smsDemoMode           = filter_var(env('drms.smsDemoMode'), FILTER_VALIDATE_BOOLEAN) ?? $this->smsDemoMode;
        $this->smsAlertNumbers       = (string) (env('drms.smsAlertNumbers') ?? '');
        $this->smsApiUrl             = (string) (env('drms.smsApiUrl') ?? '');
        $this->smsProvider           = (string) (env('drms.smsProvider') ?? $this->smsProvider);
        $this->semaphoreApiKey       = (string) (env('drms.semaphoreApiKey') ?? '');
        $this->socialKeywords        = (string) (env('drms.socialKeywords') ?? $this->socialKeywords);
        $this->socialFeedUrl         = (string) (env('drms.socialFeedUrl') ?? '');
        $this->digestEnabled         = filter_var(env('drms.digestEnabled'), FILTER_VALIDATE_BOOLEAN) ?? $this->digestEnabled;
    }
}
