<?php
$summary = $summary ?? ['today' => 0, 'week' => 0, 'top_action' => '—', 'top_user' => '—'];

function drms_audit_group_date(string $at): string
{
    $ts = strtotime($at) ?: time();
    $today = date('Y-m-d');
    $day   = date('Y-m-d', $ts);
    if ($day === $today) {
        return 'Today';
    }
    if ($day === date('Y-m-d', strtotime('-1 day'))) {
        return 'Yesterday';
    }

    return date('F j, Y', $ts);
}

$timelineGroups = [];
foreach ($timeline as $item) {
    $key = drms_audit_group_date((string) ($item['created_at'] ?? ''));
    $timelineGroups[$key][] = $item;
}
?>

<?= $this->extend('layouts/admin') ?>

<?= $this->section('breadcrumb') ?>
<li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Home</a></li>
<li class="breadcrumb-item active">Audit trail</li>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
    <p class="text-muted mb-2 mb-md-0">Immutable activity log — who changed what, when, and from where.</p>
    <div>
        <a href="#" class="btn btn-sm btn-outline-success" id="btnExportAudit"><i class="fas fa-file-csv"></i> Export CSV</a>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRefreshAudit"><i class="fas fa-sync-alt"></i> Refresh</button>
    </div>
</div>

<div class="row drms-audit-stats mb-3">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3 id="auditKpiToday"><?= (int) ($summary['today'] ?? 0) ?></h3>
                <p>Events today</p>
            </div>
            <div class="icon"><i class="fas fa-bolt"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3 id="auditKpiWeek"><?= (int) ($summary['week'] ?? 0) ?></h3>
                <p>Last 7 days</p>
            </div>
            <div class="icon"><i class="fas fa-calendar-week"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3 class="h5" id="auditKpiTopAction"><?= esc($summary['top_action'] ?? '—') ?></h3>
                <p>Top action</p>
            </div>
            <div class="icon"><i class="fas fa-tag"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3 class="h5" id="auditKpiTopUser"><?= esc($summary['top_user'] ?? '—') ?></h3>
                <p>Most active user</p>
            </div>
            <div class="icon"><i class="fas fa-user-check"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-area mr-1"></i> Activity trend (14 days)</h3></div>
            <div class="card-body">
                <div class="drms-chart-wrap drms-chart-wrap--medium">
                    <canvas id="chartAuditTrend"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">About this log</h3></div>
            <div class="card-body small text-muted">
                <p class="mb-2">Records are <strong>append-only</strong>. Each entry may include user, IP address, and JSON metadata from the action.</p>
                <p class="mb-0">Use filters below to investigate incidents, volunteer deployments, and evacuation changes.</p>
            </div>
        </div>
    </div>
