<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Shield\Filters\PermissionFilter;

/**
 * Permission check for /api/v1 — return JSON 403 instead of redirecting.
 */
class ApiPermissionFilter extends PermissionFilter
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $result = parent::before($request, $arguments);

        if ($result === null) {
            return null;
        }

        if ($this->isApiRequest($request)) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON([
                    'status'  => 403,
                    'message' => 'You do not have permission for this action.',
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
