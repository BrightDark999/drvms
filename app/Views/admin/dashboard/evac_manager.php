<?php
$assignedCenter = $assignedCenter ?? null;
$hasAssignment  = ! empty($assignedCenter);
$centerId       = (int) ($assignedCenter['id'] ?? $stats['assigned_center_id'] ?? 0);
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('breadcrumb') ?>
<li class="breadcrumb-item active">Evacuation Dashboard</li>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-12">
        <div class="callout callout-info mb-3">
            <h5 class="mb-1">Welcome, <?= esc(drms_current_user_display()['name']) ?></h5>
            <p class="mb-0">
                <?php if ($hasAssignment): ?>
                You manage <strong><?= esc($assignedCenter['name']) ?></strong> — register families, track check-ins, and update shelter capacity.
                <?php else: ?>
                No shelter is assigned to your account yet. Ask an administrator to set your <strong>Assigned evacuation center</strong> in User Management.
                <?php endif ?>
            </p>
        </div>
    </div>
</div>

<?php if ($hasAssignment): ?>
<div class="row">
    <div class="col-lg-8">
        <div class="card card-outline card-primary mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="fas fa-home mr-1"></i> Your assigned center</h3>
                <div class="card-tools">
                    <a href="<?= base_url('admin/evacuation-centers') ?>" class="btn btn-sm btn-primary">Open evacuation module</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-7">
                        <h5 class="font-weight-bold mb-1"><?= esc($assignedCenter['name']) ?></h5>
                        <p class="text-muted small mb-2"><?= esc($assignedCenter['address'] ?: 'No address on file') ?></p>
                        <p class="mb-1 small"><strong>Status:</strong> <span class="badge badge-<?= ($assignedCenter['status'] ?? '') === 'open' ? 'success' : 'secondary' ?>"><?= esc(ucfirst((string) ($assignedCenter['status'] ?? 'unknown'))) ?></span></p>
                        <p class="mb-0 small"><strong>Contact:</strong> <?= esc($assignedCenter['contact_person'] ?: '—') ?> <?= $assignedCenter['contact_phone'] ? ' · ' . esc($assignedCenter['contact_phone']) : '' ?></p>
                    </div>
                    <div class="col-md-5">
                        <p class="mb-1"><strong><?= (int) ($assignedCenter['occupancy'] ?? 0) ?></strong> / <?= (int) ($assignedCenter['capacity'] ?? 0) ?> persons</p>
                        <div class="progress mb-2" style="height:10px">
                            <div class="progress-bar bg-<?= ($assignedCenter['occupancy_pct'] ?? 0) >= 90 ? 'danger' : (($assignedCenter['occupancy_pct'] ?? 0) >= 70 ? 'warning' : 'success') ?>"
                                 style="width:<?= min(100, (int) ($assignedCenter['occupancy_pct'] ?? 0)) ?>%"></div>
                        </div>
                        <p class="small text-muted mb-0"><?= (int) ($assignedCenter['families_registered'] ?? 0) ?> families · <?= (int) ($assignedCenter['medical_needs_count'] ?? 0) ?> medical flags</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="row">
            <div class="col-6 col-lg-12 mb-3 mb-lg-2">
                <div class="small-box bg-success mb-0">
                    <div class="inner">
                        <h3><?= (int) ($stats['families_checked_in'] ?? 0) ?></h3>
                        <p>Checked in</p>
                    </div>
                    <div class="icon"><i class="fas fa-user-check"></i></div>
                </div>
            </div>
            <div class="col-6 col-lg-12">
                <div class="small-box bg-warning mb-0">
                    <div class="inner">
                        <h3><?= (int) ($stats['families_pending'] ?? 0) ?></h3>
                        <p>Pending check-in</p>
                    </div>
                    <div class="icon"><i class="fas fa-clock"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card card-outline card-success">
            <div class="card-header"><h3 class="card-title">Register a family</h3></div>
            <form id="formEvacRegisterFamily" class="card-body">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label>Family head name <span class="text-danger">*</span></label>
                    <input type="text" name="family_head_name" class="form-control" required maxlength="120">
                </div>
                <div class="form-group">
                    <label>Members count</label>
                    <input type="number" name="members_count" class="form-control" min="1" value="1" required>
                </div>
                <div class="form-group">
                    <label>Contact phone</label>
                    <input type="text" name="contact_phone" class="form-control" maxlength="32">
                </div>
                <div class="form-group">
                    <label>Medical needs</label>
                    <textarea name="medical_needs" class="form-control" rows="2" placeholder="Optional — pregnancy, PWD, medicines…"></textarea>
                </div>
                <button type="submit" class="btn btn-success btn-block">Register family</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Families at this center</h3>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRefreshEvacFamilies">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-striped table-hover mb-0" id="tblEvacFamilies">
                    <thead>
                        <tr>
                            <th>Family head</th>
                            <th>Members</th>
                            <th>Token</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($families === []): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No families registered yet. Use the form to register walk-ins or pre-registrations.</td></tr>
                        <?php else: ?>
                        <?php foreach ($families as $family):
                            $status = (string) ($family['check_in_status'] ?? 'pending');
                            $badge = match ($status) {
                                'checked_in'  => 'success',
                                'checked_out' => 'secondary',
                                default       => 'warning',
                            };
                            ?>
                        <tr data-family-id="<?= (int) ($family['id'] ?? 0) ?>">
                            <td>
                                <strong><?= esc($family['family_head_name'] ?? '') ?></strong>
                                <?php if (! empty($family['has_medical'])): ?>
                                <span class="badge badge-danger ml-1">Medical</span>
                                <?php endif ?>
                                <?php if (! empty($family['contact_phone'])): ?>
                                <br><small class="text-muted"><?= esc($family['contact_phone']) ?></small>
                                <?php endif ?>
                            </td>
                            <td><?= (int) ($family['members_count'] ?? 0) ?></td>
                            <td><code class="small"><?= esc($family['family_qr_token'] ?? '') ?></code></td>
                            <td><span class="badge badge-<?= $badge ?>"><?= esc($family['check_in_label'] ?? '') ?></span></td>
                            <td>
                                <?php if ($status !== 'checked_out'): ?>
                                <button type="button" class="btn btn-xs btn-warning btn-evac-checkout" data-id="<?= (int) ($family['id'] ?? 0) ?>">Check out</button>
                                <?php else: ?>
                                <span class="text-muted small">—</span>
                                <?php endif ?>
                            </td>
                        </tr>
                        <?php endforeach ?>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="row">
    <div class="col-md-4">
        <div class="small-box bg-info">
            <div class="inner"><h3><?= (int) ($stats['total_centers'] ?? 0) ?></h3><p>Centers (municipality)</p></div>
            <div class="icon"><i class="fas fa-home"></i></div>
        </div>
    </div>
    <div class="col-12">
        <div class="alert alert-warning">Assign a shelter to this account in <strong>Admin → User Management</strong> → edit <code>evac_manager</code> → <em>Assigned evacuation center</em>.</div>
    </div>
