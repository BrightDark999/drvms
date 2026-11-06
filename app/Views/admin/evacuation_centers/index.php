<?php
$totalCap = (int) ($summary['total_capacity'] ?? 0);
$totalOcc = (int) ($summary['total_occupancy'] ?? 0);
$overallPct = $totalCap > 0 ? (int) round(($totalOcc / $totalCap) * 100) : 0;

$closedCount = 0;
foreach ($centers as $c) {
    if (strtolower((string) ($c['status'] ?? '')) === 'closed') {
        $closedCount++;
    }
}
$statusLabels = ['Open', 'Full', 'Closed'];
$statusValues = [
    (int) ($summary['open_count'] ?? 0),
    (int) ($summary['full_count'] ?? 0),
    $closedCount,
];

$occLabels = [];
$occValues = [];
foreach ($centers as $c) {
    $occLabels[] = mb_substr((string) ($c['name'] ?? ''), 0, 18);
    $occValues[] = $totalCap > 0 ? (int) round(((int) ($c['current_occupancy'] ?? 0) / max(1, (int) ($c['capacity'] ?? 1))) * 100) : 0;
}
?>

<?= $this->extend('layouts/admin') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Home</a></li>
<li class="breadcrumb-item active">Evacuation centers</li>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
    <p class="text-muted mb-2 mb-md-0">Monitor shelter capacity, occupancy, and locations across <?= esc($drms->serviceMunicipality) ?>.</p>
    <div>
        <a href="<?= base_url('evac-centers') ?>" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">
            <i class="fas fa-external-link-alt"></i> Public page
        </a>
        <button type="button" class="btn btn-sm btn-primary drms-icon-action" id="btnAddEvac" title="Add center">
            <i class="fas fa-plus"></i> Add center
        </button>
    </div>
</div>

