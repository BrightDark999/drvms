<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Services\AuthCatalogService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\Shield\Authentication\Authenticators\Session;

/**
 * JWT login + auth introspection (shared with relief integrations).
 */
class AuthApi extends ResourceController
{
    protected $format = 'json';

    /**
     * POST /api/v1/auth/login — email + password → JWT.
     */
    public function login(): ResponseInterface
    {
        $input = $this->request->getJSON(true) ?? $this->request->getPost();

        $email    = trim((string) ($input['email'] ?? ''));
        $password = (string) ($input['password'] ?? '');

        if ($email === '' || $password === '') {
            return $this->failValidationErrors([
                'email'    => 'Email is required.',
                'password' => 'Password is required.',
            ]);
        }

        /** @var Session $authenticator */
        $authenticator = auth('session')->getAuthenticator();
        $result        = $authenticator->attempt([
            'email'    => $email,
            'password' => $password,
        ]);

        if (! $result->isOK()) {
            return $this->failUnauthorized($result->reason() ?? 'Invalid credentials.');
        }

        $user = auth()->user();
        if ($user === null) {
            return $this->failUnauthorized('Invalid credentials.');
        }

        if (! (bool) $user->active) {
            auth()->logout();

            return $this->failUnauthorized('Account is not approved. Contact MDRRMO.');
        }

        $jwtConfig = config('AuthJWT');
        $manager   = service('jwtmanager');
        $token     = $manager->generateToken($user, [
            'username' => (string) ($user->username ?? ''),
            'email'    => (string) ($user->email ?? ''),
        ], $jwtConfig->timeToLive, 'default');

        auth()->logout();

        return $this->respond([
            'data' => [
                'token_type'   => 'Bearer',
                'access_token' => $token,
                'expires_in'   => $jwtConfig->timeToLive,
                'user'         => [
                    'id'       => (int) $user->id,
                    'username' => (string) ($user->username ?? ''),
                    'email'    => (string) ($user->email ?? ''),
                    'groups'   => method_exists($user, 'getGroups') ? $user->getGroups() : [],
                ],
            ],
        ]);
    }

    public function me(): ResponseInterface
    {
        if (! auth()->loggedIn()) {
            return $this->failUnauthorized('Authentication required. POST /api/v1/auth/login or send Authorization: Bearer {jwt}.');
        }

        return $this->respond([
            'data' => (new AuthCatalogService())->currentUserPayload(),
        ]);
    }
}
