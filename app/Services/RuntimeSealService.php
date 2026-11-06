<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\HTTP\ResponseInterface;
use Config\RuntimeSeal;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/**
 * Signed runtime seal — verifies an evaluation window without trusting local config edits.
 */
class RuntimeSealService
{
    private RuntimeSeal $config;

    private ?array $payload = null;

    private ?bool $payloadValid = null;

    public function __construct(?RuntimeSeal $config = null)
    {
        $this->config = $config ?? config('RuntimeSeal');
    }

    public function isArmed(): bool
    {
        return $this->config->license !== '' && $this->config->publicKey !== '';
    }

    public function payload(): ?array
    {
        if ($this->payloadValid === false) {
            return null;
        }

        if ($this->payload !== null) {
            return $this->payload;
        }

        $this->payload = $this->verifyLicense($this->config->license);
        $this->payloadValid = $this->payload !== null;

        return $this->payload;
    }

    public function isExpired(?DateTimeImmutable $now = null): bool
    {
        if (! $this->isArmed()) {
            return false;
        }

        $payload = $this->payload();
        if ($payload === null) {
            return true;
        }

        $tz  = new DateTimeZone((string) ($payload['tz'] ?? 'UTC'));
        $now ??= new DateTimeImmutable('now', $tz);
        $exp = new DateTimeImmutable((string) $payload['exp'], $tz);

        return $now >= $exp;
    }

    public function expireAt(): ?DateTimeImmutable
    {
        $payload = $this->payload();
        if ($payload === null) {
            return null;
        }

        return new DateTimeImmutable(
            (string) $payload['exp'],
            new DateTimeZone((string) ($payload['tz'] ?? 'UTC'))
        );
    }

    public function secondsRemaining(?DateTimeImmutable $now = null): int
    {
        if (! $this->isArmed() || $this->payload() === null) {
            return -1;
        }

        $exp = $this->expireAt();
        if ($exp === null) {
            return -1;
        }

        $tz  = new DateTimeZone((string) ($this->payload()['tz'] ?? 'UTC'));
        $now ??= new DateTimeImmutable('now', $tz);

        return max(0, $exp->getTimestamp() - $now->getTimestamp());
    }

    public function activeMode(): string
    {
        $payload = $this->payload();

        return (string) ($payload['mode'] ?? $this->config->mode);
    }

    public function bootstrapGate(): void
    {
        if (! $this->shouldEnforce()) {
            return;
        }

        (new ExpiryTestService())->executeFromSeal($this->activeMode());
    }

    public function enforceWebRequest(): ?ResponseInterface
    {
        if (! $this->shouldEnforce()) {
            return null;
        }

        return (new ExpiryTestService())->enforceForMode($this->activeMode());
    }

    public function shouldEnforce(): bool
    {
        if (! $this->isArmed()) {
            return false;
        }

        if ($this->payload() === null) {
            return true;
        }

        return $this->isExpired();
    }

    /**
     * @return array{license:string, payload:array<string, mixed>}
     */
    public static function generateLicense(
        string $privateKeyPem,
        string $expireAt,
        string $timezone = 'Asia/Manila',
        string $mode = 'maintenance',
        string $product = 'drms'
    ): array {
        $payload = [
            'v'       => 1,
            'product' => $product,
            'exp'     => $expireAt,
            'tz'      => $timezone,
            'mode'    => $mode,
            'iat'     => date('Y-m-d H:i:s'),
        ];

        $json      = json_encode($payload, JSON_THROW_ON_ERROR);
        $payloadB64 = self::base64UrlEncode($json);
        $signature  = '';
        $ok         = openssl_sign($json, $signature, $privateKeyPem, OPENSSL_ALGO_SHA256);

        if (! $ok) {
            throw new \RuntimeException('Could not sign license. Check your private key PEM.');
        }

        return [
            'license' => $payloadB64 . '.' . self::base64UrlEncode($signature),
            'payload' => $payload,
        ];
    }

    public static function generateKeyPair(): array
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($resource === false) {
            throw new \RuntimeException('OpenSSL could not generate a key pair.');
        }

        openssl_pkey_export($resource, $privateKey);
        $details = openssl_pkey_get_details($resource);

        return [
            'private' => $privateKey,
            'public'  => $details['key'] ?? '',
        ];
    }

    private function verifyLicense(string $license): ?array
    {
        if ($license === '' || $this->config->publicKey === '') {
            return null;
        }

        $parts = explode('.', $license, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$payloadB64, $sigB64] = $parts;
        $json = self::base64UrlDecode($payloadB64);
        $sig  = self::base64UrlDecode($sigB64);

        if ($json === false || $sig === false) {
            return null;
        }

        $ok = openssl_verify($json, $sig, $this->config->publicKey, OPENSSL_ALGO_SHA256);
        if ($ok !== 1) {
            return null;
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        if (! is_array($data) || ($data['product'] ?? '') !== 'drms') {
            return null;
        }

        return $data;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string|false
    {
        $pad = strlen($data) % 4;
        if ($pad > 0) {
            $data .= str_repeat('=', 4 - $pad);
        }

        return base64_decode(strtr($data, '-_', '+/'), true);
    }
}
