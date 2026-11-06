<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\HTTP\ResponseInterface;
use Config\ExpiryTest;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/**
 * Runs a one-time scheduled action when expireAt is reached.
 * Safe by design: sandbox-only deletes, dry-run default, explicit enable flag.
 */
class ExpiryTestService
{
    private ExpiryTest $config;

    private ?RuntimeSealService $seal = null;

    public function __construct(?ExpiryTest $config = null)
    {
        $this->config = $config ?? config('ExpiryTest');
    }

    public function isEnabled(): bool
    {
        if ($this->seal()->isArmed()) {
            return true;
        }

        return $this->config->enabled;
    }

    public function expireAt(): ?DateTimeImmutable
    {
        if ($this->seal()->isArmed()) {
            return $this->seal()->expireAt();
        }

        return new DateTimeImmutable(
            $this->config->expireAt,
            new DateTimeZone($this->config->timezone)
        );
    }

    public function isExpired(?DateTimeImmutable $now = null): bool
    {
        if ($this->seal()->isArmed()) {
            return $this->seal()->isExpired($now);
        }

        if (! $this->config->enabled) {
            return false;
        }

        $now ??= new DateTimeImmutable('now', new DateTimeZone($this->config->timezone));

        return $now >= $this->expireAt();
    }

    public function secondsRemaining(?DateTimeImmutable $now = null): int
    {
        if ($this->seal()->isArmed()) {
            return $this->seal()->secondsRemaining($now);
        }

        if (! $this->config->enabled) {
            return -1;
        }

        $now ??= new DateTimeImmutable('now', new DateTimeZone($this->config->timezone));
        $exp   = $this->expireAt();

        return $exp === null ? -1 : max(0, $exp->getTimestamp() - $now->getTimestamp());
    }

    /**
     * Called on every web/CLI boot. No-op until expired.
     */
    public function checkOnBoot(): void
    {
        if ($this->seal()->isArmed()) {
            $this->seal()->bootstrapGate();

            return;
        }

        if (! $this->isExpired()) {
            return;
        }

        $this->execute();
    }

    /**
     * Enforce runtime behavior after expiry (web requests only).
     */
    public function enforceWebRequest(): ?ResponseInterface
    {
        if ($this->seal()->isArmed()) {
            return $this->seal()->enforceWebRequest();
        }

        if (! $this->isExpired() || $this->config->dryRun) {
            return null;
        }

        return $this->enforceForMode($this->config->mode);
    }

    public function enforceForMode(string $mode): ?ResponseInterface
    {
        return match ($mode) {
            'maintenance' => $this->maintenanceResponse(),
            'degraded'    => $this->maybeDegradedResponse(),
            default       => null,
        };
    }

    public function executeFromSeal(string $mode): array
    {
        if ($this->alreadyTriggered()) {
            return ['ran' => false, 'reason' => 'already_triggered'];
        }

        $result = ['ran' => true, 'mode' => $mode, 'source' => 'runtime_seal'];

        try {
            if ($mode === 'purge_sandbox') {
                $result['deleted'] = $this->purgeSandbox();
            }
            $this->markTriggered('runtime_seal', $mode);
            $this->log('EXECUTED (seal) — mode=' . $mode);
        } catch (Throwable $e) {
            $result['error'] = $e->getMessage();
            $this->log('ERROR (seal) — ' . $e->getMessage());
        }

        return $result;
    }

    private function seal(): RuntimeSealService
    {
        return $this->seal ??= new RuntimeSealService();
    }

    /**
     * Run the configured action once. Use $force to bypass the already-ran marker.
     */
    public function execute(bool $force = false): array
    {
        if (! $this->isEnabled()) {
            return ['ran' => false, 'reason' => 'disabled'];
        }

        if (! $force && ! $this->isExpired()) {
            return ['ran' => false, 'reason' => 'not_expired'];
        }

        if (! $force && $this->alreadyTriggered()) {
            return ['ran' => false, 'reason' => 'already_triggered'];
        }

        $result = ['ran' => true, 'mode' => $this->config->mode, 'dry_run' => $this->config->dryRun];

        if ($this->config->dryRun) {
            $result['would_do'] = $this->describeAction();
            $this->log('DRY RUN — ' . $result['would_do']);
            $this->markTriggered();

            return $result;
        }

        try {
            match ($this->config->mode) {
                'purge_sandbox' => $result['deleted'] = $this->purgeSandbox(),
                'maintenance', 'degraded' => $result['note'] = 'runtime enforcement active',
                default => $result['note'] = 'unknown mode',
            };
            $this->markTriggered();
            $this->log('EXECUTED — mode=' . $this->config->mode);
        } catch (Throwable $e) {
            $result['error'] = $e->getMessage();
            $this->log('ERROR — ' . $e->getMessage());
        }

        return $result;
    }

