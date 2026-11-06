<?php
$overview = $analytics['overview'] ?? $overview ?? [];
$alerts   = $analytics['alerts'] ?? [];
$trend    = $analytics['trend_7d'] ?? ['labels' => [], 'values' => []];
$byStatus = $analytics['by_status'] ?? ['labels' => [], 'values' => []];
$bySev    = $analytics['by_severity'] ?? ['labels' => [], 'values' => []];
$byType   = $analytics['by_type'] ?? ['labels' => [], 'values' => []];
$recent   = $analytics['recent_incidents'] ?? [];
$deploys  = $analytics['deployments_7d'] ?? ['labels' => [], 'values' => []];
$evac     = $analytics['evac_summary'] ?? [];
$evacPct  = ($evac['total_capacity'] ?? 0) > 0
    ? (int) round((($evac['total_occupancy'] ?? 0) / $evac['total_capacity']) * 100)
    : 0;
?>

<?= $this->extend('layouts/admin') ?>

<?= $this->section('breadcrumb') ?>
<li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Home</a></li>
<li class="breadcrumb-item active">Reports</li>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if (session('error')): ?>
    <div class="alert alert-danger"><?= esc(session('error')) ?></div>
<?php endif ?>

<div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
    <p class="text-muted mb-2 mb-md-0">Live operational analytics and exportable summaries for MDRRMO leadership.</p>
    <div class="d-flex align-items-center">
        <span class="badge badge-light mr-2" id="reportsLiveBadge"><i class="fas fa-circle text-success"></i> Live</span>
        <small class="text-muted" id="reportsUpdated">Updated <?= esc(date('M j, g:i A')) ?></small>
        <button type="button" class="btn btn-sm btn-outline-secondary ml-2" id="btnRefreshReports" title="Refresh">
            <i class="fas fa-sync-alt"></i>
        </button>
    </div>
</div>

