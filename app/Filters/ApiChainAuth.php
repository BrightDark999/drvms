<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Shield\Filters\ChainAuth;

/**
 * Chain auth for /api/v1 — return JSON 401 instead of redirecting to login.
 */
class ApiChainAuth extends ChainAuth
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $result = parent::before($request, $arguments);

        if ($result === null) {
            return null;
        }

        if ($this->isApiRequest($request)) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status'  => 401,
                    'message' => 'Authentication required. Please log in again.',
                ]);
        }

        return $result;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
        parent::after($request, $response, $arguments);
    }

    private function isApiRequest(RequestInterface $request): bool
    {
        if (! $request instanceof IncomingRequest) {
            return false;
        }

        $path = trim($request->getUri()->getPath(), '/');

        return str_starts_with($path, 'api/v1');
    }
}
