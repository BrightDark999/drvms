<?php
helper('drms');
$avail = strtolower((string) ($stats['availability_status'] ?? 'unknown'));
$availBadge = match ($avail) {
    'available' => 'success',
    'on_duty'   => 'info',
    'deployed'  => 'danger',
    'unavailable', 'off_duty' => 'secondary',
    default     => 'warning',
};
$idInfo = drms_id_verification_badge((string) ($stats['id_verification_status'] ?? 'none'));
$isDeployed = $avail === 'deployed';
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('breadcrumb') ?>
<li class="breadcrumb-item active">Volunteer Dashboard</li>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-12">
        <div class="callout callout-info mb-3">
            <h5 class="mb-1">Welcome, <?= esc(drms_current_user_display()['name']) ?></h5>
            <p class="mb-0">Your volunteer operations view — assignments, area incidents, and profile status. MDRRMO / LGU staff deploy you through Volunteer Dispatch.</p>
        </div>
    </div>
</div>

<div class="row" id="volKpiRow">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3 id="kpiAssignments"><?= (int) ($stats['active_assignments'] ?? 0) ?></h3>
                <p>Active assignments</p>
            </div>
            <div class="icon"><i class="fas fa-tasks"></i></div>
            <a href="<?= base_url('admin/matching') ?>" class="small-box-footer">Open dispatch <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><span class="badge badge-<?= $availBadge ?>" id="kpiAvailability"><?= esc($stats['availability_label'] ?? '—') ?></span></h3>
                <p>Availability</p>
            </div>
            <div class="icon"><i class="fas fa-user-check"></i></div>
            <a href="<?= base_url('profile') ?>" class="small-box-footer">Update profile <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3 id="kpiIncidents"><?= (int) ($stats['active_incidents'] ?? 0) ?></h3>
                <p>Active incidents (area)</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            <a href="<?= base_url('admin/matching') ?>" class="small-box-footer">View dispatch queue <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3 id="kpiNotifications"><?= (int) ($stats['unread_notifications'] ?? 0) ?></h3>
                <p>Unread alerts</p>
            </div>
            <div class="icon"><i class="fas fa-bell"></i></div>
            <a href="<?= base_url('profile') ?>" class="small-box-footer">View profile <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<?php if (! empty($stats['has_profile'])): ?>
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-clock mr-1"></i> My availability</h3>
            </div>
            <div class="card-body">
                <?php if ($isDeployed): ?>
                    <p class="mb-0 text-muted">
                        <span class="badge badge-danger">Deployed</span>
                        You are on an active assignment. Contact MDRRMO if you need to change your status.
                    </p>
                <?php else: ?>
                    <p class="text-muted small mb-3">Tell MDRRMO when you can or cannot respond. This affects dispatch matching.</p>
                    <div class="btn-group flex-wrap" role="group" id="volAvailButtons">
                        <button type="button" class="btn btn-success btn-vol-avail <?= $avail === 'available' ? 'active' : '' ?>" data-status="available">
                            <i class="fas fa-check-circle mr-1"></i> Available
                        </button>
                        <button type="button" class="btn btn-info btn-vol-avail <?= $avail === 'on_duty' ? 'active' : '' ?>" data-status="on_duty">
                            <i class="fas fa-hard-hat mr-1"></i> On duty
                        </button>
                        <button type="button" class="btn btn-secondary btn-vol-avail <?= $avail === 'unavailable' ? 'active' : '' ?>" data-status="unavailable">
                            <i class="fas fa-ban mr-1"></i> Unavailable
                        </button>
                    </div>
                    <p class="small text-muted mt-2 mb-0">You can also update name, phone, skills, and ID on <a href="<?= base_url('profile') ?>">My Profile</a>.</p>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>
<?php endif ?>

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="info-box mb-3">
            <span class="info-box-icon bg-secondary"><i class="fas fa-id-card"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">ID verification</span>
                <span class="info-box-number"><span class="badge badge-<?= esc($idInfo['badge']) ?>" id="kpiIdStatus"><?= esc($idInfo['label']) ?></span></span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box mb-3">
            <span class="info-box-icon bg-primary"><i class="fas fa-tools"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Skills listed</span>
                <span class="info-box-number" id="kpiSkills"><?= (int) ($stats['skills_count'] ?? 0) ?></span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box mb-3">
            <span class="info-box-icon bg-indigo"><i class="fas fa-battery-half"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Fatigue level</span>
                <span class="info-box-number"><span id="kpiFatigue"><?= (int) ($stats['fatigue_level'] ?? 0) ?></span>%</span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box mb-3">
            <span class="info-box-icon bg-teal"><i class="fas fa-home"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Evacuation occupancy</span>
                <span class="info-box-number" id="kpiEvac"><?= esc($stats['evacuation_occupancy'] ?? '0%') ?></span>
            </div>
        </div>
    </div>
