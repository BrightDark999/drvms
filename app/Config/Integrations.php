<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Relief & logistics module integration (inventory / distributions — external service).
 *
 * Set in .env when the relief microservice is deployed:
 *   integrations.reliefApiEnabled = true
 *   integrations.reliefApiBaseUrl = https://relief.example.gov.ph/api/v1
 *   integrations.reliefApiKey = your-shared-secret
 */
class Integrations extends BaseConfig
{
    public bool $reliefApiEnabled = false;

    public string $reliefApiBaseUrl = '';

    public string $reliefApiKey = '';

    public int $reliefApiTimeout = 15;

    public function __construct()
    {
        parent::__construct();

        $enabled = env('integrations.reliefApiEnabled');
        if ($enabled !== null && $enabled !== '') {
            $this->reliefApiEnabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
        }

        $base = env('integrations.reliefApiBaseUrl');
        if (is_string($base) && $base !== '') {
            $this->reliefApiBaseUrl = $base;
        }

        $key = env('integrations.reliefApiKey');
        if (is_string($key) && $key !== '') {
            $this->reliefApiKey = $key;
        }

        $timeout = env('integrations.reliefApiTimeout');
        if (is_numeric($timeout)) {
            $this->reliefApiTimeout = (int) $timeout;
        }
    }
}
