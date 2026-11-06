<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Authentication\Authenticators\Session;
use CodeIgniter\Shield\Controllers\LoginController as ShieldLoginController;

class LoginController extends ShieldLoginController
{
    public function loginAction(): RedirectResponse
    {
        $rules = $this->getValidationRules();

        if (! $this->validateData($this->request->getPost(), $rules, [], config('Auth')->DBGroup)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        /** @var array<string, string> $credentials */
        $credentials             = $this->request->getPost(setting('Auth.validFields')) ?? [];
        $credentials             = array_filter($credentials);
        $credentials['password'] = (string) $this->request->getPost('password');
        $remember                = (bool) $this->request->getPost('remember');

        /** @var Session $authenticator */
        $authenticator = auth('session')->getAuthenticator();
        $result        = $authenticator->remember($remember)->attempt($credentials);

        if (! $result->isOK()) {
            return redirect()->route('login')->withInput()->with('error', $result->reason());
        }

        $user = auth()->user();
        if ($user !== null && ! (bool) $user->active) {
            auth()->logout();

            $status = strtolower((string) ($user->status ?? 'pending'));
            if ($status === 'rejected') {
                $msg = $user->status_message ?: 'Your registration was not approved. Contact MDRRMO for assistance.';

                return redirect()->route('login')->withInput()->with('error', $msg);
            }

            return redirect()->to(url_to('pending-approval'))
                ->with('message', 'Your account is pending administrator approval.');
        }

        if ($authenticator->hasAction()) {
            return redirect()->route('auth-action-show')->withCookies();
        }

        helper('drms');

        return redirect()->to(drms_login_redirect())->withCookies();
    }
}