<div class="row" id="reportsKpiRow">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3 id="kpiActiveIncidents"><?= (int) ($overview['active_incidents'] ?? 0) ?></h3>
                <p>Active incidents</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3 id="kpiVolunteers"><?= (int) ($overview['volunteers_on_duty'] ?? 0) ?></h3>
                <p>Volunteers available</p>
            </div>
            <div class="icon"><i class="fas fa-hands-helping"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3 id="kpiEvacOcc"><?= esc($overview['evacuation_occupancy'] ?? '0%') ?></h3>
                <p>Evacuation occupancy</p>
            </div>
            <div class="icon"><i class="fas fa-home"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3 id="kpiPending"><?= (int) ($overview['pending_verifications'] ?? 0) ?></h3>
                <p>Pending verification</p>
            </div>
            <div class="icon"><i class="fas fa-clipboard-check"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-outline card-danger">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-line mr-1"></i> Incident trend (7 days)</h3>
            </div>
            <div class="card-body">
                <div class="drms-chart-wrap drms-chart-wrap--tall">
                    <canvas id="chartIncidentTrend"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">By status</h3></div>
            <div class="card-body">
                <div class="drms-chart-wrap">
                    <canvas id="chartByStatus"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">By severity</h3></div>
            <div class="card-body">
                <div class="drms-chart-wrap">
                    <canvas id="chartBySeverity"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">By incident type</h3></div>
            <div class="card-body">
                <div class="drms-chart-wrap">
                    <canvas id="chartByType"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Volunteer deployments (7d)</h3></div>
            <div class="card-body">
                <div class="drms-chart-wrap">
                    <canvas id="chartDeployments"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="fas fa-list mr-1"></i> Recent incidents</h3>
                <a href="<?= base_url('admin/incidents') ?>" class="btn btn-xs btn-outline-primary">View all</a>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover table-sm mb-0" id="tblRecentIncidents">
                    <thead><tr><th>Reference</th><th>Title</th><th>Severity</th><th>Status</th><th>When</th></tr></thead>
                    <tbody>
                        <?php if ($recent === []): ?>
                            <tr><td colspan="5" class="text-muted p-3">No incidents recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent as $inc):
                                $sev = strtolower((string) ($inc['severity'] ?? ''));
                                $sevBadge = match ($sev) {
                                    'critical' => 'danger',
                                    'high'     => 'warning',
                                    default    => 'secondary',
                                };
                                ?>
                                <tr>
                                    <td><code><?= esc($inc['reference_code'] ?? '') ?></code></td>
                                    <td><?= esc($inc['title'] ?? '') ?></td>
                                    <td><span class="badge badge-<?= $sevBadge ?>"><?= esc(ucfirst($sev)) ?></span></td>
                                    <td><?= esc(ucfirst($inc['status'] ?? '')) ?></td>
                                    <td class="text-muted small"><?= esc($inc['created_at'] ?? '') ?></td>
                                </tr>
                            <?php endforeach ?>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-file-export mr-1"></i> Export reports</h3></div>
            <div class="card-body">
                <div class="row drms-export-cards">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <a href="<?= base_url('admin/reports/export/incidents-pdf') ?>" class="drms-export-card">
                            <i class="fas fa-file-pdf text-danger"></i>
                            <strong>Incidents PDF</strong>
                            <span class="small text-muted">Full incident log</span>
                        </a>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <a href="<?= base_url('admin/reports/export/incidents-xlsx') ?>" class="drms-export-card">
                            <i class="fas fa-file-excel text-success"></i>
                            <strong>Incidents Excel</strong>
                            <span class="small text-muted">Spreadsheet export</span>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="<?= base_url('admin/reports/export/overview-pdf') ?>" class="drms-export-card">
                            <i class="fas fa-file-pdf text-primary"></i>
                            <strong>Operations PDF</strong>
                            <span class="small text-muted">KPIs + alerts snapshot</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-home mr-1"></i> Evacuation snapshot</h3>
                <a href="<?= base_url('admin/evacuation-centers') ?>" class="btn btn-xs btn-outline-dark ml-auto">Manage</a>
            </div>
            <div class="card-body">
                <div class="row text-center mb-3">
                    <div class="col-4"><div class="drms-snap-num"><?= (int) ($evac['open_count'] ?? 0) ?></div><div class="small text-muted">Open</div></div>
                    <div class="col-4"><div class="drms-snap-num"><?= (int) ($evac['total_occupancy'] ?? 0) ?></div><div class="small text-muted">Occupied</div></div>
                    <div class="col-4"><div class="drms-snap-num"><?= (int) ($evac['families_total'] ?? 0) ?></div><div class="small text-muted">Families</div></div>
                </div>
                <div class="mb-2 d-flex justify-content-between small">
                    <span>Overall capacity</span>
                    <strong><?= (int) ($evac['total_occupancy'] ?? 0) ?> / <?= (int) ($evac['total_capacity'] ?? 0) ?> (<?= $evacPct ?>%)</strong>
                </div>
                <div class="progress mb-3">
                    <div class="progress-bar <?= $evacPct >= 90 ? 'bg-danger' : ($evacPct >= 70 ? 'bg-warning' : 'bg-success') ?>" style="width: <?= min(100, $evacPct) ?>%"></div>
                </div>
                <?php foreach (array_slice($evac['centers'] ?? [], 0, 5) as $ec):
                    $epct = (int) ($ec['pct'] ?? 0);
                    ?>
                    <div class="drms-evac-snap-row mb-2">
                        <div class="d-flex justify-content-between small">
                            <span><?= esc($ec['name'] ?? '') ?></span>
                            <span><?= $epct ?>%</span>
                        </div>
                        <div class="progress progress-xs">
                            <div class="progress-bar <?= $epct >= 90 ? 'bg-danger' : ($epct >= 70 ? 'bg-warning' : 'bg-info') ?>" style="width: <?= min(100, $epct) ?>%"></div>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        </div>

        <div class="card card-outline card-danger">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bell mr-1"></i> Active alerts</h3>
                <span class="badge badge-light ml-1" id="reportsAlertCount"><?= count($alerts) ?></span>
            </div>
            <div class="card-body p-0" id="reportsAlertsList">
                <?php if ($alerts === []): ?>
                    <p class="text-muted small p-3 mb-0">No active alerts.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($alerts as $alert):
                            $badge = match ($alert['level'] ?? '') {
                                'danger'  => 'danger',
                                'warning' => 'warning',
                                default   => 'info',
                            };
                            ?>
                            <li class="list-group-item">
                                <span class="badge badge-<?= $badge ?> mr-1"><?= esc(ucfirst($alert['level'] ?? '')) ?></span>
                                <strong class="small"><?= esc($alert['title'] ?? '') ?></strong>
                                <div class="small text-muted"><?= esc($alert['message'] ?? '') ?></div>
                            </li>
                        <?php endforeach ?>
                    </ul>
                <?php endif ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-plug mr-1"></i> Module integration</h3></div>
            <div class="card-body small text-muted">
                <p class="mb-2"><strong>Operations:</strong> incidents, volunteers, dispatch — served by DRMS.</p>
                <p class="mb-2"><strong>Relief &amp; logistics:</strong> relief, donations, inventory. Configure <code>integrations.reliefApiBaseUrl</code> in <code>.env</code> when the logistics service is online.</p>
                <code class="d-block mb-1">GET /api/v1/stats</code>
                <code class="d-block mb-1">GET /api/v1/incidents</code>
                <code class="d-block">GET /api/v1/evacuation-centers</code>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(function () {
    var statsUrl = <?= json_encode($statsUrl) ?>;
    var charts = {};

    var palette = ['#c62828', '#e65100', '#1565c0', '#2e7d32', '#6a1b9a', '#00838f', '#5d4037', '#546e7a'];

    function makeLineChart(id, labels, values, label, color) {
        var ctx = document.getElementById(id);
        if (!ctx || typeof Chart === 'undefined') return;
        if (charts[id]) charts[id].destroy();
        charts[id] = new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: label,
                    data: values,
                    borderColor: color || '#c62828',
                    backgroundColor: 'rgba(198, 40, 40, 0.08)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                scales: {
                    yAxes: [{ ticks: { beginAtZero: true, precision: 0 } }]
                }
            }
        });
    }

    function makeDoughnut(id, labels, values) {
        var ctx = document.getElementById(id);
        if (!ctx || typeof Chart === 'undefined') return;
        if (charts[id]) charts[id].destroy();
        charts[id] = new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{ data: values, backgroundColor: palette }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { position: 'bottom', labels: { boxWidth: 12, fontSize: 11 } }
            }
        });
    }

    function makeBar(id, labels, values, color) {
        var ctx = document.getElementById(id);
        if (!ctx || typeof Chart === 'undefined') return;
        if (charts[id]) charts[id].destroy();
        charts[id] = new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{ data: values, backgroundColor: color || '#1565c0' }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                scales: { yAxes: [{ ticks: { beginAtZero: true, precision: 0 } }] }
            }
        });
    }

    function renderCharts(a) {
        makeLineChart('chartIncidentTrend', a.trend_7d.labels, a.trend_7d.values, 'Incidents', '#c62828');
        makeDoughnut('chartByStatus', a.by_status.labels, a.by_status.values);
        makeBar('chartBySeverity', a.by_severity.labels, a.by_severity.values, '#e65100');
        makeBar('chartByType', a.by_type.labels, a.by_type.values, '#2e7d32');
        makeLineChart('chartDeployments', a.deployments_7d.labels, a.deployments_7d.values, 'Deployments', '#1565c0');
    }

    var initial = <?= json_encode($analytics) ?>;
    renderCharts(initial);

    function refreshReports() {
        $('#btnRefreshReports i').addClass('fa-spin');
        $.getJSON(statsUrl).done(function (res) {
            if (!res.analytics) return;
            var a = res.analytics;
            var o = a.overview || {};
            $('#kpiActiveIncidents').text(o.active_incidents || 0);
            $('#kpiVolunteers').text(o.volunteers_on_duty || 0);
            $('#kpiEvacOcc').text(o.evacuation_occupancy || '0%');
            $('#kpiPending').text(o.pending_verifications || 0);
            $('#reportsAlertCount').text((a.alerts || []).length);
            $('#reportsUpdated').text('Updated ' + new Date().toLocaleString());
            renderCharts(a);
        }).always(function () {
            $('#btnRefreshReports i').removeClass('fa-spin');
        });
    }

    $('#btnRefreshReports').on('click', refreshReports);
    setInterval(refreshReports, 60000);
});
</script>
<?= $this->endSection() ?>