</div>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title mb-0">Filters</h3>
    </div>
    <div class="card-body py-3">
        <div class="form-row align-items-end">
            <div class="col-md-2 form-group mb-md-0">
                <label class="small mb-1">Date</label>
                <select id="filterDatePreset" class="form-control form-control-sm">
                    <option value="">All time</option>
                    <option value="today">Today</option>
                    <option value="7d" selected>Last 7 days</option>
                    <option value="30d">Last 30 days</option>
                </select>
            </div>
            <div class="col-md-2 form-group mb-md-0">
                <label class="small mb-1">Module</label>
                <select id="filterActionGroup" class="form-control form-control-sm">
                    <option value="">All modules</option>
                    <option value="incident">Incidents</option>
                    <option value="volunteer">Volunteers</option>
                    <option value="evacuation">Evacuation</option>
                    <option value="donation">Donations</option>
                </select>
            </div>
            <div class="col-md-2 form-group mb-md-0">
                <label class="small mb-1">Entity type</label>
                <select id="filterEntityType" class="form-control form-control-sm">
                    <option value="">All types</option>
                    <?php foreach ($entityTypes as $et): ?>
                        <option value="<?= esc($et) ?>"><?= esc(ucwords(str_replace('_', ' ', $et))) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-3 form-group mb-md-0">
                <label class="small mb-1">User</label>
                <select id="filterUserId" class="form-control form-control-sm">
                    <option value="">All users</option>
                    <?php foreach ($filterUsers as $u): ?>
                        <option value="<?= (int) $u['id'] ?>"><?= esc($u['label']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-3 form-group mb-md-0">
                <button type="button" class="btn btn-sm btn-primary" id="btnApplyAuditFilters">Apply filters</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearAuditFilters">Clear</button>
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs drms-audit-tabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" data-toggle="tab" href="#auditTabTable" role="tab"><i class="fas fa-table"></i> Table</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#auditTabTimeline" role="tab"><i class="fas fa-stream"></i> Timeline</a>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="auditTabTable" role="tabpanel">
        <div class="card card-outline card-secondary mb-0 border-top-0 rounded-0">
            <div class="card-body table-responsive">
                <table id="tblAudit" class="table table-bordered table-striped table-hover w-100">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Entity</th>
                            <th width="48"></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
    <div class="tab-pane fade" id="auditTabTimeline" role="tabpanel">
        <div class="card card-outline card-secondary mb-0 border-top-0 rounded-0">
            <div class="card-body drms-audit-timeline-wrap" id="auditTimelineBody">
                <?php if ($timelineGroups === []): ?>
                    <p class="text-muted mb-0">No audit events match the current filters.</p>
                <?php else: ?>
                    <?php foreach ($timelineGroups as $groupLabel => $items): ?>
                        <h6 class="drms-audit-timeline-date"><?= esc($groupLabel) ?></h6>
                        <ul class="drms-audit-timeline list-unstyled mb-4">
                            <?php foreach ($items as $item):
                                $badge = $item['badge_class'] ?? 'info';
                                ?>
                                <li class="drms-audit-timeline-item <?= ! empty($item['is_destructive']) ? 'drms-audit-timeline-item--danger' : '' ?>">
                                    <div class="drms-audit-timeline-time"><?= esc(date('g:i A', strtotime((string) ($item['created_at'] ?? 'now')))) ?></div>
                                    <div class="drms-audit-timeline-body">
                                        <span class="badge badge-<?= esc($badge) ?>"><?= esc($item['action_label'] ?? '') ?></span>
                                        <strong><?= esc($item['user_label'] ?? '') ?></strong>
                                        <span class="text-muted">— <?= esc($item['entity_label'] ?? '') ?></span>
                                        <button type="button" class="btn btn-xs btn-link btn-audit-detail p-0 ml-1" data-id="<?= (int) ($item['id'] ?? 0) ?>">Details</button>
                                    </div>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    <?php endforeach ?>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="auditDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Audit entry details</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="auditDetailBody">
                <p class="text-muted">Loading…</p>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(function () {
    var dataUrl = <?= json_encode($dataUrl) ?>;
    var summaryUrl = <?= json_encode($summaryUrl) ?>;
    var timelineUrl = <?= json_encode($timelineUrl) ?>;
    var exportUrl = <?= json_encode($exportUrl) ?>;
    var showBase = <?= json_encode(rtrim(base_url('admin/audit-trail/show'), '/')) ?>;
    var trendChart = null;

    function filterParams() {
        return {
            date_preset: $('#filterDatePreset').val() || '',
            action_group: $('#filterActionGroup').val() || '',
            entity_type: $('#filterEntityType').val() || '',
            user_id: $('#filterUserId').val() || ''
        };
    }

    var table = $('#tblAudit').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: dataUrl,
            data: function (d) {
                return $.extend({}, d, filterParams());
            }
        },
        columns: [
            { data: 0, orderable: false },
            { data: 1 },
            { data: 2, orderable: false },
            { data: 3, orderable: false },
            { data: 4, orderable: false, searchable: false }
        ],
        responsive: true,
        pageLength: 25,
        autoWidth: false,
        order: []
    });

    function renderTrend(trend) {
        if (typeof Chart === 'undefined' || !document.getElementById('chartAuditTrend')) return;
        var ctx = document.getElementById('chartAuditTrend').getContext('2d');
        if (trendChart) trendChart.destroy();
        trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: trend.labels || [],
                datasets: [{
                    label: 'Events',
                    data: trend.values || [],
                    borderColor: '#6d1f2a',
                    backgroundColor: 'rgba(109, 31, 42, 0.1)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                scales: { yAxes: [{ ticks: { beginAtZero: true, precision: 0 } }] }
            }
        });
    }

    renderTrend(<?= json_encode($trend) ?>);

    function refreshSummary() {
        $.getJSON(summaryUrl).done(function (res) {
            if (!res.summary) return;
            $('#auditKpiToday').text(res.summary.today || 0);
            $('#auditKpiWeek').text(res.summary.week || 0);
            $('#auditKpiTopAction').text(res.summary.top_action || '—');
            $('#auditKpiTopUser').text(res.summary.top_user || '—');
            if (res.trend) renderTrend(res.trend);
        });
    }

    function refreshTimeline() {
        $.getJSON(timelineUrl, filterParams()).done(function (res) {
            var items = res.timeline || [];
            if (!items.length) {
                $('#auditTimelineBody').html('<p class="text-muted mb-0">No audit events match the current filters.</p>');
                return;
            }
            var groups = {};
            items.forEach(function (item) {
                var d = item.created_at ? new Date(item.created_at.replace(' ', 'T')) : new Date();
                var key = d.toDateString();
                var today = new Date().toDateString();
                var yesterday = new Date(Date.now() - 86400000).toDateString();
                var label = key === today ? 'Today' : (key === yesterday ? 'Yesterday' : d.toLocaleDateString(undefined, { month: 'long', day: 'numeric', year: 'numeric' }));
                if (!groups[label]) groups[label] = [];
                groups[label].push(item);
            });
            var html = '';
            Object.keys(groups).forEach(function (label) {
                html += '<h6 class="drms-audit-timeline-date">' + label + '</h6><ul class="drms-audit-timeline list-unstyled mb-4">';
                groups[label].forEach(function (item) {
                    var t = item.created_at ? new Date(item.created_at.replace(' ', 'T')).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }) : '';
                    var danger = item.is_destructive ? ' drms-audit-timeline-item--danger' : '';
                    html += '<li class="drms-audit-timeline-item' + danger + '">';
                    html += '<div class="drms-audit-timeline-time">' + t + '</div>';
                    html += '<div class="drms-audit-timeline-body">';
                    html += '<span class="badge badge-' + (item.badge_class || 'info') + '">' + (item.action_label || '') + '</span> ';
                    html += '<strong>' + (item.user_label || '') + '</strong> ';
                    html += '<span class="text-muted">— ' + (item.entity_label || '') + '</span> ';
                    html += '<button type="button" class="btn btn-xs btn-link btn-audit-detail p-0 ml-1" data-id="' + item.id + '">Details</button>';
                    html += '</div></li>';
                });
                html += '</ul>';
            });
            $('#auditTimelineBody').html(html);
        });
    }

    function showDetail(id) {
        $('#auditDetailBody').html('<p class="text-muted">Loading…</p>');
        $('#auditDetailModal').modal('show');
        $.getJSON(showBase + '/' + id).done(function (res) {
            if (res.status !== 'ok' || !res.data) {
                $('#auditDetailBody').html('<p class="text-danger">Could not load entry.</p>');
                return;
            }
            var d = res.data;
            var html = '<dl class="row small mb-0">';
            html += '<dt class="col-sm-3">When</dt><dd class="col-sm-9">' + (d.created_at || '') + '</dd>';
            html += '<dt class="col-sm-3">User</dt><dd class="col-sm-9">' + (d.user_label || '') + '</dd>';
            html += '<dt class="col-sm-3">Action</dt><dd class="col-sm-9"><span class="badge badge-' + (d.badge_class || 'info') + '">' + (d.action_label || '') + '</span> <code>' + (d.action || '') + '</code></dd>';
            html += '<dt class="col-sm-3">Entity</dt><dd class="col-sm-9">' + (d.entity_label || '') + '</dd>';
            html += '<dt class="col-sm-3">IP address</dt><dd class="col-sm-9"><code>' + (d.ip_address || '—') + '</code></dd>';
            if (d.entity_url) {
                html += '<dt class="col-sm-3">Related</dt><dd class="col-sm-9"><a href="' + d.entity_url + '">Open module</a></dd>';
            }
            html += '</dl>';
            if (d.meta_pretty) {
                html += '<h6 class="mt-3">Metadata</h6><pre class="drms-audit-meta small bg-light p-2 rounded">' + $('<div>').text(d.meta_pretty).html() + '</pre>';
            }
            $('#auditDetailBody').html(html);
        }).fail(function () {
            $('#auditDetailBody').html('<p class="text-danger">Could not load entry.</p>');
        });
    }

    $(document).on('click', '.btn-audit-detail', function () {
        showDetail($(this).data('id'));
    });

    $('#btnApplyAuditFilters').on('click', function () {
        table.ajax.reload();
        refreshTimeline();
    });

    $('#btnClearAuditFilters').on('click', function () {
        $('#filterDatePreset').val('');
        $('#filterActionGroup').val('');
        $('#filterEntityType').val('');
        $('#filterUserId').val('');
        table.ajax.reload();
        refreshTimeline();
    });

    $('#btnRefreshAudit').on('click', function () {
        table.ajax.reload(null, false);
        refreshSummary();
        refreshTimeline();
    });

    $('#btnExportAudit').on('click', function (e) {
        e.preventDefault();
        var q = $.param(filterParams());
        window.location.href = exportUrl + (q ? '?' + q : '');
    });

    $('a[data-toggle="tab"][href="#auditTabTimeline"]').on('shown.bs.tab', refreshTimeline);

    setInterval(refreshSummary, 90000);
});
</script>
<?= $this->endSection() ?>