</div>

<?php if (empty($stats['has_profile'])): ?>
<div class="row">
    <div class="col-12">
        <div class="alert alert-warning">
            <strong>Volunteer profile not linked.</strong> Complete your profile and skills so MDRRMO can verify and assign you.
            <a href="<?= base_url('profile') ?>" class="alert-link ml-1">Go to profile</a>
        </div>
    </div>
</div>
<?php endif ?>

<div class="row">
    <div class="col-12">
        <div class="card card-outline card-danger">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bell mr-1"></i> Your alerts</h3>
                <span class="badge badge-light" id="alertCount"><?= count($alerts) ?> active</span>
            </div>
            <div class="card-body p-0" id="volAlertsBody">
                <?php if (empty($alerts)): ?>
                    <p class="text-muted p-3 mb-0">No alerts right now. Stay available and watch for deployment notifications.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($alerts as $alert):
                            $badge = match ($alert['level'] ?? 'info') {
                                'danger'  => 'danger',
                                'warning' => 'warning',
                                default   => 'info',
                            };
                            ?>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div>
                                <span class="badge badge-<?= $badge ?> mr-2"><?= esc(ucfirst($alert['level'] ?? 'info')) ?></span>
                                <strong><?= esc($alert['title'] ?? '') ?></strong>
                                <div class="small text-muted"><?= esc($alert['message'] ?? '') ?></div>
                            </div>
                            <?php if (! empty($alert['url'])): ?>
                                <a href="<?= esc($alert['url']) ?>" class="btn btn-xs btn-outline-primary drms-icon-action" title="Open"><i class="fas fa-external-link-alt"></i></a>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-map-marked-alt mr-1 text-danger"></i> Active incidents — <?= esc($drms->serviceMunicipality) ?></h3>
                <div class="card-tools d-flex flex-wrap align-items-center">
                    <select id="filterIncidentCategory" class="custom-select custom-select-sm mr-2" style="width: auto; max-width: 160px;" title="Filter by category">
                        <option value="">All Categories</option>
                        <?php foreach ($incidentTypes ?? [] as $typeKey => $typeLabel): ?>
                            <option value="<?= esc($typeKey) ?>"><?= esc($typeLabel) ?></option>
                        <?php endforeach ?>
                    </select>
                    <select id="filterIncidentBarangay" class="custom-select custom-select-sm mr-2" style="width: auto; max-width: 150px;" title="Filter by barangay">
                        <option value="">All Barangays</option>
                        <?php foreach ($barangays ?? [] as $b): ?>
                            <option value="<?= (int) $b['id'] ?>"><?= esc($b['name']) ?></option>
                        <?php endforeach ?>
                    </select>
                    <button type="button" class="btn btn-xs btn-outline-primary mr-2" id="btnFocusBaras"><i class="fas fa-crosshairs"></i> Focus area</button>
                    <span class="badge badge-light p-1 border" id="mapPinCount">Loading…</span>
                </div>
            </div>
            <div class="card-body p-0 position-relative">
                <div id="drmsIncidentsMap" class="drms-incidents-map"></div>
                <div class="drms-map-legend">
                    <div class="drms-map-legend-icons">
                        <?php foreach ($mapMarkerLegend as $item): ?>
                        <span class="drms-map-legend-icon" title="<?= esc($item['label']) ?>">
                            <i class="fas <?= esc($item['icon']) ?>" style="background:<?= esc($item['marker_bg']) ?>; border-color:<?= esc($item['border_color'] ?? '#ffc107') ?>;"></i>
                            <?= esc($item['label']) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="incidentMapDetailsModal" tabindex="-1" role="dialog" aria-labelledby="volIncModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title font-weight-bold" id="volIncModalTitle">
                    <i class="fas fa-exclamation-triangle mr-2"></i> Incident Details
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <h4 class="font-weight-bold text-dark mb-1" id="volIncModalHeaderTitle"></h4>
                    <span class="badge badge-secondary" id="volIncModalRef"></span>
                    <span class="badge badge-info ml-1" id="volIncModalCategory"></span>
                    <span class="badge ml-1" id="volIncModalSeverity"></span>
                </div>
                <div class="p-3 bg-light rounded border mb-3">
                    <div class="row">
                        <div class="col-sm-6 mb-2">
                            <small class="text-muted d-block font-weight-bold">Barangay / Location</small>
                            <span id="volIncModalBarangay" class="text-dark"></span>
                        </div>
                        <div class="col-sm-6 mb-2">
                            <small class="text-muted d-block font-weight-bold">Status</small>
                            <span class="badge" id="volIncModalStatus"></span>
                        </div>
                        <div class="col-12 mb-0">
                            <small class="text-muted d-block font-weight-bold">Reported Date & Time</small>
                            <span id="volIncModalReportedAt" class="text-dark"></span>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="font-weight-bold text-muted small text-uppercase mb-1">Description</label>
                    <p id="volIncModalDesc" class="text-dark mb-0 bg-white p-2 border rounded" style="white-space: pre-line; min-height: 50px;"></p>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header border-0">
                <h3 class="card-title"><i class="fas fa-chart-line mr-1"></i> Your deployments (7 days)</h3>
            </div>
            <div class="card-body">
                <div class="drms-chart-wrap">
                    <canvas id="volDeploymentsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-link mr-1"></i> Quick actions</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <a href="<?= base_url('admin/matching') ?>" class="btn btn-info btn-block"><i class="fas fa-random mr-1"></i> Volunteer Dispatch</a>
                    </div>
                    <div class="col-md-6 mb-2">
                        <a href="<?= base_url('profile') ?>" class="btn btn-primary btn-block"><i class="fas fa-user mr-1"></i> My profile &amp; skills</a>
                    </div>
                    <div class="col-md-6 mb-2">
                        <a href="<?= base_url('report') ?>" class="btn btn-danger btn-block" target="_blank"><i class="fas fa-bullhorn mr-1"></i> Report incident</a>
                    </div>
                    <div class="col-md-6 mb-2">
                        <a href="<?= base_url('evac-centers') ?>" class="btn btn-secondary btn-block" target="_blank"><i class="fas fa-map-marker-alt mr-1"></i> Evacuation centers</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clipboard-list mr-1"></i> My assignments</h3>
                <div class="card-tools">
                    <span class="badge badge-light"><?= count($assignments) ?> record(s)</span>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-striped table-hover mb-0" id="volAssignmentsTable">
                    <thead>
                        <tr>
                            <th>Deployed</th>
                            <th>Reference</th>
                            <th>Incident</th>
                            <th>Severity</th>
                            <th>Status</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($assignments === []): ?>
                            <tr><td colspan="6" class="text-muted text-center py-4">No deployments yet. When MDRRMO assigns you, they will appear here.</td></tr>
                        <?php else: ?>
                            <?php foreach ($assignments as $row):
                                $sev = strtolower((string) ($row['severity'] ?? ''));
                                $sevBadge = match ($sev) {
                                    'critical' => 'danger',
                                    'high'     => 'warning',
                                    'low'      => 'info',
                                    default    => 'secondary',
                                };
                                $depStatus = strtolower((string) ($row['status'] ?? ''));
                                $depBadge = $depStatus === 'deployed' ? 'danger' : 'success';
                                ?>
                            <tr>
                                <td><?= esc($row['deployed_at'] ?? '—') ?></td>
                                <td><code><?= esc($row['reference_code'] ?? '—') ?></code></td>
                                <td><?= esc($row['title'] ?? '—') ?></td>
                                <td><span class="badge badge-<?= $sevBadge ?>"><?= esc(ucfirst($sev ?: '—')) ?></span></td>
                                <td><span class="badge badge-<?= $depBadge ?>"><?= esc(ucfirst($depStatus ?: '—')) ?></span></td>
                                <td class="small"><?= esc($row['notes'] ?? '—') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<script>
