<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use Config\Integrations;
use RuntimeException;

/**
 * REST client for the external relief / inventory system.
 * DRMS no longer stores relief goods locally — this client connects when configured.
 */
class ExternalReliefApiClient
{
    public function __construct(
        private Integrations $config = new Integrations(),
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->config->reliefApiEnabled
            && trim($this->config->reliefApiBaseUrl) !== '';
    }

    /**
     * @return array<string, mixed>
     */
    public function health(): array
    {
        if (! $this->isEnabled()) {
            return [
                'connected' => false,
                'message'   => 'Relief API not configured. Set integrations.reliefApiBaseUrl in .env when the external system is ready.',
            ];
        }

        try {
            $response = $this->request('GET', '/health');

            return [
                'connected' => true,
                'message'   => 'External relief API reachable.',
                'data'      => $response,
            ];
        } catch (RuntimeException $e) {
            return [
                'connected' => false,
                'message'   => $e->getMessage(),
            ];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listInventory(): array
    {
        if ($this->isEnabled()) {
            try {
                $response = $this->request('GET', '/inventory');
                $data     = is_array($response['data'] ?? null) ? $response['data'] : [];
                if ($data !== []) {
                    return $data;
                }
            } catch (RuntimeException) {
                // fall through to shared database
            }
        }

        return (new \App\Services\InventoryService())->listForScope('all');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listDistributions(): array
    {
        $response = $this->request('GET', '/distributions');

        return is_array($response['data'] ?? null) ? $response['data'] : [];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function createDistribution(array $payload): array
    {
        $response = $this->request('POST', '/distributions', $payload);

        return is_array($response['data'] ?? null) ? $response['data'] : $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, ?array $body = null): array
    {
        if (! $this->isEnabled()) {
            throw new RuntimeException('External relief API is not enabled.');
        }

        $base = rtrim($this->config->reliefApiBaseUrl, '/');
        $url  = $base . '/' . ltrim($path, '/');

        $client = service('curlrequest', [
            'timeout' => $this->config->reliefApiTimeout,
        ]);

        $options = [
            'headers' => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ];

        if ($this->config->reliefApiKey !== '') {
            $options['headers']['Authorization'] = 'Bearer ' . $this->config->reliefApiKey;
        }

        if ($body !== null) {
            $options['json'] = $body;
        }

        $response = $client->request($method, $url, $options);
        $status   = $response->getStatusCode();
        $decoded  = json_decode($response->getBody(), true);

        if ($status >= 400) {
            $msg = is_array($decoded) ? (string) ($decoded['message'] ?? 'API error') : 'API error';

            throw new RuntimeException("Relief API {$status}: {$msg}");
        }

        return is_array($decoded) ? $decoded : [];
    }
}
