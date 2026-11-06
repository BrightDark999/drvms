<?php

declare(strict_types=1);

namespace App\Services;

use Config\Automation;
use Throwable;

/**
 * SMS delivery adapter — demo mode logs; optional HTTP gateway via drms.smsApiUrl.
 */
class SmsChannelService
{
    public function __construct(
        private Automation $config = new Automation(),
    ) {
    }

    public function send(string $to, string $message): bool
    {
        $to = $this->normalizePhone($to);
        if ($to === '') {
            return false;
        }

        if ($this->config->smsProvider === 'semaphore' && $this->config->semaphoreApiKey !== '') {
            return $this->sendSemaphore($to, $message);
        }

        if ($this->config->smsDemoMode && $this->config->smsApiUrl === '' && $this->config->smsProvider !== 'semaphore') {
            log_message('info', "[DRMS SMS demo] To: {$to} — {$message}");

            return true;
        }

        if ($this->config->smsApiUrl !== '') {
            return $this->postGateway($to, $message);
        }

        log_message('warning', 'SMS gateway not configured; message not sent to ' . $to);

        return false;
    }

    /**
     * @return list<string>
     */
    public function getAlertNumbers(): array
    {
        $numbers = [];
        $raw     = $this->config->smsAlertNumbers;
        if ($raw === '') {
            return [];
        }

        foreach (explode(',', $raw) as $n) {
            $n = $this->normalizePhone(trim($n));
            if ($n !== '') {
                $numbers[] = $n;
            }
        }

        return array_values(array_unique($numbers));
    }

    private function postGateway(string $to, string $message): bool
    {
        try {
            $client   = service('curlrequest');
            $response = $client->post($this->config->smsApiUrl, [
                'headers' => ['Content-Type' => 'application/json'],
                'json'    => ['to' => $to, 'message' => $message, 'source' => 'DRMS'],
                'timeout' => 20,
            ]);

            return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
        } catch (Throwable $e) {
            log_message('error', 'SmsChannelService: ' . $e->getMessage());

            return false;
        }
    }

    private function sendSemaphore(string $to, string $message): bool
    {
        try {
            $client   = service('curlrequest');
            $response = $client->post('https://api.semaphore.co/api/v4/messages', [
                'form_params' => [
                    'apikey'  => $this->config->semaphoreApiKey,
                    'number'  => $to,
                    'message' => mb_substr($message, 0, 480),
                    'sendername' => 'DRMS',
                ],
                'timeout' => 20,
            ]);
            $body = json_decode($response->getBody(), true);
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                return true;
            }
            log_message('error', 'Semaphore SMS: ' . json_encode($body));

            return false;
        } catch (Throwable $e) {
            log_message('error', 'Semaphore SMS: ' . $e->getMessage());

            return false;
        }
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^\d+]/', '', $phone) ?? '';

        return $phone;
    }
}
