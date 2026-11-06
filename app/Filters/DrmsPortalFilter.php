<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Redirect limited-role users away from modules they cannot access.
 */
class DrmsPortalFilter implements FilterInterface
{
    /** @var array<string, list<string>> URI prefix => required permission (any match) */
    private array $modulePermissions = [
        'admin/incidents'            => ['incidents.*', 'admin.*'],
        'admin/volunteers'           => ['volunteers.*', 'admin.*'],
        'admin/matching'             => ['volunteers.*', 'matching.access', 'admin.*'],
        'admin/evacuation-centers'   => ['evacuation.*', 'admin.*'],
        'admin/donations'            => ['donations.*', 'admin.*'],
        'admin/notifications'        => ['notifications.*', 'admin.*'],
        'admin/reports'              => ['reports.*', 'admin.*'],
        'admin/audit-trail'          => ['audit.*', 'admin.*'],
        'admin/users'                => ['users.*', 'admin.*'],
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        if (! auth()->loggedIn()) {
            return redirect()->to(url_to('login'));
        }

        $path = trim($request->getUri()->getPath(), '/');
        if (! str_starts_with($path, 'admin/')) {
            return null;
        }

        if ($path === 'admin' || str_starts_with($path, 'admin/dashboard')) {
            return null;
        }

        if (str_starts_with($path, 'admin/messages')) {
            return auth()->user()->can('messages.access') || auth()->user()->can('admin.*')
                ? null
                : redirect()->to(base_url('admin/dashboard'))->with('error', 'Access denied.');
        }

        foreach ($this->modulePermissions as $prefix => $permissions) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                foreach ($permissions as $perm) {
                    if (auth()->user()->can($perm)) {
                        return null;
                    }
                }

                return redirect()->to(base_url('admin/dashboard'))
                    ->with('error', 'You do not have permission to access that module.');
            }
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
