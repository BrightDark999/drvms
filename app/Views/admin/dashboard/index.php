<?= $this->extend('layouts/admin') ?>

<?= $this->section('breadcrumb') ?>
<li class="breadcrumb-item active">Dashboard</li>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?= (int) $stats['active_incidents'] ?></h3>
                <p>Active Incidents</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            <a href="<?= base_url('admin/incidents') ?>" class="small-box-footer">Open list <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= (int) $stats['volunteers_on_duty'] ?></h3>
                <p>Volunteers On Duty</p>
            </div>
            <div class="icon"><i class="fas fa-hands-helping"></i></div>
            <a href="<?= base_url('admin/volunteers') ?>" class="small-box-footer">Open list <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= (int) ($stats['pending_verifications'] ?? 0) ?></h3>
                <p>Pending Verification</p>
            </div>
            <div class="icon"><i class="fas fa-clipboard-check"></i></div>
            <a href="<?= base_url('admin/incidents') ?>" class="small-box-footer">Review incidents <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= esc($stats['evacuation_occupancy']) ?></h3>
                <p>Evacuation Occupancy</p>
            </div>
            <div class="icon"><i class="fas fa-home"></i></div>
            <a href="<?= base_url('admin/evacuation-centers') ?>" class="small-box-footer">Open centers <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card card-outline card-danger">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bell mr-1"></i> Operational alerts</h3>
                <span class="badge badge-light"><?= count($alerts) ?> active</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($alerts)): ?>
                    <p class="text-muted p-3 mb-0">No rule-based alerts right now. System is monitoring incidents and evacuation occupancy.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($alerts as $alert):
                            $badge = match ($alert['level']) {
                                'danger'  => 'danger',
                                'warning' => 'warning',
                                default   => 'info',
                            };
                            ?>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div>
                                <span class="badge badge-<?= $badge ?> mr-2"><?= esc(ucfirst($alert['level'])) ?></span>
                                <strong><?= esc($alert['title']) ?></strong>
                                <div class="small text-muted"><?= esc($alert['message']) ?></div>
                            </div>
                            <?php if (! empty($alert['url'])): ?>
                                <a href="<?= esc($alert['url']) ?>" class="btn btn-xs btn-outline-primary drms-icon-action" title="View details" aria-label="View details"><i class="fas fa-eye" aria-hidden="true"></i></a>
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
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-map-marked-alt mr-1 text-danger"></i> Active incidents — <?= esc($drms->serviceMunicipality) ?>, <?= esc($drms->serviceProvince) ?>
                </h3>
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
                    <button type="button" class="btn btn-xs btn-outline-primary mr-2" id="btnFocusBaras" title="Zoom to Baras service area">
                        <i class="fas fa-crosshairs"></i> Focus Baras
                    </button>
                    <span class="badge badge-light p-1 border" id="mapPinCount">Loading…</span>
                </div>
            </div>
            <div class="card-body p-0 position-relative">
                <div id="drmsIncidentsMap" class="drms-incidents-map"></div>
                <div class="drms-map-legend">
                    <div class="drms-map-legend-row mb-1">
                        <span class="drms-map-legend-swatch drms-map-legend-area"></span> Baras, Rizal boundary (OSM)
                    </div>
                    <div class="drms-map-legend-icons">
                        <?php foreach ($mapMarkerLegend as $item): ?>
                        <span class="drms-map-legend-icon" title="<?= esc($item['label']) ?>">
                            <i class="fas <?= esc($item['icon']) ?>" style="background-color:<?= esc($item['marker_bg']) ?>; border:2px solid <?= esc($item['border_color']) ?>; color:#fff;"></i>
                            <?= esc($item['label']) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="incidentMapDetailsModal" tabindex="-1" role="dialog" aria-labelledby="incModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title font-weight-bold" id="incModalTitle">
                    <i class="fas fa-exclamation-triangle mr-2"></i> Incident Details
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <h4 class="font-weight-bold text-dark mb-1" id="incModalHeaderTitle"></h4>
                    <span class="badge badge-secondary" id="incModalRef"></span>
                    <span class="badge badge-info ml-1" id="incModalCategory"></span>
                    <span class="badge ml-1" id="incModalSeverity"></span>
                </div>
                <div class="p-3 bg-light rounded border mb-3">
                    <div class="row">
                        <div class="col-sm-6 mb-2">
                            <small class="text-muted d-block font-weight-bold">Barangay / Location</small>
                            <span id="incModalBarangay" class="text-dark"></span>
                        </div>
                        <div class="col-sm-6 mb-2">
                            <small class="text-muted d-block font-weight-bold">Status</small>
                            <span class="badge" id="incModalStatus"></span>
                        </div>
                        <div class="col-12 mb-0">
                            <small class="text-muted d-block font-weight-bold">Reported Date & Time</small>
                            <span id="incModalReportedAt" class="text-dark"></span>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="font-weight-bold text-muted small text-uppercase mb-1">Description</label>
                    <p id="incModalDesc" class="text-dark mb-0 bg-white p-2 border rounded" style="white-space: pre-line; min-height: 50px;"></p>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <a href="<?= base_url('admin/incidents') ?>" class="btn btn-danger btn-sm">
                    <i class="fas fa-external-link-alt mr-1"></i> Open Incidents Module
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header border-0">
                <h3 class="card-title"><i class="fas fa-chart-line mr-1"></i> Incidents reported (last 7 days)</h3>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-2" id="chartSourceLabel">Loading from database…</p>
                <div class="drms-chart-wrap">
                    <canvas id="incidentsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent incidents</h3>
                <div class="card-tools">
                    <a href="<?= base_url('admin/incidents') ?>" class="btn btn-sm btn-primary drms-icon-action" title="Report incident" aria-label="Report incident">
                        <i class="fas fa-plus" aria-hidden="true"></i>
                    </a>
                    <a href="<?= base_url('admin/incidents') ?>" class="btn btn-sm btn-outline-secondary ml-1 drms-icon-action" title="View all incidents" aria-label="View all incidents">
                        <i class="fas fa-list" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
            <div class="card-body table-responsive">
                <p class="text-muted small mb-2">Live data from the database — same records as the Incidents module.</p>
                <table id="incidentsTable" class="table table-bordered table-striped table-hover w-100">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Severity</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Barangay</th>
                            <th>Reported</th>
                        </tr>
                    </thead>
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
    var incidentsMap = null;
    var barasBoundaryLayer = null;
    var outsideMaskLayer = null;
    var incidentLayer = null;
    var heatLayer = null;
    var heatmapOn = false;

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

    function createMap() {
        var el = document.getElementById('drmsIncidentsMap');
        if (!el || typeof L === 'undefined') {
            return null;
        }
        if (incidentsMap) {
            incidentsMap.remove();
            incidentsMap = null;
            barasBoundaryLayer = null;
            outsideMaskLayer = null;
            incidentLayer = null;
        }
        incidentsMap = L.map(el, { minZoom: 11, maxZoom: 17 });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap'
        }).addTo(incidentsMap);
        incidentsMap.setMaxBounds(swNeBounds(mapCfg.maxBounds));
        return incidentsMap;
    }

    function addOutsideMask(map, boundaryData) {
        if (outsideMaskLayer) {
            map.removeLayer(outsideMaskLayer);
            outsideMaskLayer = null;
        }
        var ring = boundaryData.features && boundaryData.features[0]
            && boundaryData.features[0].geometry
            && boundaryData.features[0].geometry.coordinates[0];
        if (!ring || !ring.length) {
            return null;
        }
        var inner = ring.map(function (c) { return [c[1], c[0]]; }).reverse();
        var world = [[-85, -180], [-85, 180], [85, 180], [85, -180], [-85, -180]];
        outsideMaskLayer = L.polygon([world, inner], {
            fillColor: '#1e2a3a',
            fillOpacity: 0.5,
            stroke: false,
            interactive: false
        }).addTo(map);
        return outsideMaskLayer;
    }

    function drawBarasShape(map, boundaryData) {
        if (barasBoundaryLayer) {
            map.removeLayer(barasBoundaryLayer);
            barasBoundaryLayer = null;
        }
        addOutsideMask(map, boundaryData);
        barasBoundaryLayer = L.geoJSON(boundaryData, {
            style: barasPolygonStyle,
            onEachFeature: function (feature, layer) {
                layer.bindPopup('<strong>' + mapCfg.label + '</strong><br>Official municipal boundary (OpenStreetMap)');
            }
        }).addTo(map);
        return barasBoundaryLayer;
    }

    function incidentMarkerIcon(props) {
        var p = props || {};
        var iconClass = p.icon || 'fa-exclamation-triangle';
        var bg = p.marker_bg || '#546e7a';
        var border = p.border_color || '#ffc107';
        return L.divIcon({
            className: 'drms-incident-marker-wrap',
            html: '<div class="drms-incident-marker" style="background:' + bg + ';border-color:' + border + '">' +
                '<i class="fas ' + iconClass + '"></i></div>',
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

    var cachedIncidentsGeo = null;

    function openIncidentModal(p) {
        p = p || {};
        $('#incModalHeaderTitle').text(p.title || 'Untitled Incident');
        $('#incModalRef').text(p.reference || 'N/A');
        $('#incModalCategory').text(p.type_label || p.incident_type || 'Incident');
        
        var sevBadgeClass = 'badge-warning';
        var sev = (p.severity || '').toLowerCase();
        if (sev === 'critical') sevBadgeClass = 'badge-danger';
        else if (sev === 'high') sevBadgeClass = 'badge-warning';
        else if (sev === 'low') sevBadgeClass = 'badge-success';
        
        $('#incModalSeverity').attr('class', 'badge ' + sevBadgeClass).text(sev.toUpperCase());
        $('#incModalBarangay').text(p.barangay_name || 'Baras');
        
        var statusClass = 'badge-info';
        if (p.status === 'in_progress') statusClass = 'badge-primary';
        else if (p.status === 'resolved') statusClass = 'badge-success';
        $('#incModalStatus').attr('class', 'badge ' + statusClass).text((p.status || '').replace('_', ' ').toUpperCase());
        
        $('#incModalReportedAt').text(p.created_at || 'Recently');
        $('#incModalDesc').text(p.description || 'No detailed description provided.');
        
        $('#incidentMapDetailsModal').modal('show');
    }

    function addIncidents(geojson) {
        if (!incidentsMap) {
            return;
        }
        if (geojson) {
            cachedIncidentsGeo = geojson;
        }
        if (!cachedIncidentsGeo) {
            return;
        }
        if (incidentLayer) {
            incidentsMap.removeLayer(incidentLayer);
        }
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

        (cachedIncidentsGeo.features || []).forEach(function (f) {
            var c = f.geometry.coordinates;
            var p = f.properties || {};

            // Monthly counter calculation
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

            // Category & Barangay Filters
            if (selCategory && p.incident_type !== selCategory) {
                return;
            }
            if (selBarangay && String(p.barangay_id) !== String(selBarangay)) {
                return;
            }

            var marker = L.marker([c[1], c[0]], { icon: incidentMarkerIcon(p) });

            // Hover tooltip
            marker.bindTooltip(incidentPopupHtml(p), {
                direction: 'top',
                offset: [0, -18],
                opacity: 0.95
            });

            // Click modal
            marker.on('click', function () {
                openIncidentModal(p);
            });

            marker.addTo(incidentLayer);
            count++;
        });

        $('#mapPinCount').text(monthCount + ' incident(s) in ' + currentMonthName + ' ' + curYear + ' · ' + mapCfg.municipality);
        return count;
    }

    function combineBounds() {
        var bounds = swNeBounds(mapCfg.bounds);
        if (barasBoundaryLayer && typeof barasBoundaryLayer.getBounds === 'function') {
            bounds = barasBoundaryLayer.getBounds();
        }
        if (incidentLayer) {
            incidentLayer.eachLayer(function (layer) {
                if (typeof layer.getLatLng === 'function') {
                    bounds.extend(layer.getLatLng());
                } else if (typeof layer.getBounds === 'function') {
                    bounds.extend(layer.getBounds());
                }
            });
        }
        return bounds;
    }

    function focusBaras() {
        if (!incidentsMap) {
            return;
        }
        incidentsMap.fitBounds(combineBounds().pad(0.02), { maxZoom: 14 });
    }

    function initMapLayers(boundaryData, incidentGeo) {
        var map = createMap();
        if (!map) {
            $('#mapPinCount').text('Map container missing');
            return;
        }
        drawBarasShape(map, boundaryData);
        addIncidents(incidentGeo || { features: [] });
        setTimeout(function () {
            map.invalidateSize();
            focusBaras();
        }, 100);
        $('#btnFocusBaras').off('click').on('click', focusBaras);
        $('#filterIncidentCategory, #filterIncidentBarangay').off('change').on('change', function () {
            addIncidents();
        });
    }

    $.when(
        $.getJSON(mapCfg.boundaryUrl),
        $.getJSON(<?= json_encode($incidentsMapUrl) ?>)
    ).done(function (boundaryRes, incidentRes) {
        initMapLayers(boundaryRes[0], incidentRes[0]);
    }).fail(function () {
        var map = createMap();
        if (map) {
            focusBaras();
            $.getJSON(<?= json_encode($incidentsMapUrl) ?>).done(function (res) {
                addIncidents(res);
            });
        }
    });

    var table = DRMS.apiDataTable('#incidentsTable', '/incidents?limit=25', [
        { data: 'reference_code' },
        { data: 'title' },
        { data: 'incident_type', defaultContent: '—' },
        { data: 'severity', render: function (s) {
            var cls = 'badge-secondary';
            if (s === 'low') cls = 'badge-success';
            if (s === 'moderate') cls = 'badge-warning';
            if (s === 'high') cls = 'badge-orange';
            if (s === 'critical') cls = 'badge-danger';
            return '<span class="badge ' + cls + '">' + (s ? s.toUpperCase() : '—') + '</span>';
        }},
        { data: 'priority_tag', defaultContent: '—' },
        { data: 'status', render: function (st) {
            var cls = 'badge-secondary';
            if (st === 'reported') cls = 'badge-secondary';
            if (st === 'verified') cls = 'badge-info';
            if (st === 'in_progress') cls = 'badge-warning';
            if (st === 'resolved') cls = 'badge-success';
            if (st === 'closed') cls = 'badge-dark';
            return '<span class="badge ' + cls + '">' + (st ? st.toUpperCase().replace('_', ' ') : '—') + '</span>';
        }},
        { data: 'barangay_name', defaultContent: '—' },
        { data: 'created_at', defaultContent: '—' }
    ], {
        order: [[7, 'desc']],
        pageLength: 10
    });

    DRMS.apiGet('/reports/dashboard-chart').then(function (res) {
        var payload = DRMS.apiUnwrap(res);
        if (!payload || !payload.labels) return;
        var chartData = payload.data || payload.values || [];
        var total = payload.total !== undefined ? payload.total : chartData.reduce(function (a, b) { return a + b; }, 0);
        $('#chartSourceLabel').text('Real-time database records (' + total + ' total in window)');
        var chartCanvas = document.getElementById('incidentsChart');
        if (!chartCanvas) return;
        var ctx = chartCanvas.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: payload.labels,
                datasets: [{
                    label: 'Incidents Reported',
                    data: chartData,
                    borderColor: '#c62828',
                    backgroundColor: 'rgba(198, 40, 40, 0.12)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        ticks: { beginAtZero: true, stepSize: 1 }
                    }]
                }
            }
        });
    }).catch(function (err) {
        console.warn('Dashboard chart fetch:', err);
    });
});
</script>
<?= $this->endSection() ?>