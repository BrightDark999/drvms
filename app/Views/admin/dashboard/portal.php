<?= $this->extend('layouts/admin') ?>

<?= $this->section('breadcrumb') ?>
<li class="breadcrumb-item active">My Portal</li>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-12">
        <div class="callout callout-info">
            <h5>Welcome, <?= esc(auth()->user()->username ?? 'User') ?></h5>
            <p class="mb-0">This is your limited DRMS portal. Use the menu for modules assigned to your role, or report incidents publicly.</p>
        </div>
    </div>
</div>
<div class="row">
    <?php if (drms_user_can('matching.access', 'volunteers.*', 'admin.*')): ?>
    <div class="col-md-4">
        <div class="small-box bg-info">
            <div class="inner"><h3><i class="fas fa-random"></i></h3><p>Volunteer Dispatch</p></div>
            <a href="<?= base_url('admin/matching') ?>" class="small-box-footer">View assignments <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <?php endif; ?>
    <?php if (drms_user_can('donations.*', 'admin.*')): ?>
    <div class="col-md-4">
        <div class="small-box bg-success">
            <div class="inner"><h3><i class="fas fa-hand-holding-heart"></i></h3><p>Donations</p></div>
            <a href="<?= base_url('admin/donations') ?>" class="small-box-footer">Open module <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <?php endif; ?>
    <div class="col-md-4">
        <div class="small-box bg-danger">
            <div class="inner"><h3><i class="fas fa-bullhorn"></i></h3><p>Report disaster</p></div>
            <a href="<?= base_url('report') ?>" class="small-box-footer" target="_blank">Public form <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Quick links</h3></div>
            <div class="card-body">
                <a href="<?= base_url('profile') ?>" class="btn btn-outline-primary btn-block mb-2">Edit profile &amp; skills</a>
                <a href="<?= base_url('evac-centers') ?>" class="btn btn-outline-secondary btn-block" target="_blank">Find evacuation center</a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Your notifications</h3></div>
            <div class="card-body p-0" id="portalNotifList">
                <p class="text-muted p-3 mb-0">Loading…</p>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(function () {
    $.getJSON('<?= base_url('profile/notifications') ?>', function (res) {
        var $box = $('#portalNotifList').empty();
        var items = res.items || [];
        if (!items.length) {
            $box.append('<p class="text-muted p-3 mb-0">No new notifications.</p>');
            return;
        }
        var $ul = $('<ul class="list-group list-group-flush"></ul>');
        items.forEach(function (n) {
            $ul.append('<li class="list-group-item"><strong>' + (n.title || '') + '</strong><br><small>' + (n.body || '') + '</small></li>');
        });
        $box.append($ul);
    });
});
</script>
<?= $this->endSection() ?>
