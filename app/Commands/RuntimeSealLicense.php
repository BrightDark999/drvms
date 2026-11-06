<?php

declare(strict_types=1);

namespace App\Commands;

use App\Services\RuntimeSealService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Developer-only license tooling. Requires DRMS_LICENSE_PRIVATE_KEY in .env.
 *
 * php spark runtime:seal keygen
 * php spark runtime:seal generate --until="2026-07-01 23:59:00"
 * php spark runtime:seal status
 */
class RuntimeSealLicense extends BaseCommand
{
    protected $group       = 'DRMS';
    protected $name        = 'runtime:seal';
    protected $description = 'Generate signed runtime seals for surrendered builds (developer only).';
    protected $usage       = 'runtime:seal <keygen|generate|status> [options]';
    protected $options     = [
        'until' => 'Expiry datetime for generate (Y-m-d H:i:s).',
        'tz'    => 'Timezone (default Asia/Manila).',
        'mode'  => 'maintenance | degraded | purge_sandbox',
    ];

    public function run(array $params): void
    {
        $action = strtolower((string) ($params[0] ?? 'status'));

        match ($action) {
            'keygen'    => $this->keygen(),
            'generate'  => $this->generate($params),
            'status'    => $this->status(),
            default     => CLI::error('Use: keygen | generate | status'),
        };
    }

    private function keygen(): void
    {
        $keys = RuntimeSealService::generateKeyPair();
        $dir  = WRITEPATH . 'license-keys';
        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        $privatePath = $dir . DIRECTORY_SEPARATOR . 'drms_license_private.pem';
        $publicPath  = $dir . DIRECTORY_SEPARATOR . 'drms_license_public.pem';

        file_put_contents($privatePath, $keys['private']);
        file_put_contents($publicPath, $keys['public']);
        chmod($privatePath, 0600);

        CLI::write('Key pair written:', 'green');
        CLI::write('  Private (KEEP — never surrender): ' . $privatePath, 'yellow');
        CLI::write('  Public (embed in RuntimeSeal.php): ' . $publicPath, 'green');
        CLI::newLine();
        CLI::write('Add to your .env (not the surrendered zip):', 'yellow');
        CLI::write('DRMS_LICENSE_PRIVATE_KEY="' . str_replace("\n", '\\n', trim($keys['private'])) . '"');
    }

    private function generate(array $params): void
    {
        $privateKey = $this->privateKeyFromEnv();
        if ($privateKey === null) {
            CLI::error('DRMS_LICENSE_PRIVATE_KEY missing in .env. Run runtime:seal keygen first.');

            return;
        }

        $until = CLI::getOption('until') ?? ($params[1] ?? null);
        if (! is_string($until) || trim($until) === '') {
            CLI::error('Pass --until="2026-07-01 23:59:00" or: runtime:seal generate "2026-07-01 23:59:00"');

            return;
        }
        $until = trim($until);

        $tz   = CLI::getOption('tz') ?: 'Asia/Manila';
        $mode = CLI::getOption('mode') ?: 'maintenance';

        $publicPem = $this->publicKeyForInstall();
        if ($publicPem === null) {
            CLI::error('Public key missing. Run keygen and paste public PEM into app/Config/RuntimeSeal.php');

            return;
        }

        $built = RuntimeSealService::generateLicense($privateKey, $until, (string) $tz, (string) $mode);
        $this->installIntoConfig($publicPem, $built['license'], (string) $mode);

        CLI::write('License installed into app/Config/RuntimeSeal.php', 'green');
        CLI::write('Expires: ' . $until . ' (' . $tz . ')', 'green');
        CLI::write('Mode: ' . $mode, 'green');
        CLI::newLine();
        CLI::write('Before surrendering the project:', 'yellow');
        CLI::write('  1. Remove DRMS_LICENSE_PRIVATE_KEY from any .env you ship');
        CLI::write('  2. Do not include writable/license-keys/');
        CLI::write('  3. Optional: delete this command file if you want zero CLI hints');
    }

    private function status(): void
    {
        $seal = new RuntimeSealService();
        $cfg  = config('RuntimeSeal');

        CLI::write('Runtime seal', 'yellow');
        CLI::write('  armed:    ' . ($seal->isArmed() ? 'yes' : 'no'));
        CLI::write('  valid:    ' . ($seal->payload() !== null ? 'yes' : 'no'));
        CLI::write('  mode:     ' . $seal->activeMode());

        $exp = $seal->expireAt();
        if ($exp !== null) {
            CLI::write('  expires:  ' . $exp->format('Y-m-d H:i:s'));
        }

        if ($seal->isArmed() && $seal->isExpired()) {
            CLI::write('  status:   EXPIRED — enforcement active', 'red');
        } elseif ($seal->isArmed()) {
            $remaining = $seal->secondsRemaining();
            CLI::write(sprintf('  status:   active — %d seconds remaining', $remaining), 'green');
        } else {
            CLI::write('  status:   not configured', 'dark_gray');
        }

        if ($cfg->license !== '') {
            CLI::newLine();
            CLI::write('License (first 48 chars): ' . substr($cfg->license, 0, 48) . '...', 'dark_gray');
        }
    }

    private function privateKeyFromEnv(): ?string
    {
        $raw = env('DRMS_LICENSE_PRIVATE_KEY');
        if (is_string($raw) && $raw !== '') {
            $raw = trim($raw, " \t\n\r\0\x0B\"'");
            $pem = str_replace('\\n', "\n", $raw);
            if (str_contains($pem, 'BEGIN PRIVATE KEY')) {
                return $pem;
            }
        }

        $path = WRITEPATH . 'license-keys' . DIRECTORY_SEPARATOR . 'drms_license_private.pem';
        if (is_file($path)) {
            return trim((string) file_get_contents($path));
        }

        return null;
    }

    private function publicKeyForInstall(): ?string
    {
        $fromConfig = config('RuntimeSeal')->publicKey;
        if ($fromConfig !== '') {
            return $fromConfig;
        }

        $path = WRITEPATH . 'license-keys' . DIRECTORY_SEPARATOR . 'drms_license_public.pem';
        if (is_file($path)) {
            return trim((string) file_get_contents($path));
        }

        return null;
    }

    private function installIntoConfig(string $publicPem, string $license, string $mode): void
    {
        $path = APPPATH . 'Config' . DIRECTORY_SEPARATOR . 'RuntimeSeal.php';
        $escapedPublic = str_replace("'", "\\'", $publicPem);
        $escapedLicense = str_replace("'", "\\'", $license);

        $content = <<<PHP
<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class RuntimeSeal extends BaseConfig
{
    public string \$publicKey = '{$escapedPublic}';

    public string \$license = '{$escapedLicense}';

    public string \$mode = '{$mode}';
}

PHP;

        file_put_contents($path, $content);
    }
}
