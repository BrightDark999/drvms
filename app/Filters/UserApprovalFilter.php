<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Block inactive (unapproved) users from using protected areas.
 */
class UserApprovalFilter implements FilterInterface
{
    /** @var list<string> */
    private array $exceptPrefixes = [
        'login',
        'register',
        'logout',
        'auth/',
        'cron/',
        'api/v1/health',
        'demo',
        'demo/',
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        if (! auth()->loggedIn()) {
            return null;
        }

        if (function_exists('drms_is_offline_demo_user') && drms_is_offline_demo_user()) {
            return null;
        }

        $user = auth()->user();
        if ($user === null || (bool) $user->active) {
            return null;
        }

        $path = trim($request->getUri()->getPath(), '/');
        foreach ($this->exceptPrefixes as $prefix) {
            if ($path === rtrim($prefix, '/') || str_starts_with($path, $prefix)) {
                return null;
            }
        }

        auth()->logout();

        $status = strtolower((string) ($user->status ?? 'pending'));
        if ($status === 'rejected') {
            return redirect()->to(url_to('login'))
                ->with('error', $user->status_message ?: 'Your account was not approved.');
        }

        return redirect()->to(url_to('pending-approval'))
            ->with('message', 'Your account must be approved by an administrator first.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
