<?php

declare(strict_types=1);

namespace App\Commands;

use App\Services\ExpiryTestService;
use App\Services\RuntimeSealService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Manage the controlled expiry test.
 *
 * php spark expiry:test status
 * php spark expiry:test setup
 * php spark expiry:test run
 * php spark expiry:test reset
 */
class ExpiryTestRun extends BaseCommand
{
    protected $group       = 'DRMS';
    protected $name        = 'expiry:test';
    protected $description = 'Status, setup, run, or reset the controlled expiry test.';
    protected $usage       = 'expiry:test <status|setup|run|reset> [--force]';
    protected $arguments   = [
        'action' => 'status | setup | run | reset',
    ];
    protected $options     = [
        '--force' => 'Run even if expireAt has not passed (run action only).',
    ];

    public function run(array $params): void
    {
        $action  = strtolower((string) ($params[0] ?? 'status'));
        $service = new ExpiryTestService();
        $config  = config('ExpiryTest');

        match ($action) {
            'status' => $this->showStatus($service, $config),
            'setup'  => $this->setupSandbox($service),
            'run'    => $this->runNow($service, CLI::getOption('force') !== null),
            'reset'  => $this->reset($service),
            default  => CLI::error('Unknown action. Use: status, setup, run, reset'),
        };
    }

    private function showStatus(ExpiryTestService $service, $config): void
    {
        $seal = new RuntimeSealService();
        if ($seal->isArmed()) {
            CLI::write('Signed runtime seal is active — local ExpiryTest config is ignored.', 'yellow');
            CLI::write('  valid:   ' . ($seal->payload() !== null ? 'yes' : 'no'));
            CLI::write('  expires: ' . ($seal->expireAt()?->format('Y-m-d H:i:s') ?? 'n/a'));
            CLI::write('  mode:    ' . $seal->activeMode());
            CLI::newLine();
        }

        CLI::write('Expiry test configuration', 'yellow');
        CLI::write('  enabled:  ' . ($config->enabled ? 'yes' : 'no'));
        CLI::write('  expireAt: ' . $config->expireAt . ' (' . $config->timezone . ')');
        CLI::write('  mode:     ' . $config->mode);
        CLI::write('  dryRun:   ' . ($config->dryRun ? 'yes (safe)' : 'no (live)'));
        CLI::write('  sandbox:  ' . $service->sandboxPath());
        CLI::newLine();

        if (! $config->enabled) {
            CLI::write('Nothing will run until enabled=true in app/Config/ExpiryTest.php', 'dark_gray');

            return;
        }

        $remaining = $service->secondsRemaining();
        if ($service->isExpired()) {
            CLI::write('Status: EXPIRED — action should run (or already has).', 'red');
        } else {
            $hours = intdiv($remaining, 3600);
            $mins  = intdiv($remaining % 3600, 60);
            $secs  = $remaining % 60;
            CLI::write(sprintf('Status: waiting — %02d:%02d:%02d until expiry.', $hours, $mins, $secs), 'green');
        }
    }

    private function setupSandbox(ExpiryTestService $service): void
    {
        $count = $service->setupSandbox();
        CLI::write("Created/updated {$count} sandbox files in:", 'green');
        CLI::write('  ' . $service->sandboxPath());
    }

    private function runNow(ExpiryTestService $service, bool $force): void
    {
        $result = $service->execute($force);
        CLI::write(json_encode($result, JSON_PRETTY_PRINT), $result['ran'] ? 'green' : 'yellow');
    }

    private function reset(ExpiryTestService $service): void
    {
        $service->resetTrigger();
        CLI::write('Trigger marker cleared. Sandbox can be re-setup with: php spark expiry:test setup', 'green');
    }
}
