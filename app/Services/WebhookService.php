<?php

declare(strict_types=1);

namespace App\Services;

use Config\Automation;
use Throwable;

/**
 * Posts operational alerts to Slack, Microsoft Teams, or a generic webhook URL.
 */
class WebhookService
{
    public function __construct(
        private Automation $config = new Automation(),
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->config->webhookUrl !== '';
    }

    /**
     * @param array<string, mixed> $context
     */
    public function send(string $title, string $message, array $context = []): bool
    {
        $url = $this->config->webhookUrl;
        if ($url === '') {
            return false;
        }

        $payload = $this->buildPayload($title, $message, $context);

        try {
            $client   = service('curlrequest');
            $response = $client->post($url, [
                'headers' => ['Content-Type' => 'application/json'],
                'json'    => $payload,
                'timeout' => 15,
            ]);

            $ok = $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
            if (! $ok) {
                log_message('error', 'Webhook HTTP ' . $response->getStatusCode() . ': ' . $response->getBody());
            }

            return $ok;
        } catch (Throwable $e) {
            log_message('error', 'WebhookService: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function buildPayload(string $title, string $message, array $context): array
    {
        $format = strtolower($this->config->webhookFormat);
        $body   = $message;
        if ($context !== []) {
            $body .= "\n\n" . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        if ($format === 'teams') {
            return [
                '@type'    => 'MessageCard',
                '@context' => 'https://schema.org/extensions',
                'summary'  => $title,
                'themeColor' => 'D13438',
                'title'    => $title,
                'text'     => $body,
            ];
        }

        if ($format === 'generic') {
            return [
                'title'   => $title,
                'message' => $body,
                'context' => $context,
                'source'  => 'DRMS',
                'sent_at' => date('c'),
            ];
        }

        return [
            'text' => "*{$title}*\n{$body}",
        ];
    }
}