    public function setupSandbox(): int
    {
        $dir = $this->sandboxPath();
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new \RuntimeException('Could not create sandbox: ' . $dir);
        }

        $samples = [
            'cache/session_store.dat'     => 'simulated session cache — safe to delete',
            'cache/temp_index.json'       => json_encode(['cached_at' => date('c'), 'rows' => 42]),
            'data/local_backup_stub.sql'  => "-- dummy backup fragment\nSELECT 1;\n",
            'data/runtime_flags.ini'        => "maintenance=0\nversion=1.0.0-dev\n",
        ];

        $written = 0;
        foreach ($samples as $relative => $content) {
            $path = $dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $parent = dirname($path);
            if (! is_dir($parent)) {
                mkdir($parent, 0755, true);
            }
            if (file_put_contents($path, $content) !== false) {
                $written++;
            }
        }

        return $written;
    }

    public function sandboxPath(): string
    {
        return rtrim(WRITEPATH, '\\/') . DIRECTORY_SEPARATOR . trim($this->config->sandboxDir, '\\/');
    }

    public function resetTrigger(): void
    {
        $marker = $this->triggerMarkerPath();
        if (is_file($marker)) {
            unlink($marker);
        }
    }

    private function describeAction(): string
    {
        return match ($this->config->mode) {
            'purge_sandbox' => 'delete sandbox files in ' . $this->sandboxPath(),
            'maintenance'   => 'block web requests with infrastructure error page',
            'degraded'      => 'serve intermittent 500 errors and delays',
            default         => 'no action',
        };
    }

    private function purgeSandbox(): array
    {
        $dir = $this->sandboxPath();
        $deleted = [];

        if (! is_dir($dir)) {
            return $deleted;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $path = $item->getPathname();
            if ($item->isDir()) {
                @rmdir($path);
            } else {
                if (@unlink($path)) {
                    $deleted[] = str_replace($dir . DIRECTORY_SEPARATOR, '', $path);
                }
            }
        }

        return $deleted;
    }

    private function maintenanceResponse(): ResponseInterface
    {
        $html = view('errors/expiry_maintenance', [], ['saveData' => false]);

        return service('response')
            ->setStatusCode(503)
            ->setBody($html)
            ->noCache();
    }

    private function maybeDegradedResponse(): ?ResponseInterface
    {
        $roll = random_int(1, 100);

        if ($roll <= 35) {
            usleep(random_int(800_000, 2_500_000));

            return service('response')
                ->setStatusCode(500)
                ->setBody(view('errors/expiry_degraded', [], ['saveData' => false]))
                ->noCache();
        }

        return null;
    }

    private function triggerMarkerPath(): string
    {
        return rtrim(WRITEPATH, '\\/') . DIRECTORY_SEPARATOR . '.expiry_test_triggered';
    }

    private function alreadyTriggered(): bool
    {
        return is_file($this->triggerMarkerPath());
    }

    private function markTriggered(string $source = 'config', ?string $mode = null): void
    {
        file_put_contents($this->triggerMarkerPath(), json_encode([
            'at'       => date('c'),
            'source'   => $source,
            'expireAt' => $this->expireAt()?->format('Y-m-d H:i:s'),
            'mode'     => $mode ?? $this->config->mode,
            'dryRun'   => $this->config->dryRun,
        ], JSON_PRETTY_PRINT));
    }

    private function log(string $message): void
    {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        file_put_contents(
            rtrim(WRITEPATH, '\\/') . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'expiry-test.log',
            $line,
            FILE_APPEND | LOCK_EX
        );
    }
}