<div class="row" id="evacKpiRow">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3 id="evacKpiOpen"><?= (int) ($summary['open_count'] ?? 0) ?></h3>
                <p>Open centers</p>
            </div>
            <div class="icon"><i class="fas fa-door-open"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3 id="evacKpiOcc"><?= $totalOcc ?> <small>/ <?= $totalCap ?></small></h3>
                <p>Total occupancy</p>
            </div>
            <div class="icon"><i class="fas fa-users"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3 id="evacKpiFamilies"><?= (int) ($summary['families_total'] ?? 0) ?></h3>
                <p>Families registered</p>
            </div>
            <div class="icon"><i class="fas fa-people-arrows"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3 id="evacKpiMedical"><?= (int) ($summary['medical_needs'] ?? 0) ?></h3>
                <p>Medical needs</p>
            </div>
            <div class="icon"><i class="fas fa-ambulance"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-map mr-1"></i> Center locations</h3>
                <span class="badge badge-light ml-2"><?= count($mapCenters) ?> on map</span>
            </div>
            <div class="card-body p-0">
                <div id="drmsEvacAdminMap" class="drms-evac-admin-map"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Overall occupancy</h3></div>
            <div class="card-body text-center">
                <div class="drms-evac-gauge" style="--pct: <?= $overallPct ?>">
                    <span><?= $overallPct ?>%</span>
                </div>
                <p class="small text-muted mb-0 mt-2"><?= $totalOcc ?> of <?= $totalCap ?> slots used</p>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Status mix</h3></div>
            <div class="card-body">
                <div class="drms-chart-wrap">
                    <canvas id="chartEvacStatus"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Occupancy by center (%)</h3></div>
            <div class="card-body">
                <div class="drms-chart-wrap drms-chart-wrap--medium">
                    <canvas id="chartEvacOcc"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row drms-evac-cards mb-3">
    <?php foreach ($centers as $c):
        $cap = (int) ($c['capacity'] ?? 0);
        $occ = (int) ($c['current_occupancy'] ?? 0);
        $pct = $cap > 0 ? (int) round(($occ / $cap) * 100) : 0;
        $status = strtolower((string) ($c['status'] ?? ''));
        $badge = match ($status) {
            'open'   => 'success',
            'full'   => 'danger',
            'closed' => 'secondary',
            default  => 'light',
        };
        ?>
        <div class="col-lg-4 col-md-6">
            <div class="card drms-evac-center-card h-100 <?= ! empty($c['is_full']) ? 'drms-evac-center-card--full' : '' ?>"
                 data-evac-id="<?= (int) ($c['id'] ?? 0) ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="mb-0"><?= esc($c['name'] ?? '') ?></h5>
                        <span class="badge badge-<?= $badge ?>"><?= esc(ucfirst($status)) ?></span>
                    </div>
                    <div class="small text-muted mb-2"><?= esc($c['barangay_name'] ?? '—') ?></div>
                    <div class="d-flex justify-content-between small mb-1">
                        <span>Occupancy</span>
                        <strong><?= $occ ?> / <?= $cap ?></strong>
                    </div>
                    <div class="progress progress-sm mb-2">
                        <div class="progress-bar <?= $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success') ?>" style="width: <?= min(100, $pct) ?>%"></div>
                    </div>
                    <div class="small text-muted">
                        <?= (int) ($c['families_registered'] ?? 0) ?> families ·
                        <?= (int) ($c['available_slots'] ?? 0) ?> slots open ·
                        <?= (int) ($c['medical_needs_count'] ?? 0) ?> medical
                    </div>
                </div>
                <div class="card-footer py-2 bg-white border-top-0">
                    <button type="button" class="btn btn-sm btn-outline-primary btn-evac-families"
                            data-id="<?= (int) ($c['id'] ?? 0) ?>"
                            data-name="<?= esc($c['name'] ?? '', 'attr') ?>">
                        <i class="fas fa-users"></i> View families
                    </button>
                </div>
            </div>
        </div>
    <?php endforeach ?>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">All centers</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" id="btnRefreshEvacTable" title="Refresh table">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>
    <div class="card-body table-responsive">
        <table id="tblEvac" class="table table-bordered table-striped table-hover w-100">
            <thead>
                <tr><th>Name</th><th>Barangay</th><th>Occupancy</th><th>Status</th><th>Actions</th></tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="evacModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form id="formEvac" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" id="crudRecordId" value="">
            <div class="modal-header">
                <h5 class="modal-title" id="evacModalTitle">Add evacuation center</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Fields marked <span class="text-danger">*</span> are required. Use the sections below so staff and evacuees know who to call and what families must bring.</p>

                <h6 class="text-uppercase text-muted border-bottom pb-2 mb-3"><i class="fas fa-building mr-1"></i> Center identity</h6>
                <div class="form-group">
                    <label for="evacName">Shelter / facility name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="evacName" class="form-control" required placeholder="e.g. San Jose Elementary School — Gymnasium">
                    <small class="form-text text-muted">Official name shown on the public evacuation map and reports.</small>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="evacBarangay">Barangay</label>
                        <select name="barangay_id" id="evacBarangay" class="form-control">
                            <option value="">— Select barangay —</option>
                            <?php foreach ($barangays as $b): ?>
                                <option value="<?= (int) $b['id'] ?>"><?= esc($b['name']) ?></option>
                            <?php endforeach ?>
                        </select>
                        <small class="form-text text-muted">Used for map pin (barangay coordinates) and filtering.</small>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="evacStatus">Shelter status</label>
                        <select name="status" id="evacStatus" class="form-control">
                            <option value="open">Open — accepting evacuees</option>
                            <option value="full">Full — no new families</option>
                            <option value="closed">Closed — not operating</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="evacAddress">Full street address</label>
                    <textarea name="address" id="evacAddress" class="form-control" rows="2" placeholder="Street, landmark, directions for drivers"></textarea>
                    <small class="form-text text-muted">Physical location of the building entrance families should use.</small>
                </div>

                <h6 class="text-uppercase text-muted border-bottom pb-2 mb-3 mt-2"><i class="fas fa-users mr-1"></i> Capacity &amp; census <small class="font-weight-normal">(staff-maintained)</small></h6>
                <p class="small text-muted">These numbers are <strong>operational counts for this shelter</strong>, not resident phone numbers. Update them as families check in or leave.</p>
                <div class="row">
                    <div class="col-md-6 col-lg-3 form-group">
                        <label for="evacCapacity">Maximum capacity</label>
                        <input type="number" name="capacity" id="evacCapacity" class="form-control" value="0" min="0" required>
                        <small class="form-text text-muted">Total persons (beds/mats) this center can hold.</small>
                    </div>
                    <div class="col-md-6 col-lg-3 form-group">
                        <label for="evacOccupancy">Current headcount</label>
                        <input type="number" name="current_occupancy" id="evacOccupancy" class="form-control" value="0" min="0" required>
                        <small class="form-text text-muted">People physically inside the shelter now.</small>
                    </div>
                    <div class="col-md-6 col-lg-3 form-group">
                        <label for="evacFamilies">Families registered</label>
                        <input type="number" name="families_registered" id="evacFamilies" class="form-control" value="0" min="0">
                        <small class="form-text text-muted">Pre-registered families (online or at desk), including not yet checked in.</small>
                    </div>
                    <div class="col-md-6 col-lg-3 form-group">
                        <label for="evacMedical">Medical needs flagged</label>
                        <input type="number" name="medical_needs_count" id="evacMedical" class="form-control" value="0" min="0">
                        <small class="form-text text-muted">Families/members needing medical attention at this center.</small>
                    </div>
                </div>

                <h6 class="text-uppercase text-muted border-bottom pb-2 mb-3 mt-2"><i class="fas fa-phone mr-1"></i> Center contacts <small class="font-weight-normal">(not evacuee numbers)</small></h6>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="evacContactPerson">On-duty manager / focal person</label>
                        <input type="text" name="contact_person" id="evacContactPerson" class="form-control" placeholder="e.g. Brgy. Captain, Evac Center Manager, MDRRMO duty officer">
                        <small class="form-text text-muted">Name of the staff member or official responsible for this shelter shift.</small>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="evacContactPhone">Center hotline</label>
                        <input type="text" name="contact_phone" id="evacContactPhone" class="form-control" placeholder="e.g. 0917-123-4567">
                        <small class="form-text text-muted">Phone for evacuees and responders to ask about slots, directions, or emergencies at <em>this</em> center — not a resident’s personal mobile.</small>
                    </div>
                </div>

                <h6 class="text-uppercase text-muted border-bottom pb-2 mb-3 mt-2"><i class="fas fa-clipboard-list mr-1"></i> Family intake — procedure &amp; requirements</h6>
                <p class="small text-muted mb-2">Shown to families on the public evacuation page when they select this center. Explain the steps to be allowed inside and what documents to bring.</p>
                <div class="form-group">
                    <label for="evacIntakeProcedures">Check-in procedure for families</label>
                    <textarea name="intake_procedures" id="evacIntakeProcedures" class="form-control" rows="6" placeholder="1. Go only if status is OPEN and slots are available.&#10;2. Report to the registration desk with family head name and member count.&#10;3. Present valid government ID for the family head (and adults if requested).&#10;4. Declare medical needs, pregnancy, infants, and PWDs.&#10;5. Receive your FAM- QR token and keep it for check-in.&#10;6. Wait for staff room/bay assignment; follow center rules."></textarea>
                    <small class="form-text text-muted">Step-by-step instructions from arrival to assignment inside the shelter.</small>
                </div>
                <div class="form-group mb-0">
                    <label for="evacRequiredItems">Required documents &amp; items to bring</label>
                    <textarea name="required_items" id="evacRequiredItems" class="form-control" rows="4" placeholder="Valid ID (family head), FAM QR token after registration, clothes, hygiene kit, prescribed medicines, infant supplies, drinking water, important documents in a sealed bag."></textarea>
                    <small class="form-text text-muted">IDs, tokens, and belongings families must have to be admitted.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save center</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="evacFamiliesModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0" id="evacFamiliesModalTitle">Registered families</h5>
                    <p class="small text-muted mb-0 mt-1" id="evacFamiliesModalMeta"></p>
                </div>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-0">
                <div id="evacFamiliesLoading" class="text-center py-5 text-muted">
                    <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                    <div>Loading families…</div>
                </div>
                <div id="evacFamiliesEmpty" class="text-center py-5 text-muted d-none">
                    <i class="fas fa-users-slash fa-2x mb-2"></i>
                    <div>No families registered at this center yet.</div>
                    <p class="small mb-0">Families can register on the <a href="<?= base_url('evac-centers') ?>" target="_blank" rel="noopener">public evacuation page</a>.</p>
                </div>
                <div class="table-responsive d-none" id="evacFamiliesTableWrap">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Family head</th>
                                <th>Members</th>
                                <th>Contact</th>
                                <th>Medical</th>
                                <th>Token</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="evacFamiliesTableBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRefreshEvacFamilies">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="evacFamilyDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="evacFamilyDetailTitle">Family details</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="evacFamilyDetailBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning d-none" id="btnEvacFamilyCheckout">
                    <i class="fas fa-sign-out-alt"></i> Check out family
                </button>
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
    var mapCfg = <?= json_encode($mapConfig) ?>;
    var mapCenters = <?= json_encode($mapCenters) ?>;

    var table = DRMS.apiDataTable('#tblEvac', '/evacuation-centers', [
        { data: 'name' },
        { data: 'barangay_name', defaultContent: '—' },
        { data: null, orderable: false, render: function (row) {
            var cap = parseInt(row.capacity, 10) || 0;
            var occ = parseInt(row.current_occupancy, 10) || 0;
            var pct = cap > 0 ? Math.round((occ / cap) * 100) : 0;
            var bar = pct >= 90 ? 'bg-danger' : (pct >= 70 ? 'bg-warning' : 'bg-success');
            return '<div class="small">' + occ + ' / ' + cap + ' (' + pct + '%)</div>'
                + '<div class="progress progress-xs"><div class="progress-bar ' + bar + '" style="width:' + Math.min(100, pct) + '%"></div></div>';
        }},
        { data: 'status', render: function (s) {
            var c = { open: 'success', full: 'danger', closed: 'secondary' };
            return '<span class="badge badge-' + (c[s] || 'light') + '">' + (s || '') + '</span>';
        }},
        { data: null, orderable: false, searchable: false, render: function (row) {
            return '<div class="btn-group btn-group-xs">'
                + '<button type="button" class="btn btn-primary btn-evac-families" data-id="' + row.id + '" data-name="' + escHtml(row.name || '') + '" title="Families"><i class="fas fa-users"></i></button>'
                + '<button type="button" class="btn btn-info btn-api-edit" data-id="' + row.id + '" title="Edit"><i class="fas fa-edit"></i></button>'
                + '<button type="button" class="btn btn-danger btn-api-delete" data-id="' + row.id + '" title="Delete"><i class="fas fa-trash"></i></button>'
                + '</div>';
        }}
    ], { order: [[0, 'asc']] });

    var activeFamiliesCenterId = 0;
    var activeFamiliesCenterName = '';
    var cachedFamilies = [];

    function escHtml(s) {
        return $('<div>').text(s == null ? '' : String(s)).html();
    }

    function familyStatusBadge(f) {
        if (f.check_in_status === 'checked_out') {
            return '<span class="badge badge-secondary">Checked out</span>';
        }
        if (f.check_in_status === 'checked_in') {
            return '<span class="badge badge-success">Checked in</span>';
        }
        return '<span class="badge badge-warning">Not checked in</span>';
    }

    function familiesMetaText(meta) {
        meta = meta || {};
        return (meta.count || 0) + ' total · '
            + (meta.checked_in || 0) + ' inside · '
            + (meta.pending || 0) + ' pending · '
            + (meta.checked_out || 0) + ' checked out';
    }

    function checkoutFamily(familyId, familyName) {
        if (!activeFamiliesCenterId) {
            return;
        }
        var label = familyName ? ('Check out ' + familyName + '?') : 'Check out this family?';
        DRMS.confirm(label, 'This frees their slots at the center and marks them as checked out.', function () {
            DRMS.apiPost('/evacuation-centers/' + activeFamiliesCenterId + '/families/' + familyId + '/checkout', {})
                .then(function (res) {
                    DRMS.toast('Checked out', res.message || 'Family checked out.', res.status === 'already' ? 'warning' : 'success');
                    $('#evacFamilyDetailModal').modal('hide');
                    loadFamiliesModal(activeFamiliesCenterId, activeFamiliesCenterName);
                    if (table) {
                        table.ajax.reload(null, false);
                    }
                }).catch(function (err) {
                    DRMS.toast('Error', err.message, 'error');
                });
        });
    }

    function renderFamilyDetail(f) {
        var medical = (f.medical_needs || '').trim();
        var html = '<dl class="row mb-0">';
        html += '<dt class="col-sm-4">Family head</dt><dd class="col-sm-8"><strong>' + escHtml(f.family_head_name) + '</strong></dd>';
        html += '<dt class="col-sm-4">Members</dt><dd class="col-sm-8">' + escHtml(f.members_count) + '</dd>';
        html += '<dt class="col-sm-4">Contact phone</dt><dd class="col-sm-8">' + (f.contact_phone ? escHtml(f.contact_phone) : '<span class="text-muted">—</span>') + '</dd>';
        html += '<dt class="col-sm-4">Medical needs</dt><dd class="col-sm-8">' + (medical ? '<span class="text-danger">' + escHtml(medical) + '</span>' : '<span class="text-muted">None declared</span>') + '</dd>';
        html += '<dt class="col-sm-4">QR token</dt><dd class="col-sm-8"><code>' + escHtml(f.family_qr_token) + '</code></dd>';
        html += '<dt class="col-sm-4">Registered</dt><dd class="col-sm-8">' + escHtml(f.registered_at_display) + '</dd>';
        html += '<dt class="col-sm-4">Checked in</dt><dd class="col-sm-8">' + escHtml(f.checked_in_at_display) + '</dd>';
        html += '<dt class="col-sm-4">Checked out</dt><dd class="col-sm-8">' + escHtml(f.checked_out_at_display) + '</dd>';
        html += '<dt class="col-sm-4">Status</dt><dd class="col-sm-8">' + familyStatusBadge(f) + '</dd>';
        html += '<dt class="col-sm-4">Evacuation center</dt><dd class="col-sm-8">' + escHtml(f.center_name) + '</dd>';
        html += '<dt class="col-sm-4">Barangay</dt><dd class="col-sm-8">' + escHtml(f.center_barangay || '—') + '</dd>';
        html += '<dt class="col-sm-4">Center address</dt><dd class="col-sm-8">' + escHtml(f.center_address || '—') + '</dd>';
        html += '<dt class="col-sm-4">Center hotline</dt><dd class="col-sm-8">' + escHtml(f.center_contact || '—') + '</dd>';
        html += '</dl>';
        return html;
    }

    function showFamilyDetail(familyId) {
        var f = cachedFamilies.find(function (row) { return Number(row.id) === Number(familyId); });
        if (!f) {
            return;
        }
        $('#evacFamilyDetailTitle').text('Family — ' + (f.family_head_name || 'Details'));
        $('#evacFamilyDetailBody').html(renderFamilyDetail(f));
        if (f.check_in_status === 'checked_out') {
            $('#btnEvacFamilyCheckout').addClass('d-none').removeData('family-id');
        } else {
            $('#btnEvacFamilyCheckout').removeClass('d-none').data('family-id', f.id).data('family-name', f.family_head_name || '');
        }
        $('#evacFamilyDetailModal').modal('show');
    }

    function renderFamiliesTable(families) {
        var $body = $('#evacFamiliesTableBody');
        $body.empty();
        families.forEach(function (f) {
            var medical = (f.medical_needs || '').trim();
            var medicalCell = medical
                ? '<span class="text-danger small" title="' + escHtml(medical) + '"><i class="fas fa-notes-medical"></i> Yes</span>'
                : '<span class="text-muted">—</span>';
            var actions = '<button type="button" class="btn btn-xs btn-outline-info btn-evac-family-detail" data-family-id="' + escHtml(f.id) + '">Details</button>';
            if (f.check_in_status !== 'checked_out') {
                actions += ' <button type="button" class="btn btn-xs btn-outline-warning btn-evac-family-checkout" data-family-id="' + escHtml(f.id) + '" data-family-name="' + escHtml(f.family_head_name) + '">Check out</button>';
            }
            var row = '<tr' + (f.check_in_status === 'checked_out' ? ' class="text-muted"' : '') + '>'
                + '<td><strong>' + escHtml(f.family_head_name) + '</strong></td>'
                + '<td>' + escHtml(f.members_count) + '</td>'
                + '<td>' + (f.contact_phone ? escHtml(f.contact_phone) : '<span class="text-muted">—</span>') + '</td>'
                + '<td>' + medicalCell + '</td>'
                + '<td><code class="small">' + escHtml(f.family_qr_token) + '</code></td>'
                + '<td>' + familyStatusBadge(f) + '</td>'
                + '<td class="text-nowrap">' + actions + '</td>'
                + '</tr>';
            $body.append(row);
        });
    }

    function loadFamiliesModal(centerId, centerName) {
        activeFamiliesCenterId = centerId;
        activeFamiliesCenterName = centerName || 'Center';
        $('#evacFamiliesModalTitle').text('Families — ' + activeFamiliesCenterName);
        $('#evacFamiliesModalMeta').text('');
        $('#evacFamiliesLoading').removeClass('d-none');
        $('#evacFamiliesEmpty').addClass('d-none');
        $('#evacFamiliesTableWrap').addClass('d-none');
        $('#evacFamiliesModal').modal('show');

        DRMS.apiGet('/evacuation-centers/' + centerId + '/families')
            .then(function (res) {
                $('#evacFamiliesLoading').addClass('d-none');
                var payload = DRMS.apiUnwrap(res);
                cachedFamilies = payload.families || [];
                var meta = payload.meta || {};
                var center = payload.center || {};
                $('#evacFamiliesModalTitle').text('Families — ' + (center.name || activeFamiliesCenterName));
                $('#evacFamiliesModalMeta').text(familiesMetaText(meta));
                if (!cachedFamilies.length) {
                    $('#evacFamiliesEmpty').removeClass('d-none');
                    return;
                }
                renderFamiliesTable(cachedFamilies);
                $('#evacFamiliesTableWrap').removeClass('d-none');
            })
            .catch(function (err) {
                $('#evacFamiliesLoading').addClass('d-none');
                $('#evacFamiliesEmpty').removeClass('d-none').find('div').first().text(err.message || 'Could not load families.');
            });
    }

    $(document).on('click', '.btn-evac-families', function () {
        loadFamiliesModal($(this).data('id'), $(this).data('name') || '');
    });

    $(document).on('click', '.btn-evac-family-detail', function () {
        showFamilyDetail($(this).data('family-id'));
    });

    $(document).on('click', '.btn-evac-family-checkout', function () {
        checkoutFamily($(this).data('family-id'), $(this).data('family-name') || '');
    });

    $('#btnEvacFamilyCheckout').on('click', function () {
        checkoutFamily($(this).data('family-id'), $(this).data('family-name') || '');
    });

    $('#btnRefreshEvacFamilies').on('click', function () {
        if (activeFamiliesCenterId) {
            loadFamiliesModal(activeFamiliesCenterId, activeFamiliesCenterName);
        }
    });

    DRMS.bindApiCrud({
        resource: '/evacuation-centers',
        tableSelector: '#tblEvac',
        dataTable: table,
        form: '#formEvac',
        modal: '#evacModal',
        addBtn: '#btnAddEvac',
        idField: '#crudRecordId',
        onSaved: function (res, recordId, finish) {
            finish();
            setTimeout(function () { window.location.reload(); }, 600);
        },
        onReset: function () { $('#evacModalTitle').text('Add evacuation center'); },
        onLoad: function (d) {
            $('#evacModalTitle').text('Edit evacuation center');
            $('input[name=name]').val(d.name || '');
            $('select[name=barangay_id]').val(d.barangay_id || '');
            $('textarea[name=address]').val(d.address || '');
            $('input[name=capacity]').val(d.capacity || 0);
            $('input[name=current_occupancy]').val(d.current_occupancy || 0);
            $('input[name=families_registered]').val(d.families_registered || 0);
            $('input[name=medical_needs_count]').val(d.medical_needs_count || 0);
            $('select[name=status]').val(d.status || 'open');
            $('input[name=contact_person]').val(d.contact_person || '');
            $('input[name=contact_phone]').val(d.contact_phone || '');
            $('textarea[name=intake_procedures]').val(d.intake_procedures || '');
            $('textarea[name=required_items]').val(d.required_items || '');
        }
    });

    $('#btnRefreshEvacTable').on('click', function () {
        table.ajax.reload(null, false);
    });

    if (typeof Chart !== 'undefined') {
        new Chart(document.getElementById('chartEvacStatus').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($statusLabels) ?>,
                datasets: [{ data: <?= json_encode($statusValues) ?>, backgroundColor: ['#28a745', '#dc3545', '#6c757d'] }]
            },
            options: { responsive: true, maintainAspectRatio: false, legend: { position: 'bottom' } }
        });

        new Chart(document.getElementById('chartEvacOcc').getContext('2d'), {
            type: 'horizontalBar',
            data: {
                labels: <?= json_encode($occLabels) ?>,
                datasets: [{ data: <?= json_encode($occValues) ?>, backgroundColor: '#1565c0' }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                scales: { xAxes: [{ ticks: { beginAtZero: true, max: 100 } }] }
            }
        });
    }

    if (typeof L !== 'undefined' && document.getElementById('drmsEvacAdminMap')) {
        var map = L.map('drmsEvacAdminMap', { minZoom: 11, maxZoom: 17 }).setView(
            [mapCfg.center.lat, mapCfg.center.lng], mapCfg.zoom || 13
        );
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18, attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        var bounds = [];
        var markers = {};
        mapCenters.forEach(function (c) {
            var m = c.marker || {};
            var icon = L.divIcon({
                className: 'drms-evac-marker-wrap',
                html: '<div class="drms-evac-marker" style="background:' + (m.marker_bg || '#2e7d32') + ';border-color:' + (m.border_color || '#1b5e20') + '"><i class="fas ' + (m.icon || 'fa-home') + '"></i></div>',
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            });
            var mk = L.marker([c.latitude, c.longitude], { icon: icon })
                .bindPopup('<strong>' + c.name + '</strong><br>' + c.occupancy + ' / ' + c.capacity + '<br>' + c.available_slots + ' slots open')
                .addTo(map);
            markers[c.id] = mk;
            bounds.push([c.latitude, c.longitude]);
        });

        if (bounds.length > 1) {
            map.fitBounds(bounds, { padding: [30, 30], maxZoom: 14 });
        } else if (bounds.length === 1) {
            map.setView(bounds[0], 14);
        }

        $('.drms-evac-center-card').on('click', function () {
            var id = parseInt($(this).data('evac-id'), 10);
            if (markers[id]) {
                map.setView(markers[id].getLatLng(), Math.max(map.getZoom(), 14));
                markers[id].openPopup();
            }
        });

        setTimeout(function () { map.invalidateSize(); }, 300);
    }

    setInterval(function () {
        DRMS.apiGet('/evacuation-centers/summary').then(function (res) {
            var s = DRMS.apiUnwrap(res);
            if (!s) return;
            $('#evacKpiOpen').text(s.open_count || 0);
            $('#evacKpiOcc').html((s.total_occupancy || 0) + ' <small>/ ' + (s.total_capacity || 0) + '</small>');
            $('#evacKpiFamilies').text(s.families_total || 0);
            $('#evacKpiMedical').text(s.medical_needs || 0);
        }).catch(function () {});
    }, 45000);
});
</script>
<?= $this->endSection() ?>
