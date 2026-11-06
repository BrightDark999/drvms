<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\DemoAuthService;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Client demo hub — one-click role logins without editing controllers.
 */
class Demo extends BaseController
{
    public function index(): string|RedirectResponse
    {
        $config = drms_config();
        if (! $config->demoModeEnabled) {
            return redirect()->to(url_to('login'))->with('error', 'Demo mode is disabled on this server.');
        }

        return view('demo/index', [
            'title'    => 'Client demo',
            'drms'     => $config,
            'accounts' => (new DemoAuthService())->accounts(),
        ]);
    }

    public function loginAs(string $role): RedirectResponse
    {
        if (! drms_config()->demoModeEnabled) {
            return redirect()->to(url_to('login'))->with('error', 'Demo mode is disabled.');
        }

        return (new DemoAuthService())->loginAs($role);
    }

    public function logout(): RedirectResponse
    {
        return (new DemoAuthService())->logout();
    }
}
