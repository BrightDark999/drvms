<?php

declare(strict_types=1);

namespace App\Commands;

use App\Models\BarangayModel;
use App\Models\DonationModel;
use App\Models\EvacuationCenterModel;
use App\Models\IncidentModel;
use App\Models\NotificationQueueModel;
use App\Models\VolunteerModel;
use App\Services\AlertsService;
use App\Services\DashboardStatsService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * Quick backend connectivity audit for DRMS modules.
 *
 * php spark drms:audit
 */
class DrmsSystemAudit extends BaseCommand
{
    protected $group       = 'DRMS';
    protected $name        = 'drms:audit';
    protected $description = 'Check database tables and core services used by admin modules.';
    protected $usage       = 'drms:audit';

    /** @var list<array{module:string, table:string, model:class-string}> */
    private array $modules = [
        ['module' => 'Incidents', 'table' => 'incidents', 'model' => IncidentModel::class],
        ['module' => 'Volunteers', 'table' => 'volunteers', 'model' => VolunteerModel::class],
        ['module' => 'Evacuation centers', 'table' => 'evacuation_centers', 'model' => EvacuationCenterModel::class],
        ['module' => 'Donations', 'table' => 'donations', 'model' => DonationModel::class],
        ['module' => 'Notifications', 'table' => 'notification_queue', 'model' => NotificationQueueModel::class],
        ['module' => 'Barangays (lookup)', 'table' => 'barangays', 'model' => BarangayModel::class],
    ];

    public function run(array $params): void
    {
        CLI::write('DRMS system audit', 'green');
        CLI::newLine();

        $dbOk = $this->auditDatabase();
        $this->auditServices();
        $this->auditFiles();

        CLI::newLine();
        CLI::write($dbOk ? 'Audit finished — review rows above.' : 'Audit finished — fix database errors first.', $dbOk ? 'green' : 'red');
    }

    private function auditDatabase(): bool
    {
        CLI::write('Database tables', 'yellow');
        $allOk = true;

        try {
            $db = db_connect();
        } catch (Throwable $e) {
            CLI::error('DB connection failed: ' . $e->getMessage());

            return false;
        }

        foreach ($this->modules as $m) {
            try {
                if (! $db->tableExists($m['table'])) {
                    CLI::write(sprintf('  [FAIL] %-22s table missing: %s', $m['module'], $m['table']), 'red');
                    $allOk = false;
                    continue;
                }
                $count = model($m['model'])->countAllResults();
                CLI::write(sprintf('  [OK]   %-22s %s (%d rows)', $m['module'], $m['table'], $count), 'green');
            } catch (Throwable $e) {
                CLI::write(sprintf('  [FAIL] %-22s %s', $m['module'], $e->getMessage()), 'red');
                $allOk = false;
            }
        }

        foreach (
            [
                ['Audit trail', 'audit_logs'],
                ['Volunteer deployments', 'volunteer_deployments'],
                ['AI suggestions log', 'ai_suggestions'],
            ] as [$label, $table]
        ) {
            try {
                if ($db->tableExists($table)) {
                    $n = $db->table($table)->countAllResults();
                    CLI::write(sprintf('  [OK]   %-22s %s (%d rows)', $label, $table, $n), 'green');
                } else {
                    CLI::write(sprintf('  [FAIL] %-22s table missing: %s', $label, $table), 'red');
                    $allOk = false;
                }
            } catch (Throwable $e) {
                CLI::write(sprintf('  [FAIL] %-22s %s', $label, $e->getMessage()), 'red');
                $allOk = false;
            }
        }

        CLI::newLine();

        return $allOk;
    }

    private function auditServices(): void
    {
        CLI::write('Core services', 'yellow');

        try {
            $stats = (new DashboardStatsService())->getOverview();
            CLI::write('  [OK]   DashboardStatsService  KPIs: ' . json_encode($stats), 'green');
        } catch (Throwable $e) {
            CLI::write('  [FAIL] DashboardStatsService  ' . $e->getMessage(), 'red');
        }

        try {
            $alerts = (new AlertsService())->getDashboardAlerts();
            CLI::write('  [OK]   AlertsService          ' . count($alerts) . ' alert(s)', 'green');
        } catch (Throwable $e) {
            CLI::write('  [FAIL] AlertsService          ' . $e->getMessage(), 'red');
        }

        CLI::newLine();
    }

    private function auditFiles(): void
    {
        CLI::write('Static assets / config', 'yellow');

        $geo = FCPATH . 'assets/geo/baras-rizal.geojson';
        if (is_file($geo)) {
            CLI::write('  [OK]   Baras boundary GeoJSON   ' . $geo, 'green');
        } else {
            CLI::write('  [WARN] Baras boundary GeoJSON   missing (dashboard map)', 'yellow');
        }

        $integrations = config('Integrations');
        CLI::write('  [INFO] External relief API: ' . ($integrations->reliefApiEnabled ? 'enabled' : 'not configured'), 'white');

        CLI::newLine();
    }
}
