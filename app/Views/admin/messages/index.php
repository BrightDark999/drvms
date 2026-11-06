<?= $this->extend('layouts/admin') ?>

<?= $this->section('breadcrumb') ?>
<li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Home</a></li>
<li class="breadcrumb-item active">Messages</li>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">In-system chat</h3></div>
            <div class="card-body p-0" style="max-height:400px;overflow-y:auto">
                <ul class="list-group list-group-flush">
                    <?php foreach ($inbox as $m): ?>
                        <li class="list-group-item small">
                            <strong>#<?= (int) $m['id'] ?></strong>
                            <?= esc(mb_substr((string) ($m['body'] ?? ''), 0, 80)) ?>
                            <div class="text-muted"><?= esc($m['created_at'] ?? '') ?></div>
                        </li>
                    <?php endforeach ?>
                </ul>
            </div>
        </div>
        <?php if (auth()->user()->inGroup('mdrrmo', 'admin')): ?>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Broadcast announcement</h3></div>
            <div class="card-body">
                <form id="formBroadcast">
                    <?= csrf_field() ?>
                    <textarea name="body" class="form-control mb-2" rows="3" placeholder="Emergency announcement to all staff…" required></textarea>
                    <button type="submit" class="btn btn-danger btn-sm btn-block">Send announcement</button>
                </form>
            </div>
        </div>
        <?php endif ?>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Direct message</h3>
                <div class="card-tools">
                    <select id="selectContact" class="form-control form-control-sm" style="width:auto;display:inline-block">
                        <option value="">— Select contact —</option>
                        <?php foreach ($contacts as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= $peerId === (int) $c['id'] ? 'selected' : '' ?>><?= esc($c['username']) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>
            <div class="card-body" id="threadBox" style="min-height:260px;max-height:360px;overflow-y:auto;background:#f8f9fa">
                <?php if ($peerId < 1): ?>
                    <p class="text-muted mb-0">Select a contact to view the conversation thread.</p>
                <?php else: ?>
                    <?php foreach ($thread as $m): ?>
                        <div class="mb-2 small">
                            <strong><?= (int) ($m['sender_user_id'] ?? 0) === (int) auth()->id() ? 'You' : 'User #' . (int) $m['sender_user_id'] ?>:</strong>
                            <?= esc($m['body'] ?? '') ?>
                        </div>
                    <?php endforeach ?>
                <?php endif ?>
            </div>
            <div class="card-footer">
                <form id="formMessage" class="form-inline w-100">
                    <?= csrf_field() ?>
                    <input type="hidden" name="recipient_user_id" id="recipientUserId" value="<?= $peerId > 0 ? $peerId : '' ?>">
                    <input type="text" name="body" id="messageBody" class="form-control flex-grow-1 mr-2" placeholder="Type a message…" required>
                    <button type="submit" class="btn btn-primary">Send</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(function () {
    $('#selectContact').on('change', function () {
        var id = $(this).val();
        if (id) window.location = '<?= base_url('admin/messages') ?>?user=' + id;
    });
    $('#formMessage').on('submit', function (e) {
        e.preventDefault();
        DRMS.postData('<?= base_url('admin/messages/send') ?>', {
            recipient_user_id: $('#recipientUserId').val(),
            body: $('#messageBody').val()
        }, function () {
            $('#messageBody').val('');
            location.reload();
        });
    });
    $('#formBroadcast').on('submit', function (e) {
        e.preventDefault();
        DRMS.postForm('<?= base_url('admin/messages/broadcast') ?>', $(this), function () {
            DRMS.toast('Sent', 'Announcement broadcast.', 'success');
        });
    });
});
</script>
<?= $this->endSection() ?>
