<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Services\Integrations\ExternalReliefApiClient;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use RuntimeException;

/**
 * Bridge to external relief / inventory system (separate codebase).
 */
class IntegrationsApi extends ResourceController
{
    protected $format = 'json';

    public function reliefStatus(): ResponseInterface
    {
        return $this->respond([
            'data' => (new ExternalReliefApiClient())->health(),
        ]);
    }

    public function reliefInventory(): ResponseInterface
    {
        try {
            return $this->respond([
                'data' => (new ExternalReliefApiClient())->listInventory(),
            ]);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 503);
        }
    }

    public function reliefDistributions(): ResponseInterface
    {
        try {
            return $this->respond([
                'data' => (new ExternalReliefApiClient())->listDistributions(),
            ]);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 503);
        }
    }
}
