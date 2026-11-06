<?= $this->extend('layouts/admin') ?>

<?= $this->section('breadcrumb') ?>
<li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Home</a></li>
<li class="breadcrumb-item active">Donations</li>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Donations &amp; assistance</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-sm btn-primary drms-icon-action" id="btnAddDonation" title="Record donation" aria-label="Record donation">
                <i class="fas fa-plus" aria-hidden="true"></i>
            </button>
        </div>
    </div>
    <div class="card-body table-responsive">
        <table id="tblDonations" class="table table-bordered table-striped w-100">
            <thead>
                <tr><th>Donor</th><th>Type</th><th>Amount</th><th>Received</th><th>Actions</th></tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="donationModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="formDonation" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" id="crudRecordId" value="">
            <div class="modal-header">
                <h5 class="modal-title" id="donationModalTitle">Record donation</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Donor name <span class="text-danger">*</span></label>
                    <input type="text" name="donor_name" class="form-control" list="donorList" required>
                    <datalist id="donorList">
                        <?php foreach ($donors as $d): ?>
                            <option value="<?= esc($d['name']) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select name="donation_type" class="form-control">
                        <option value="cash">Cash</option>
                        <option value="in_kind">In-kind</option>
                        <option value="service">Service</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>Amount (PHP)</label>
                    <input type="number" step="0.01" name="amount_peso" class="form-control">
                </div>
                <div class="form-group">
                    <label>Received at</label>
                    <input type="datetime-local" name="received_at" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(function () {
    var baseUrl = <?= json_encode(rtrim(base_url('admin/donations'), '/')) ?>;
    var table = DRMS.serverDataTable('#tblDonations', <?= json_encode($dataUrl) ?>, [
        { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 },
        { data: 4, orderable: false, searchable: false }
    ]);

    DRMS.bindCrud({
        tableSelector: '#tblDonations',
        dataTable: table,
        form: '#formDonation',
        modal: '#donationModal',
        addBtn: '#btnAddDonation',
        baseUrl: baseUrl,
        idField: '#crudRecordId',
        onReset: function () { $('#donationModalTitle').text('Record donation'); },
        onLoad: function (d) {
            $('#donationModalTitle').text('Edit donation');
            $('input[name=donor_name]').val(d.donor_name || '');
            $('select[name=donation_type]').val(d.donation_type || 'cash');
            $('textarea[name=description]').val(d.description || '');
            $('input[name=amount_peso]').val(d.amount_peso || '');
            if (d.received_at) {
                $('input[name=received_at]').val(String(d.received_at).replace(' ', 'T').slice(0, 16));
            }
        }
    });
});
</script>
<?= $this->endSection() ?>