</div>
<?php endif ?>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Quick links</h3></div>
            <div class="card-body">
                <a href="<?= base_url('admin/evacuation-centers') ?>" class="btn btn-drms-primary btn-block mb-2">Evacuation centers module</a>
                <a href="<?= base_url('admin/reports') ?>" class="btn btn-outline-secondary btn-block mb-2">Reports &amp; analytics</a>
                <a href="<?= base_url('evac-centers') ?>" class="btn btn-outline-info btn-block" target="_blank">Public shelter finder</a>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?php if ($hasAssignment && $centerId > 0): ?>
<?= $this->section('scripts') ?>
<script>
$(function () {
    var centerId = <?= (int) $centerId ?>;
    var familiesUrl = <?= json_encode(base_url('admin/evacuation-centers/families/' . $centerId)) ?>;
    var registerUrl = <?= json_encode(base_url('admin/evacuation-centers/families/' . $centerId)) ?>;
    var checkoutBase = <?= json_encode(base_url('admin/evacuation-centers/families/' . $centerId . '/checkout/')) ?>;

    $('#formEvacRegisterFamily').on('submit', function (e) {
        e.preventDefault();
        DRMS.postForm(registerUrl, this, function (res) {
            DRMS.toast('Registered', res.message || 'Family registered', 'success');
            window.location.reload();
        });
    });

    $('#btnRefreshEvacFamilies').on('click', function () {
        window.location.reload();
    });

    $('#tblEvacFamilies').on('click', '.btn-evac-checkout', function () {
        var familyId = $(this).data('id');
        DRMS.confirm('Check out family?', 'Occupancy counts will be updated for your center.', function () {
            DRMS.postData(checkoutBase + familyId, {}, function (res) {
                DRMS.toast('Checked out', res.message || 'Family checked out', 'success');
                window.location.reload();
            });
        });
    });
});
</script>
<?= $this->endSection() ?>
<?php endif ?>
