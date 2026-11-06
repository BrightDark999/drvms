<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Local expiry test (no signature) — for your machine only.
 *
 * For surrendered builds use the signed runtime seal instead:
 *   php spark runtime:seal keygen
 *   php spark runtime:seal generate --until="2026-07-01 23:59:00"
 *
 * When a seal is installed, this config is ignored for enforcement.
 */
class ExpiryTest extends BaseConfig
{
    /** Master switch. Keep false until you intentionally run a test. */
    public bool $enabled = false;

    /**
     * When this moment passes, the configured action runs once.
     * Format: Y-m-d H:i:s (24-hour clock).
     */
    public string $expireAt = '2026-06-12 23:59:00';

    public string $timezone = 'Asia/Manila';

    /**
     * maintenance — block all web traffic with a generic infrastructure error page
     * purge_sandbox — delete dummy files in writable/expiry-test-sandbox/ (simulates missing cache/data)
     * degraded — random 500 errors and slow responses (feels unstable)
     */
    public string $mode = 'maintenance';

    /**
     * When true, actions are logged but nothing is deleted and maintenance is not enforced.
     * Set false only when you are ready for the real simulated failure.
     */
    public bool $dryRun = true;

    /** Relative to WRITEPATH. Only this directory may be purged. */
    public string $sandboxDir = 'expiry-test-sandbox';

    public function __construct()
    {
        parent::__construct();

        $this->enabled  = $this->envBool('expiry.enabled', $this->enabled);
        $this->expireAt = env('expiry.expireAt') ?: $this->expireAt;
        $this->timezone = env('expiry.timezone') ?: $this->timezone;
        $this->mode     = env('expiry.mode') ?: $this->mode;
        $this->dryRun   = $this->envBool('expiry.dryRun', $this->dryRun);
    }

    private function envBool(string $key, bool $default): bool
    {
        $value = env($key);

        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
