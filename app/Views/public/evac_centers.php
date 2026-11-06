<?= $this->extend('layouts/public') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container py-5">
    <h1 class="h3 mb-2">Evacuation centers — <?= esc($drms->serviceMunicipality) ?></h1>
    <p class="text-muted mb-4">Find the nearest open shelter with available capacity. Tap a pin on the map for details.</p>

    <?php if (session('message')): ?><div class="alert alert-success"><?= esc(session('message')) ?></div><?php endif ?>
    <?php if (session('error')): ?><div class="alert alert-danger"><?= esc(session('error')) ?></div><?php endif ?>

    <div id="evacNearestBanner" class="alert alert-info border-left border-info <?= $nearest ? '' : 'd-none' ?>" style="border-left-width:4px!important">
        <strong><i class="fas fa-map-marker-alt mr-1"></i> Nearest available center:</strong>
        <span id="evacNearestText">
            <?php if ($nearest): ?>
                <?= esc($nearest['name'] ?? '') ?>
                — <?= esc($nearest['distance_km'] ?? '') ?> km away,
                <?= (int) ($nearest['available_slots'] ?? 0) ?> slots open
                <?php if (! empty($nearest['address'])): ?>
                    <br><small class="text-muted"><?= esc($nearest['address']) ?></small>
                <?php endif ?>
            <?php endif ?>
        </span>
        <small id="evacNearestNote" class="d-block text-muted mt-2 mb-0">
            <?php if ($nearest): ?>
                <?php if (($nearestSource ?? 'default') === 'gps'): ?>
                    Based on your GPS location (or coordinates in the URL).
                <?php else: ?>
                    Based on the municipal office default map center — not your phone GPS yet.
                    Click <strong>Use my location</strong> on the map for automatic nearest-center detection.
                <?php endif ?>
            <?php endif ?>
        </small>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between">
            <strong>Open centers on map</strong>
            <button type="button" class="btn btn-outline-secondary btn-sm mt-2 mt-md-0" id="btnEvacUseGps">
                <i class="fas fa-crosshairs"></i> Use my location
            </button>
        </div>
        <div class="card-body p-0">
            <div id="drmsEvacMap" class="drms-evac-map"></div>
            <?php if ($mapCenters === []): ?>
                <p class="text-muted small p-3 mb-0">No centers with map coordinates yet. Ask staff to link each center to a barangay in admin.</p>
            <?php else: ?>
                <div class="px-3 py-2 border-top small text-muted">
                    <span class="drms-evac-legend drms-evac-legend--open"></span> Open with slots
                    <span class="drms-evac-legend drms-evac-legend--full ml-3"></span> Full
                    <span class="drms-evac-legend drms-evac-legend--nearest ml-3"></span> Nearest to you
                </div>
            <?php endif ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white"><strong>Open centers</strong></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Name</th><th>Barangay</th><th>Occupancy</th><th>Families</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($centers as $c):
                                $cap = (int) ($c['capacity'] ?? 0);
                                $occ = (int) ($c['current_occupancy'] ?? 0);
                                ?>
                                <tr data-evac-id="<?= (int) ($c['id'] ?? 0) ?>" class="drms-evac-row">
                                    <td><?= esc($c['name'] ?? '') ?></td>
                                    <td><?= esc($c['barangay_name'] ?? '—') ?></td>
                                    <td><?= $occ ?> / <?= $cap ?></td>
                                    <td><?= (int) ($c['families_registered'] ?? 0) ?></td>
                                    <td><?= esc(ucfirst($c['status'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 mb-4" id="evacIntakeCard">
                <div class="card-header bg-white">
                    <strong><i class="fas fa-clipboard-list text-danger mr-1"></i> Before you go — procedure &amp; requirements</strong>
                </div>
                <div class="card-body">
                    <p class="small text-muted">Select a center below to see its check-in steps and what your family must bring. Only proceed if the center is <strong>OPEN</strong> with available slots.</p>
                    <div id="evacIntakeContent" class="small">
                        <p class="text-muted mb-0">Choose an evacuation center in the registration form to view intake details.</p>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h5">Register family at center</h2>
                    <p class="small text-muted">Pre-register online before arrival. You will receive a <strong>FAM-</strong> QR token for check-in at the registration desk.</p>
                    <form action="<?= base_url('evac-centers/register-family') ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="form-group">
                            <label>Evacuation center</label>
                            <select name="evacuation_center_id" id="evacCenterSelect" class="form-control" required>
                                <?php foreach ($centers as $c): ?>
                                    <option value="<?= (int) $c['id'] ?>"><?= esc($c['name'] ?? '') ?> — <?= esc(ucfirst($c['status'] ?? '')) ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Family head name</label>
                            <input type="text" name="family_head_name" class="form-control" required placeholder="Full name as shown on valid ID">
                            <small class="form-text text-muted">Must match the government ID presented at the registration desk.</small>
                        </div>
                        <div class="form-group">
                            <label>Number of family members</label>
                            <input type="number" name="members_count" class="form-control" value="1" min="1" required>
                            <small class="form-text text-muted">Include infants and elderly who will stay at the shelter.</small>
                        </div>
                        <div class="form-group">
                            <label>Medical needs (if any)</label>
                            <input type="text" name="medical_needs" class="form-control" placeholder="e.g. insulin, wheelchair, pregnancy">
                            <small class="form-text text-muted">Helps staff prepare assistance — not a substitute for bringing medicines.</small>
                        </div>
                        <div class="form-group">
                            <label>Your mobile number</label>
                            <input type="text" name="contact_phone" class="form-control" placeholder="Family contact while at the center">
                            <small class="form-text text-muted">Your personal phone so staff can reach your family — <em>not</em> the shelter hotline.</small>
                        </div>
                        <?= view('partials/security/captcha_honeypot') ?>
                        <button type="submit" class="btn btn-danger btn-block">Register family</button>
                    </form>
                    <hr>
                    <h2 class="h5">Check in with QR token</h2>
                    <p class="text-muted small">At the center, present your <strong>FAM-</strong> token from registration. Staff will scan or enter it to admit your family.</p>
                    <form id="formCheckInFamily" action="<?= base_url('evac-centers/check-in-family') ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="form-group">
                            <label for="familyQrToken">Family QR token</label>
                            <input type="text" name="family_qr_token" id="familyQrToken" class="form-control" placeholder="FAM-XXXXXXXX" required autocomplete="off">
                            <small class="form-text text-muted">Paste or type the token from registration (starts with FAM-).</small>
                        </div>
                        <button type="submit" class="btn btn-outline-danger btn-block" id="btnCheckInFamily">
                            <i class="fas fa-qrcode mr-1"></i> Check in family
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="checkInResultModal" tabindex="-1" role="dialog" aria-labelledby="checkInResultTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header" id="checkInModalHeader">
                <h5 class="modal-title" id="checkInResultTitle">Family check-in</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="checkInModalBody">
                <p class="text-muted mb-0">Loading…</p>
            </div>
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
(function () {
    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    var mapCfg = <?= json_encode($mapConfig) ?>;
    var centers = <?= json_encode($mapCenters) ?>;
    var centerIntake = <?= json_encode(array_values(array_map(static function ($c) {
        return [
            'id'                => (int) ($c['id'] ?? 0),
            'name'              => (string) ($c['name'] ?? ''),
            'status'            => (string) ($c['status'] ?? ''),
            'contact_person'    => (string) ($c['contact_person'] ?? ''),
            'contact_phone'     => (string) ($c['contact_phone'] ?? ''),
            'intake_procedures' => (string) ($c['intake_procedures'] ?? ''),
            'required_items'    => (string) ($c['required_items'] ?? ''),
            'available_slots'   => (int) ($c['available_slots'] ?? 0),
        ];
    }, $centers))) ?>;

    function updateNearestBanner(nearest, source) {
        var banner = document.getElementById('evacNearestBanner');
        var textEl = document.getElementById('evacNearestText');
        var noteEl = document.getElementById('evacNearestNote');
        if (!banner || !textEl || !nearest || !nearest.id) {
            if (banner) banner.classList.add('d-none');
            return;
        }
        var slots = nearest.available_slots != null ? nearest.available_slots : Math.max(0, (nearest.capacity || 0) - (nearest.current_occupancy || 0));
        var html = escapeHtml(nearest.name || '') + ' — ' + escapeHtml(String(nearest.distance_km || '0')) + ' km away, ' + slots + ' slots open';
        if (nearest.address) {
            html += '<br><small class="text-muted">' + escapeHtml(nearest.address) + '</small>';
        }
        textEl.innerHTML = html;
        if (noteEl) {
            noteEl.textContent = source === 'gps'
                ? 'Based on your GPS location.'
                : 'Based on the municipal office default map center. Use "Use my location" for GPS-based results.';
        }
        banner.classList.remove('d-none');
    }

    function renderIntake(centerId) {
        var box = document.getElementById('evacIntakeContent');
        if (!box) return;
        var c = centerIntake.find(function (x) { return x.id === parseInt(centerId, 10); });
        if (!c) {
            box.innerHTML = '<p class="text-muted mb-0">Center details not available.</p>';
            return;
        }
        var html = '<h6 class="font-weight-bold mb-2">' + escapeHtml(c.name) + '</h6>';
        html += '<p class="mb-2"><span class="badge badge-' + (c.status === 'open' ? 'success' : 'secondary') + '">' + escapeHtml(c.status.toUpperCase()) + '</span>';
        if (c.available_slots > 0) {
            html += ' <span class="text-success">' + c.available_slots + ' slots open</span>';
        }
        html += '</p>';
        if (c.contact_person || c.contact_phone) {
            html += '<p class="mb-2"><strong>Center contact (not your personal number):</strong><br>';
            if (c.contact_person) html += escapeHtml(c.contact_person) + '<br>';
            if (c.contact_phone) html += '<i class="fas fa-phone"></i> ' + escapeHtml(c.contact_phone);
            html += '</p>';
        }
        if (c.intake_procedures) {
            html += '<p class="mb-1"><strong>Check-in procedure</strong></p><pre class="bg-light p-2 rounded small mb-2" style="white-space:pre-wrap;font-family:inherit;">' + escapeHtml(c.intake_procedures) + '</pre>';
        } else {
            html += '<p class="mb-2 text-muted">Ask staff at the registration desk for check-in steps.</p>';
        }
        if (c.required_items) {
            html += '<p class="mb-0"><strong>Bring / present</strong></p><pre class="bg-light p-2 rounded small mb-0" style="white-space:pre-wrap;font-family:inherit;">' + escapeHtml(c.required_items) + '</pre>';
        }
        box.innerHTML = html;
    }

    var sel = document.getElementById('evacCenterSelect');
    if (sel) {
        sel.addEventListener('change', function () { renderIntake(sel.value); });
        if (sel.value) renderIntake(sel.value);
    }

    function showCheckInModal(payload) {
        var $modal = $('#checkInResultModal');
        var $header = $('#checkInModalHeader');
        var $title = $('#checkInResultTitle');
        var $body = $('#checkInModalBody');
        var status = payload.status || 'error';
        var d = payload.data || {};
        var isOk = status === 'ok';
        var isAlready = status === 'already';

        $header.removeClass('bg-success bg-warning bg-danger text-white');
        if (isOk) {
            $header.addClass('bg-success text-white');
            $title.text('Check-in successful');
        } else if (isAlready) {
            $header.addClass('bg-warning text-white');
            $title.text('Already checked in');
        } else {
            $header.addClass('bg-danger text-white');
            $title.text('Check-in failed');
        }

        if (status === 'error' && !d.family_head_name) {
            $body.html('<p class="mb-0">' + escapeHtml(payload.message || 'Could not check in this family.') + '</p>');
        } else {
            var rows = [
                ['Family head', d.family_head_name],
                ['QR token', d.family_qr_token],
                ['Members', d.members_count != null ? String(d.members_count) : ''],
                ['Evacuation center', d.center_name],
                ['Barangay', d.center_barangay],
                ['Center address', d.center_address],
                ['Center hotline', d.center_contact],
                ['Medical needs', d.medical_needs || '—'],
                ['Contact phone', d.contact_phone || '—'],
                ['Registered', d.registered_at],
                ['Checked in', d.checked_in_at || '—']
            ];
            var html = '<p class="mb-3">' + escapeHtml(payload.message || '') + '</p>';
            html += '<dl class="row small mb-0">';
            rows.forEach(function (row) {
                if (!row[1] || row[1] === '—') {
                    if (row[0] === 'Medical needs' || row[0] === 'Contact phone') {
                        html += '<dt class="col-sm-4">' + escapeHtml(row[0]) + '</dt>';
                        html += '<dd class="col-sm-8">' + escapeHtml(row[1]) + '</dd>';
                    }
                    return;
                }
                html += '<dt class="col-sm-4">' + escapeHtml(row[0]) + '</dt>';
                html += '<dd class="col-sm-8">' + escapeHtml(String(row[1])) + '</dd>';
            });
            html += '</dl>';
            $body.html(html);
        }

        $modal.modal('show');
    }

    $('#formCheckInFamily').on('submit', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $('#btnCheckInFamily');
        $btn.prop('disabled', true);
        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function (res) {
            showCheckInModal(res);
            if (res.status === 'ok') {
                $form.find('input[name=family_qr_token]').val('');
            }
        }).fail(function (xhr) {
            var res = xhr.responseJSON || { status: 'error', message: 'Server error. Please try again.' };
            showCheckInModal(res);
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    var mapEl = document.getElementById('drmsEvacMap');
    if (!mapEl || typeof L === 'undefined' || !centers.length) {
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

    var bounds = [];
    var markersById = {};

    function evacIcon(marker, size) {
        size = size || 36;
        return L.divIcon({
            className: 'drms-evac-marker-wrap',
            html: '<div class="drms-evac-marker' + (marker.is_nearest ? ' drms-evac-marker--nearest' : '') + '" style="background:' + marker.marker.marker_bg + ';border-color:' + marker.marker.border_color + ';width:' + size + 'px;height:' + size + 'px;">' +
                '<i class="fas ' + marker.marker.icon + '"></i></div>',
            iconSize: [size, size],
            iconAnchor: [size / 2, size / 2],
            popupAnchor: [0, -size / 2]
        });
    }

    function popupHtml(c) {
        var lines = ['<strong>' + c.name + '</strong>'];
        if (c.barangay_name) {
            lines.push('<div class="text-muted small">' + c.barangay_name + '</div>');
        }
        if (c.address) {
            lines.push('<div>' + c.address + '</div>');
        }
        lines.push('<div>Occupancy: ' + c.occupancy + ' / ' + c.capacity + '</div>');
        if (c.is_full) {
            lines.push('<div class="text-danger"><strong>Full</strong></div>');
        } else {
            lines.push('<div class="text-success">' + c.available_slots + ' slots available</div>');
        }
        if (c.contact_phone) {
            lines.push('<div><i class="fas fa-phone"></i> Center hotline: ' + c.contact_phone + '</div>');
        }
        if (c.contact_person) {
            lines.push('<div class="small text-muted">Focal: ' + c.contact_person + '</div>');
        }
        if (c.is_nearest) {
            lines.push('<div class="badge badge-warning mt-1">Nearest to you</div>');
        }
        return lines.join('');
    }

    centers.forEach(function (c) {
        var latLng = [c.latitude, c.longitude];
        var m = L.marker(latLng, { icon: evacIcon(c) }).addTo(map);
        m.bindPopup(popupHtml(c));
        markersById[c.id] = m;
        bounds.push(latLng);

        m.on('click', function () {
            document.querySelectorAll('.drms-evac-row').forEach(function (row) {
                row.classList.toggle('table-active', parseInt(row.getAttribute('data-evac-id'), 10) === c.id);
            });
        });
    });

    if (bounds.length > 1) {
        map.fitBounds(bounds, { padding: [40, 40], maxZoom: 14 });
    } else if (bounds.length === 1) {
        map.setView(bounds[0], 14);
    }

    var userMarker = null;

    function showUserLocation(lat, lng) {
        if (userMarker) {
            map.removeLayer(userMarker);
        }
        userMarker = L.circleMarker([lat, lng], {
            radius: 8,
            color: '#1565c0',
            fillColor: '#42a5f5',
            fillOpacity: 0.9,
            weight: 2
        }).addTo(map).bindPopup('You are here');

        fetch('<?= base_url('api/evac/nearest') ?>?lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.nearest || !data.nearest.id) {
                    return;
                }
                updateNearestBanner(data.nearest, 'gps');
                var nearestId = data.nearest.id;
                Object.keys(markersById).forEach(function (id) {
                    var isNearest = parseInt(id, 10) === nearestId;
                    var center = centers.find(function (x) { return x.id === parseInt(id, 10); });
                    if (center) {
                        center.is_nearest = isNearest;
                        markersById[id].setIcon(evacIcon(center, isNearest ? 42 : 36));
                    }
                });
                if (markersById[nearestId]) {
                    markersById[nearestId].openPopup();
                }
            })
            .catch(function () {});
    }

    var startLat = <?= json_encode($lat) ?>;
    var startLng = <?= json_encode($lng) ?>;
    if (startLat && startLng && <?= json_encode($hasGpsFromQuery) ?>) {
        showUserLocation(startLat, startLng);
    }

    var btnGps = document.getElementById('btnEvacUseGps');
    if (btnGps) {
        btnGps.addEventListener('click', function () {
            if (!navigator.geolocation) {
                alert('Location is not supported on this device.');
                return;
            }
            navigator.geolocation.getCurrentPosition(function (pos) {
                showUserLocation(pos.coords.latitude, pos.coords.longitude);
                map.setView([pos.coords.latitude, pos.coords.longitude], Math.max(map.getZoom(), 14));
            }, function () {
                alert('Could not get your location. Please browse the map manually.');
            });
        });
    }

    document.querySelectorAll('.drms-evac-row').forEach(function (row) {
        row.addEventListener('click', function () {
            var id = parseInt(row.getAttribute('data-evac-id'), 10);
            if (markersById[id]) {
                map.setView(markersById[id].getLatLng(), Math.max(map.getZoom(), 14));
                markersById[id].openPopup();
            }
        });
    });

    setTimeout(function () { map.invalidateSize(); }, 200);
})();
</script>
<?= $this->endSection() ?>
