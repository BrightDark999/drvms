<?php

declare(strict_types=1);

namespace App\Filters;

use App\Services\DemoAuthService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Re-hydrates offline demo users on each request (no database row required).
 */
class DemoSessionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (is_cli() || session('drms_demo_user') === null) {
            return null;
        }

        (new DemoAuthService())->restoreSessionUser();

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