$(function () {
    var mapCfg = <?= json_encode($mapConfig) ?>;
    var heatmapUrl = <?= json_encode($incidentsHeatmapUrl) ?>;
    var incidentsMapUrl = <?= json_encode($incidentsMapUrl) ?>;
    var portalLiveUrl = <?= json_encode($portalLiveUrl) ?>;
    var chartLabels = <?= json_encode($chartLabels) ?>;
    var chartValues = <?= json_encode($chartValues) ?>;

    var incidentsMap = null;
    var barasBoundaryLayer = null;
    var incidentLayer = null;
    var heatLayer = null;
    var heatmapOn = false;
    var volChart = null;

    function swNeBounds(b) {
        return L.latLngBounds([b.sw.lat, b.sw.lng], [b.ne.lat, b.ne.lng]);
    }

    function barasPolygonStyle() {
        return {
            color: '#8b2635',
            weight: 3,
            fillColor: '#c62828',
            fillOpacity: 0.22,
            opacity: 1
        };
    }

    function incidentMarkerIcon(props) {
        var p = props || {};
        return L.divIcon({
            className: 'drms-incident-marker-wrap',
            html: '<div class="drms-incident-marker" style="background:' + (p.marker_bg || '#546e7a') + ';border-color:' + (p.border_color || '#ffc107') + '">' +
                '<i class="fas ' + (p.icon || 'fa-exclamation-triangle') + '"></i></div>',
            iconSize: [34, 34],
            iconAnchor: [17, 17],
            popupAnchor: [0, -16]
        });
    }

    function incidentPopupHtml(props) {
        var p = props || {};
        var typeLabel = p.type_label || p.incident_type || 'Incident';
        var severity = (p.severity || '').charAt(0).toUpperCase() + (p.severity || '').slice(1);
        return '<div class="small p-1"><strong>' + (p.reference || '') + '</strong><br>' +
            (p.title || '') + '<br>' +
            '<span class="badge badge-light">' + typeLabel + '</span> ' +
            '<span class="badge badge-secondary">' + severity + '</span><br>' +
            '<small class="text-muted font-italic">Click marker for full details</small></div>';
    }

    var cachedVolIncidentsGeo = null;

    function openIncidentModal(p) {
        p = p || {};
        $('#volIncModalHeaderTitle').text(p.title || 'Untitled Incident');
        $('#volIncModalRef').text(p.reference || 'N/A');
        $('#volIncModalCategory').text(p.type_label || p.incident_type || 'Incident');
        
        var sevBadgeClass = 'badge-warning';
        var sev = (p.severity || '').toLowerCase();
        if (sev === 'critical') sevBadgeClass = 'badge-danger';
        else if (sev === 'high') sevBadgeClass = 'badge-warning';
        else if (sev === 'low') sevBadgeClass = 'badge-success';
        
        $('#volIncModalSeverity').attr('class', 'badge ' + sevBadgeClass).text(sev.toUpperCase());
        $('#volIncModalBarangay').text(p.barangay_name || 'Baras');
        
        var statusClass = 'badge-info';
        if (p.status === 'in_progress') statusClass = 'badge-primary';
        else if (p.status === 'resolved') statusClass = 'badge-success';
        $('#volIncModalStatus').attr('class', 'badge ' + statusClass).text((p.status || '').replace('_', ' ').toUpperCase());
        
        $('#volIncModalReportedAt').text(p.created_at || 'Recently');
        $('#volIncModalDesc').text(p.description || 'No detailed description provided.');
        
        $('#incidentMapDetailsModal').modal('show');
    }

    function addIncidents(geojson) {
        if (!incidentsMap) return;
        if (geojson) cachedVolIncidentsGeo = geojson;
        if (!cachedVolIncidentsGeo) return;
        if (incidentLayer) incidentsMap.removeLayer(incidentLayer);
        incidentLayer = L.layerGroup().addTo(incidentsMap);

        var selCategory = $('#filterIncidentCategory').val();
        var selBarangay = $('#filterIncidentBarangay').val();

        var count = 0;
        var now = new Date();
        var curMonth = now.getMonth();
        var curYear = now.getFullYear();
        var monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        var currentMonthName = monthNames[curMonth];
        var monthCount = 0;

        (cachedVolIncidentsGeo.features || []).forEach(function (f) {
            var c = f.geometry.coordinates;
            var p = f.properties || {};

            if (p.created_at) {
                var incDate = new Date(p.created_at.replace(/-/g, '/'));
                if (!isNaN(incDate.getTime())) {
                    if (incDate.getMonth() === curMonth && incDate.getFullYear() === curYear) {
                        monthCount++;
                    }
                } else {
                    monthCount++;
                }
            } else {
                monthCount++;
            }

            if (selCategory && p.incident_type !== selCategory) return;
            if (selBarangay && String(p.barangay_id) !== String(selBarangay)) return;

            var marker = L.marker([c[1], c[0]], { icon: incidentMarkerIcon(p) });
            marker.bindTooltip(incidentPopupHtml(p), {
                direction: 'top',
                offset: [0, -18],
                opacity: 0.95
            });
            marker.on('click', function () {
                openIncidentModal(p);
            });
            marker.addTo(incidentLayer);
            count++;
        });
        $('#mapPinCount').text(monthCount + ' incident(s) in ' + currentMonthName + ' ' + curYear + ' · ' + (mapCfg.municipality || ''));
    }

    function renderChart(labels, values) {
        var canvas = document.getElementById('volDeploymentsChart');
        if (!canvas) return;
        if (volChart) { volChart.destroy(); volChart = null; }
        volChart = new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Deployments',
                    data: values,
                    backgroundColor: 'rgba(21, 101, 192, 0.65)',
                    borderColor: '#1565c0',
                    borderWidth: 1
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
    renderChart(chartLabels, chartValues);

    function renderAlerts(alerts) {
        var $body = $('#volAlertsBody').empty();
        $('#alertCount').text((alerts || []).length + ' active');
        if (!alerts || !alerts.length) {
            $body.append('<p class="text-muted p-3 mb-0">No alerts right now.</p>');
            return;
        }
        var $ul = $('<ul class="list-group list-group-flush"></ul>');
        alerts.forEach(function (a) {
            var badge = a.level === 'danger' ? 'danger' : (a.level === 'warning' ? 'warning' : 'info');
            var html = '<li class="list-group-item"><span class="badge badge-' + badge + ' mr-2">' + (a.level || 'info') + '</span>' +
                '<strong>' + (a.title || '') + '</strong><div class="small text-muted">' + (a.message || '') + '</div></li>';
            $ul.append(html);
        });
        $body.append($ul);
    }

    function renderAssignments(rows) {
        var $tb = $('#volAssignmentsTable tbody').empty();
        if (!rows || !rows.length) {
            $tb.append('<tr><td colspan="6" class="text-muted text-center py-4">No deployments yet.</td></tr>');
            return;
        }
        rows.forEach(function (r) {
            $tb.append('<tr><td>' + (r.deployed_at || '—') + '</td><td><code>' + (r.reference_code || '—') + '</code></td>' +
                '<td>' + (r.title || '—') + '</td><td>' + (r.severity || '—') + '</td><td>' + (r.status || '—') + '</td>' +
                '<td class="small">' + (r.notes || '—') + '</td></tr>');
        });
    }

    function idStatusLabel(status) {
        var s = (status || 'none').toLowerCase();
        if (s === 'approved') return 'Verified';
        if (s === 'verified') return 'Verified';
        if (s === 'pending') return 'Pending review';
        if (s === 'rejected') return 'Rejected';
        return 'Not submitted';
    }

    function refreshPortal() {
        $.getJSON(portalLiveUrl, function (res) {
            if (!res || res.status !== 'ok') return;
            if (res.stats) {
                $('#kpiAssignments').text(res.stats.active_assignments || 0);
                $('#kpiAvailability').text(res.stats.availability_label || '—');
                $('#kpiIncidents').text(res.stats.active_incidents || 0);
                $('#kpiNotifications').text(res.stats.unread_notifications || 0);
                $('#kpiSkills').text(res.stats.skills_count || 0);
                $('#kpiFatigue').text(res.stats.fatigue_level || 0);
                $('#kpiEvac').text(res.stats.evacuation_occupancy || '0%');
                var idLabel = idStatusLabel(res.stats.id_verification_status);
                var idBadge = (idLabel === 'Verified') ? 'success' : ((res.stats.id_verification_status || '') === 'pending' ? 'warning' : 'secondary');
                $('#kpiIdStatus').text(idLabel).removeClass('badge-success badge-warning badge-danger badge-secondary').addClass('badge-' + idBadge);
            }
            if (res.alerts) renderAlerts(res.alerts);
            if (res.assignments) renderAssignments(res.assignments);
            if (res.chart) renderChart(res.chart.labels || [], res.chart.values || []);
            if (res.map && incidentsMap) {
                addIncidents(res.map);
                focusBaras();
            }
        });
    }

    setInterval(refreshPortal, 20000);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) refreshPortal();
    });

    var availUrl = <?= json_encode(base_url('profile/availability')) ?>;
    var csrf = DRMS.csrf();

    $('#volAvailButtons').on('click', '.btn-vol-avail', function () {
        var $btn = $(this);
        var status = $btn.data('status');
        var payload = { availability_status: status };
        if (csrf.name) payload[csrf.name] = csrf.hash;

        $btn.prop('disabled', true);
        $.ajax({
            url: availUrl,
            method: 'POST',
            data: payload,
            dataType: 'json',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function (res) {
            $('#volAvailButtons .btn-vol-avail').removeClass('active');
            $btn.addClass('active');
            if (res.data && res.data.availability_label) {
                $('#kpiAvailability').text(res.data.availability_label);
            }
            DRMS.toast('Availability updated', res.message || 'Saved.', 'success');
            refreshPortal();
        }).fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Could not update availability.';
            DRMS.toast('Error', msg, 'error');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });
});
</script>
<?= $this->endSection() ?>
