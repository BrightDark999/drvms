<?= $this->extend('layouts/admin') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<style>
.admin-incident-map {
    height: 240px;
    width: 100%;
    border-radius: 6px;
    border: 1px solid #ced4da;
    margin-bottom: 0.5rem;
    z-index: 1;
}
</style>
<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Home</a></li>
<li class="breadcrumb-item active">Incidents</li>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Disaster incidents</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-sm btn-primary" id="btnAddIncident">
                <i class="fas fa-plus mr-1"></i> Report incident
            </button>
        </div>
    </div>
    <div class="card-body table-responsive">
        <table id="tblIncidents" class="table table-bordered table-striped w-100" style="min-width:900px">
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
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="incidentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="formIncident" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" id="crudRecordId" value="">
            <div class="modal-header">
                <h5 class="modal-title" id="incidentModalTitle">Report new incident</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required maxlength="255">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="incidentDescription" class="form-control" rows="3" placeholder="What happened, where, who is affected…"></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Incident type <span class="text-danger">*</span></label>
                        <select name="incident_type" class="form-control" required>
                            <?php foreach ($incidentTypes as $key => $label): ?>
                                <option value="<?= esc($key) ?>"><?= esc($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Severity <span class="text-danger">*</span></label>
                        <select name="severity" class="form-control" required>
                            <option value="low">Low</option>
                            <option value="moderate" selected>Moderate</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group" id="fieldStatus" style="display:none">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="reported">Reported</option>
                            <option value="verified">Verified</option>
                            <option value="in_progress">In progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Barangay</label>
                        <select name="barangay_id" id="adminIncidentBarangay" class="form-control">
                            <option value="">— Select barangay —</option>
                            <?php foreach ($barangays as $b): ?>
                                <option value="<?= (int) $b['id'] ?>" data-lat="<?= esc($b['latitude'] ?? '') ?>" data-lng="<?= esc($b['longitude'] ?? '') ?>"><?= esc($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Interactive Incident Map Location Picker -->
                <div class="form-group mb-2">
                    <label class="d-block mb-1">
                        <i class="fas fa-map-marker-alt text-danger mr-1"></i> Incident Location on Map
                    </label>
                    <p class="text-muted small mb-2">Select a barangay to center the map, then tap or drag the pin to set the exact coordinates.</p>
                    <div id="adminIncidentModalMap" class="admin-incident-map"></div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <button type="button" class="btn btn-xs btn-outline-secondary" id="btnAdminUseGps">
                            <i class="fas fa-crosshairs mr-1"></i> Use My Current Location
                        </button>
                        <small class="text-muted" id="adminMapHint">Tap map to place/adjust pin</small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Latitude (optional)</label>
                        <input type="text" name="latitude" id="adminIncidentLat" class="form-control form-control-sm" placeholder="14.5171">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Longitude (optional)</label>
                        <input type="text" name="longitude" id="adminIncidentLng" class="form-control form-control-sm" placeholder="121.2672">
                    </div>
                </div>

                <div class="form-group">
                    <label>Photo / video evidence</label>
                    <input type="file" name="media[]" id="incidentMediaFiles" class="form-control-file" accept="image/*,video/*" multiple>
                    <div id="incidentMediaList" class="small mt-2 text-muted"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="btnSaveIncident">Save</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="incidentMediaModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="incidentMediaModalTitle">Incident evidence</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="incidentMediaModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
$(function () {
    var baseUrl = <?= json_encode(rtrim(base_url('admin/incidents'), '/')) ?>;
    var mapCfg = <?= json_encode($mapConfig ?? drms_leaflet_map_config()) ?>;
    var modalMap = null;
    var modalMarker = null;

    var table = DRMS.serverDataTable('#tblIncidents', <?= json_encode($dataUrl) ?>, [
        { data: 0 },
        { data: 1 },
        { data: 2 },
        { data: 3 },
        { data: 4 },
        { data: 5 },
        { data: 6 },
        { data: 7 },
        { data: 8, orderable: false, searchable: false }
    ], { order: [[7, 'desc']] });

    function initModalMap(lat, lng) {
        var mapEl = document.getElementById('adminIncidentModalMap');
        if (!mapEl || typeof L === 'undefined') return;

        var defaultLat = lat || (mapCfg.center && mapCfg.center.lat) || 14.5171;
        var defaultLng = lng || (mapCfg.center && mapCfg.center.lng) || 121.2672;
        var defaultZoom = (lat && lng) ? 15 : (mapCfg.zoom || 13);

        if (!modalMap) {
            modalMap = L.map('adminIncidentModalMap', { minZoom: 11, maxZoom: 18 }).setView([defaultLat, defaultLng], defaultZoom);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(modalMap);

            modalMap.on('click', function (e) {
                setModalCoords(e.latlng.lat, e.latlng.lng, true);
            });

            $('#btnAdminUseGps').on('click', function () {
                if (!navigator.geolocation) {
                    alert('Geolocation is not supported by your browser.');
                    return;
                }
                navigator.geolocation.getCurrentPosition(function (pos) {
                    var uLat = pos.coords.latitude;
                    var uLng = pos.coords.longitude;
                    setModalCoords(uLat, uLng, true);
                    modalMap.setView([uLat, uLng], 16);
                }, function (err) {
                    alert('Unable to retrieve your location: ' + err.message);
                }, { enableHighAccuracy: true, timeout: 10000 });
            });
        } else {
            modalMap.setView([defaultLat, defaultLng], defaultZoom);
        }

        if (lat && lng) {
            setModalCoords(lat, lng, false);
        } else if (modalMarker) {
            modalMap.removeLayer(modalMarker);
            modalMarker = null;
        }

        setTimeout(function () {
            if (modalMap) modalMap.invalidateSize();
        }, 250);
    }

    function setModalCoords(lat, lng, pan) {
        var nLat = Number(lat);
        var nLng = Number(lng);
        $('#adminIncidentLat').val(nLat.toFixed(6));
        $('#adminIncidentLng').val(nLng.toFixed(6));
        $('#adminMapHint').text('Location set: ' + nLat.toFixed(5) + ', ' + nLng.toFixed(5));

        if (!modalMarker) {
            modalMarker = L.marker([nLat, nLng], { draggable: true }).addTo(modalMap);
            modalMarker.on('dragend', function (e) {
                var pos = e.target.getLatLng();
                setModalCoords(pos.lat, pos.lng, false);
            });
        } else {
            modalMarker.setLatLng([nLat, nLng]);
        }

        if (pan && modalMap) {
            modalMap.panTo([nLat, nLng]);
        }
    }

    $('#adminIncidentBarangay').on('change', function () {
        var $opt = $(this).find('option:selected');
        var bLat = $opt.data('lat');
        var bLng = $opt.data('lng');
        if (bLat && bLng) {
            setModalCoords(bLat, bLng, true);
            if (modalMap) modalMap.setView([bLat, bLng], 15);
        }
    });

    $('#incidentModal').on('shown.bs.modal', function () {
        var cLat = parseFloat($('#adminIncidentLat').val());
        var cLng = parseFloat($('#adminIncidentLng').val());
        initModalMap(cLat || null, cLng || null);
    });

    DRMS.bindCrud({
        tableSelector: '#tblIncidents',
        dataTable: table,
        form: '#formIncident',
        modal: '#incidentModal',
        addBtn: '#btnAddIncident',
        baseUrl: baseUrl,
        idField: '#crudRecordId',
        onReset: function () {
            $('#incidentModalTitle').text('Report new incident');
            $('#fieldStatus').hide();
            $('#incidentMediaList').empty();
            $('#adminIncidentLat').val('');
            $('#adminIncidentLng').val('');
            $('#adminMapHint').text('Tap map to place/adjust pin');
            if (modalMarker && modalMap) {
                modalMap.removeLayer(modalMarker);
                modalMarker = null;
            }
        },
        onLoad: function (d) {
            $('#incidentModalTitle').text('Edit incident');
            $('#fieldStatus').show();
            $('input[name=title]').val(d.title || '');
            $('textarea[name=description]').val(d.description || '');
            $('select[name=incident_type]').val(d.incident_type || 'other');
            $('select[name=severity]').val(d.severity || 'moderate');
            $('select[name=status]').val(d.status || 'reported');
            $('select[name=barangay_id]').val(d.barangay_id || '');
            $('#adminIncidentLat').val(d.latitude || '');
            $('#adminIncidentLng').val(d.longitude || '');
            if (d.latitude && d.longitude) {
                $('#adminMapHint').text('Location set: ' + Number(d.latitude).toFixed(5) + ', ' + Number(d.longitude).toFixed(5));
            } else {
                $('#adminMapHint').text('Tap map to place/adjust pin');
            }
            var $ml = $('#incidentMediaList').empty();
            if ((d.media || []).length) {
                $ml.append('<div class="mb-2"><strong>Attached evidence:</strong></div>');
                $ml.append(renderMediaGallery(d.media));
            } else {
                $ml.append('<span class="text-muted">No files attached yet.</span>');
            }
            $('#incidentMediaFiles').val('');
        },
        deleteConfirm: 'Remove this incident from active lists?'
    });

    $('#tblIncidents').on('click', '.btn-verify-incident', function () {
        var id = $(this).data('id');
        DRMS.confirm('Verify incident?', 'Mark this report as verified by MDRRMO/LGU.', function () {
            DRMS.postJson('<?= base_url('admin/incidents/verify') ?>/' + id, {}, function (res) {
                DRMS.toast('Verified', res.message, 'success');
                if (table) {
                    table.ajax.reload(null, false);
                }
            });
        });
    });

    function renderMediaGallery(items) {
        if (!items || !items.length) {
            return '<p class="text-muted mb-0">No photos or videos attached to this incident.</p>';
        }
        var html = '<div class="row">';
        items.forEach(function (m) {
            var url = m.file_url || (m.file_path ? ('<?= base_url() ?>/' + m.file_path.replace(/^\/+/, '')) : '#');
            var isVideo = (m.file_type || '').indexOf('video') === 0 || /\.(mp4|webm|ogg)$/i.test(url);
            var name = m.file_name || 'Attached file';
            html += '<div class="col-md-4 col-sm-6 mb-3"><div class="card h-100 mb-0 shadow-sm">';
            if (isVideo) {
                html += '<video src="' + url + '" controls class="card-img-top" style="max-height:180px;object-fit:cover"></video>';
            } else {
                html += '<a href="' + url + '" target="_blank" rel="noopener"><img src="' + url + '" class="card-img-top" alt="' + $('<div>').text(name).html() + '" style="height:180px;object-fit:cover"></a>';
            }
            html += '<div class="card-body p-2"><a href="' + url + '" target="_blank" rel="noopener" class="btn btn-xs btn-outline-primary btn-block"><i class="fas fa-external-link-alt"></i> Open full size</a>';
            if (m.caption) {
                html += '<div class="small text-dark mt-1 font-weight-bold">' + $('<div>').text(m.caption).html() + '</div>';
            }
            html += '<div class="small text-muted mt-2 text-truncate">' + $('<div>').text(name).html() + '</div>';
            html += '</div></div></div>';
        });
        html += '</div>';
        return html;
    }
});
</script>
<?= $this->endSection() ?>
