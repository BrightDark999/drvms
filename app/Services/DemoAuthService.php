<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Authentication\Authenticators\Session;
use CodeIgniter\Shield\Entities\User;
use Config\Drms;
use Throwable;

/**
 * One-click demo logins for client presentations (database or offline session).
 */
class DemoAuthService
{
    private const OFFLINE_ID_BASE = 900_000;

    public function __construct(
        private Drms $config = new Drms(),
    ) {
    }

    /**
     * @return array<string, array{label:string, username:string, email:string, group:string, redirect:string}>
     */
    public function accounts(): array
    {
        return $this->config->demoAccounts;
    }

    public function loginAs(string $roleKey): RedirectResponse
    {
        $account = $this->accounts()[$roleKey] ?? null;
        if ($account === null) {
            return redirect()->to(base_url('demo'))->with('error', 'Unknown demo role.');
        }

        $this->clearSessionForRoleSwitch();

        if ($this->config->demoPreferDatabaseLogin && $this->tryDatabaseLogin($account)) {
            $this->markPresentationSession($roleKey, null);

            return redirect()->to(base_url($account['redirect']))
                ->with('message', 'Demo login: ' . $account['label'] . ' (database). Read-only demo mode is on.');
        }

        $this->loginOffline($roleKey, $account);

        return redirect()->to(base_url($account['redirect']))
            ->with('message', 'Demo login: ' . $account['label'] . ' (offline preview). Read-only demo mode is on.');
    }

    /**
     * Must run before any demo login — Shield throws if startLogin/attempt runs while already logged in.
     */
    private function clearSessionForRoleSwitch(): void
    {
        session()->remove('drms_demo_user');
        session()->remove('drms_presentation');
        session()->remove('drms_demo_role');

        if (auth()->loggedIn()) {
            auth()->logout();
        }
    }

    public function logout(): RedirectResponse
    {
        session()->remove('drms_demo_user');
        session()->remove('drms_presentation');
        session()->remove('drms_demo_role');

        if (auth()->loggedIn()) {
            auth()->logout();
        }

        return redirect()->to(base_url('demo'))->with('message', 'Demo session ended.');
    }

    /**
     * Restore offline demo user into Shield session (called from filter).
     */
    public function restoreSessionUser(): void
    {
        $demo = session('drms_demo_user');
        if (! is_array($demo) || ! isset($demo['id'], $demo['groups'])) {
            return;
        }

        $user = new User([
            'id'       => $demo['id'],
            'username' => $demo['username'] ?? 'demo',
            'email'    => $demo['email'] ?? 'demo@drms.local',
            'active'   => 1,
        ]);
        $user->setGroupsCache($demo['groups']);

        $auth = auth('session')->getAuthenticator();
        if (! $auth instanceof Session) {
            return;
        }

        $this->injectAuthenticatorState($auth, $user);
    }

    /**
     * @param array{label:string, username:string, email:string, group:string, redirect:string} $account
     */
    private function tryDatabaseLogin(array $account): bool
    {
        try {
            /** @var Session $authenticator */
            $authenticator = auth('session')->getAuthenticator();
            $password      = $this->config->demoPassword;

            $attempts = [
                ['email' => $account['email'], 'password' => $password],
                ['username' => $account['username'], 'password' => $password],
            ];

            foreach ($attempts as $credentials) {
                $result = $authenticator->remember(false)->attempt($credentials);

                if (! $result->isOK()) {
                    continue;
                }

                $user = auth()->user();
                if ($user !== null && ! (bool) $user->active) {
                    auth()->logout();

                    return false;
                }

                return true;
            }

            if (auth()->loggedIn()) {
                auth()->logout();
            }

            return false;
        } catch (Throwable $e) {
            log_message('info', 'DemoAuthService DB login skipped: ' . $e->getMessage());

            if (auth()->loggedIn()) {
                auth()->logout();
            }

            return false;
        }
    }

    /**
     * @param array{label:string, username:string, email:string, group:string, redirect:string} $account
     */
    private function loginOffline(string $roleKey, array $account): void
    {
        $offset = array_search($roleKey, array_keys($this->accounts()), true);
        $userId = self::OFFLINE_ID_BASE + ($offset === false ? 1 : $offset + 1);

        $snapshot = [
            'id'       => $userId,
            'username' => $account['username'],
            'email'    => $account['email'],
            'groups'   => [$account['group']],
            'label'    => $account['label'],
        ];

        session()->set('drms_demo_user', $snapshot);
        $this->markPresentationSession($roleKey, $snapshot);

        $user = new User([
            'id'       => $userId,
            'username' => $account['username'],
            'email'    => $account['email'],
            'active'   => 1,
        ]);
        $user->setGroupsCache([$account['group']]);

        /** @var Session $authenticator */
        $authenticator = auth('session')->getAuthenticator();
        $authenticator->startLogin($user);
        $authenticator->completeLogin($user);
    }

    /**
     * @param array<string, mixed>|null $snapshot
     */
    private function markPresentationSession(string $roleKey, ?array $snapshot): void
    {
        if ($this->config->presentationData) {
            session()->set('drms_presentation', true);
        }
        session()->set('drms_demo_role', $roleKey);

        if ($snapshot !== null) {
            session()->set('drms_demo_user', $snapshot);
        } else {
            session()->remove('drms_demo_user');
        }
    }

    private function injectAuthenticatorState(Session $auth, User $user): void
    {
        $ref = new \ReflectionClass($auth);

        foreach (['user' => $user, 'userState' => 3] as $property => $value) {
            if (! $ref->hasProperty($property)) {
                continue;
            }
            $prop = $ref->getProperty($property);
            $prop->setAccessible(true);
            $prop->setValue($auth, $value);
        }
    }
}
