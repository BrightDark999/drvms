<?= $this->extend('layouts/admin') ?>

<?= $this->section('breadcrumb') ?>
<li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Home</a></li>
<li class="breadcrumb-item active">Notifications</li>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Notification queue</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-sm btn-primary drms-icon-action" id="btnProcessNotifications" title="Process pending notifications" aria-label="Process pending notifications">
                <i class="fas fa-paper-plane" aria-hidden="true"></i>
            </button>
        </div>
    </div>
    <p class="text-muted small px-3 pt-2 mb-0">
        Auto-queued when incidents are created/verified or volunteers deployed.
        On WAMP, emails send immediately if <code>drms.notifications.autoSend = true</code> in <code>.env</code>.
        Production: run <code>php spark notifications:process</code> every minute or use web cron URL.
    </p>
    <div class="card-body table-responsive">
        <table id="tblNotify" class="table table-bordered table-striped w-100">
            <thead>
                <tr><th>Channel</th><th>Recipient</th><th>Subject</th><th>Status</th><th>Created</th><th>Actions</th></tr>
            </thead>
        </table>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(function () {
    var baseUrl = <?= json_encode(rtrim(base_url('admin/notifications'), '/')) ?>;
    var table = DRMS.serverDataTable('#tblNotify', <?= json_encode($dataUrl) ?>, [
        { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4 },
        { data: 5, orderable: false, searchable: false }
    ]);
    $('#tblNotify').on('click', '.btn-drms-delete', function () {
        var id = $(this).data('id');
        DRMS.confirm('Cancel notification?', 'Remove this pending item from the queue.', function () {
            DRMS.postData(baseUrl + '/delete/' + id, {}, function (res) {
                DRMS.toast('Cancelled', res.message, 'success');
                if (table) table.ajax.reload(null, false);
            });
        });
    });
    $('#btnProcessNotifications').on('click', function () {
        DRMS.postData('<?= base_url('admin/notifications/process') ?>', {}, function (res) {
            DRMS.toast('Queue', res.message, 'success');
            if (table) table.ajax.reload(null, false);
        });
    });
});
</script>
<?= $this->endSection() ?>
