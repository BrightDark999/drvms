<?= $this->extend('layouts/public') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h1 class="h3 mb-1">Report a disaster</h1>
                    <p class="text-muted mb-4">Citizens and barangay officials can report floods, fires, landslides, injuries, or rescue needs. MDRRMO will verify your report.</p>

                    <?php if (session('error')): ?>
                        <div class="alert alert-danger"><?= esc(session('error')) ?></div>
                    <?php endif ?>
                    <?php if (session('errors')): ?>
                        <div class="alert alert-danger">
                            <?php foreach ((array) session('errors') as $e): ?>
                                <?= esc($e) ?><br>
                            <?php endforeach ?>
                        </div>
                    <?php endif ?>

                    <form action="<?= base_url('report/submit') ?>" method="post" enctype="multipart/form-data" id="reportForm">
                        <?= csrf_field() ?>
                        <input type="hidden" name="latitude" id="reportLat" value="<?= esc(old('latitude')) ?>">
                        <input type="hidden" name="longitude" id="reportLng" value="<?= esc(old('longitude')) ?>">

                        <div class="form-group">
                            <label>Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="<?= old('title') ?>" required maxlength="255" placeholder="e.g. Flooding on Main Street">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="What happened, who is affected, access routes…"><?= old('description') ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Incident type</label>
                                <select name="incident_type" class="form-control" required>
                                    <?php foreach ($incidentTypes as $key => $label): ?>
                                        <option value="<?= esc($key) ?>" <?= old('incident_type') === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Severity</label>
                                <select name="severity" class="form-control" required>
                                    <?php foreach (['low','moderate','high','critical'] as $s): ?>
                                        <option value="<?= $s ?>" <?= old('severity', 'moderate') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Barangay <span class="text-danger">*</span></label>
                            <select name="barangay_id" id="reportBarangay" class="form-control" required>
                                <option value="">— Select your barangay —</option>
                                <?php foreach ($barangays as $b): ?>
                                    <option value="<?= (int) $b['id'] ?>"
                                        data-lat="<?= esc((string) ($b['latitude'] ?? '')) ?>"
                                        data-lng="<?= esc((string) ($b['longitude'] ?? '')) ?>"
                                        <?= (string) old('barangay_id') === (string) $b['id'] ? 'selected' : '' ?>>
                                        <?= esc($b['name']) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>

                        <div class="form-group mb-2">
                            <label class="d-block">Where did it happen? <span class="text-danger">*</span></label>
                            <p class="text-muted small mb-2">
                                No need to type coordinates. Select your barangay, then <strong>tap the map</strong> where the incident is,
                                or use <strong>My location</strong> if you are at the scene.
                            </p>
                            <div id="drmsReportMap" class="drms-report-map"></div>
                            <p class="small text-muted mt-2 mb-0" id="reportMapHint">Select a barangay to place the pin, then tap the map to adjust.</p>
                        </div>
                        <div class="mb-3">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnUseGps">
                                <i class="fas fa-crosshairs"></i> Use my location
                            </button>
                        </div>

                        <div class="form-group">
                            <label>Photo / video evidence</label>
                            <input type="file" name="media[]" class="form-control-file" accept="image/*,video/*" multiple>
                            <small class="text-muted">JPG, PNG, MP4 — max 10 MB each</small>
                        </div>
                        <?= view('partials/security/captcha_honeypot') ?>
                        <div>
                            <button type="submit" class="btn btn-danger btn-lg">Submit report</button>
                            <a href="<?= base_url('/') ?>" class="btn btn-link">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
(function () {
    var mapCfg = <?= json_encode($mapConfig) ?>;
    var mapEl = document.getElementById('drmsReportMap');
    var latInput = document.getElementById('reportLat');
    var lngInput = document.getElementById('reportLng');
    var brgySelect = document.getElementById('reportBarangay');
    var hintEl = document.getElementById('reportMapHint');
    var form = document.getElementById('reportForm');

    if (!mapEl || typeof L === 'undefined') {
        return;
    }

    var map = L.map(mapEl, { minZoom: 11, maxZoom: 17 }).setView(
        [mapCfg.center.lat, mapCfg.center.lng],
        mapCfg.zoom || 13
    );

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    var marker = null;
    var pinSet = false;

    function setCoords(lat, lng, hint) {
        latInput.value = Number(lat).toFixed(6);
        lngInput.value = Number(lng).toFixed(6);
        pinSet = true;
        if (hintEl && hint) {
            hintEl.textContent = hint;
        }
    }

    function placeMarker(lat, lng, panZoom, hint) {
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            marker.on('dragend', function () {
                var p = marker.getLatLng();
                setCoords(p.lat, p.lng, 'Pin set — drag or tap the map to adjust if needed.');
            });
        }
        setCoords(lat, lng, hint);
        if (panZoom) {
            map.setView([lat, lng], Math.max(map.getZoom(), 14));
        }
    }

    map.on('click', function (e) {
        placeMarker(e.latlng.lat, e.latlng.lng, false, 'Location marked on map.');
    });

    brgySelect.addEventListener('change', function () {
        var opt = brgySelect.options[brgySelect.selectedIndex];
        var lat = parseFloat(opt.getAttribute('data-lat') || '');
        var lng = parseFloat(opt.getAttribute('data-lng') || '');
        if (!isNaN(lat) && !isNaN(lng)) {
            placeMarker(lat, lng, true, 'Pin placed in ' + opt.text + '. Tap the map if you need a more exact spot.');
        } else if (mapCfg.center) {
            placeMarker(mapCfg.center.lat, mapCfg.center.lng, true, 'Barangay has no map data yet — tap the map where the incident happened.');
        }
    });

    document.getElementById('btnUseGps').addEventListener('click', function () {
        if (!navigator.geolocation) {
            alert('Location is not supported on this device. Please tap the map instead.');
            return;
        }
        navigator.geolocation.getCurrentPosition(function (pos) {
            placeMarker(pos.coords.latitude, pos.coords.longitude, true, 'Using your current GPS location.');
        }, function () {
            alert('Could not get GPS. Please tap the map where the incident happened.');
        });
    });

    form.addEventListener('submit', function (e) {
        if (!pinSet && brgySelect.value) {
            var opt = brgySelect.options[brgySelect.selectedIndex];
            var lat = parseFloat(opt.getAttribute('data-lat') || '');
            var lng = parseFloat(opt.getAttribute('data-lng') || '');
            if (!isNaN(lat) && !isNaN(lng)) {
                latInput.value = lat.toFixed(6);
                lngInput.value = lng.toFixed(6);
                pinSet = true;
            }
        }
        if (!latInput.value || !lngInput.value) {
            e.preventDefault();
            alert('Please select a barangay and tap the map (or use My location) so responders know where to go.');
        }
    });

    setTimeout(function () { map.invalidateSize(); }, 200);

    var oldLat = parseFloat(latInput.value || '');
    var oldLng = parseFloat(lngInput.value || '');
    if (!isNaN(oldLat) && !isNaN(oldLng)) {
        placeMarker(oldLat, oldLng, true, 'Location restored from your previous entry.');
    } else if (brgySelect.value) {
        brgySelect.dispatchEvent(new Event('change'));
    }
})();
</script>
<?= $this->endSection() ?>
