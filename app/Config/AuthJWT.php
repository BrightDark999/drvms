<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Shield\Config\AuthJWT as ShieldAuthJWT;

/**
 * JWT signing keys for /api/v1 (Postman & relief integrations).
 *
 * Set in .env:
 *   auth.jwtSecret = base64:...   (min 32 bytes for HS256)
 *   auth.jwtTTL = 3600
 */
class AuthJWT extends ShieldAuthJWT
{
    public string $authenticatorHeader = 'Authorization';

    public array $defaultClaims = [
        'iss' => 'DRMS',
    ];

    public int $timeToLive = 3600;

    public function __construct()
    {
        parent::__construct();

        $secret = env('auth.jwtSecret');
        if (is_string($secret) && $secret !== '') {
            $this->keys['default'][0]['secret'] = $secret;
        } elseif (ENVIRONMENT !== 'production') {
            // Local-only fallback — set auth.jwtSecret in production.
            $this->keys['default'][0]['secret'] = 'drms-local-jwt-dev-secret-change-in-production-32b';
        }

        $ttl = env('auth.jwtTTL');
        if ($ttl !== null && $ttl !== '') {
            $this->timeToLive = (int) $ttl;
        }
    }
}
