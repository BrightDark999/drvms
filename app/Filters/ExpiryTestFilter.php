<?php

declare(strict_types=1);

namespace App\Filters;

use App\Services\ExpiryTestService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Applies post-expiry runtime behavior (maintenance / degraded modes).
 */
class ExpiryTestFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (is_cli()) {
            return null;
        }

        $service = new ExpiryTestService();

        return $service->enforceWebRequest();
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
