<?= $this->extend('layouts/admin') ?>

<?= $this->section('breadcrumb') ?>
<li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Home</a></li>
<li class="breadcrumb-item active">Volunteers</li>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if (! empty($skillRequests)): ?>
<div class="card card-outline card-warning mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-check mr-1"></i> Pending skill approvals</h3>
        <span class="badge badge-warning"><?= count($skillRequests) ?></span>
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>Volunteer</th>
                    <th>Current</th>
                    <th>Requested</th>
                    <th>Submitted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($skillRequests as $req): ?>
                <tr>
                    <td><?= esc($req['volunteer_name'] ?? '') ?></td>
                    <td>
                        <?php foreach ($req['previous_skills'] ?? [] as $sk): ?>
                            <span class="badge badge-secondary mr-1"><?= esc($sk) ?></span>
                        <?php endforeach ?>
                        <?php if (empty($req['previous_skills'])): ?><span class="text-muted">—</span><?php endif ?>
                    </td>
                    <td>
                        <?php foreach ($req['requested_skills'] ?? [] as $sk): ?>
                            <span class="badge badge-info mr-1"><?= esc($sk) ?></span>
                        <?php endforeach ?>
                    </td>
                    <td class="small text-muted"><?= esc($req['created_at'] ?? '') ?></td>
                    <td class="text-nowrap">
                        <button type="button" class="btn btn-xs btn-success btn-approve-skills" data-id="<?= (int) ($req['id'] ?? 0) ?>">Approve</button>
                        <button type="button" class="btn btn-xs btn-outline-danger btn-reject-skills" data-id="<?= (int) ($req['id'] ?? 0) ?>">Reject</button>
                    </td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Volunteers</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-sm btn-primary drms-icon-action" id="btnAddVolunteer" title="Add volunteer" aria-label="Add volunteer">
                <i class="fas fa-plus" aria-hidden="true"></i>
            </button>
        </div>
    </div>
    <div class="card-body table-responsive">
        <table id="tblVolunteers" class="table table-bordered table-striped w-100">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Availability</th>
                    <th>Barangay</th>
                    <th>ID verification</th>
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="volunteerModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="formVolunteer" class="modal-content" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" id="crudRecordId" value="">
            <div class="modal-header">
                <h5 class="modal-title" id="volunteerModalTitle">Add volunteer</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Full name <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="form-group">
                    <label>Availability</label>
                    <select name="availability_status" class="form-control">
                        <option value="available">Available</option>
                        <option value="on_duty">On duty</option>
                        <option value="unavailable">Unavailable</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Barangay</label>
                    <select name="barangay_id" class="form-control">
                        <option value="">— Select —</option>
                        <?php foreach ($barangays as $b): ?>
                            <option value="<?= (int) $b['id'] ?>"><?= esc($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Experience (years)</label>
                    <input type="number" name="experience_years" class="form-control" min="0" max="50" value="0">
                </div>
                <div class="form-group">
                    <label>Preferred Shift Duration</label>
                    <select name="preferred_hours" class="form-control">
                        <option value="8">8 hrs</option>
                        <option value="12">12 hrs</option>
                        <option value="24">24 hrs</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Skills</label>
                    <div class="row">
                        <?php foreach ($volunteerSkills as $skill): ?>
                            <div class="col-md-6">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input skill-check" id="skill_<?= esc(md5($skill)) ?>"
                                        name="skills[]" value="<?= esc($skill) ?>">
                                    <label class="custom-control-label" for="skill_<?= esc(md5($skill)) ?>"><?= esc($skill) ?></label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>ID document</label>
                    <input type="file" name="id_document" class="form-control-file" accept="image/jpeg,image/png,image/webp,application/pdf">
                    <small class="text-muted">Upload government ID for verification. Leave blank when editing to keep the current file.</small>
                    <div id="volunteerIdPreview" class="small text-muted mt-1 d-none"></div>
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
    var fileBase = <?= json_encode(rtrim(base_url(), '/')) ?>;

    function idBadge(st) {
        var map = { approved: 'success', pending: 'warning', rejected: 'danger' };
        var lbl = { approved: 'ID verified', pending: 'ID pending', rejected: 'ID rejected' };
        return '<span class="badge badge-' + (map[st] || 'secondary') + '">' + (lbl[st] || 'No ID') + '</span>';
    }
    function volActions(row) {
        var st = row.id_verification_status || '';
        var path = row.id_verification_path || '';
        var html = '<div class="btn-group btn-group-xs">';
        if (path) {
            html += '<a href="' + fileBase + '/' + path.replace(/^\//, '') + '" target="_blank" rel="noopener" class="btn btn-outline-info" title="View ID document"><i class="fas fa-id-card"></i></a>';
        }
        html += '<button type="button" class="btn btn-info btn-api-edit" data-id="' + row.id + '" title="Edit"><i class="fas fa-edit"></i></button>';
        if (st === 'pending') {
            html += '<button type="button" class="btn btn-success btn-verify-id" data-id="' + row.id + '" title="Approve ID"><i class="fas fa-check"></i></button>'
                + '<button type="button" class="btn btn-warning btn-reject-id" data-id="' + row.id + '" title="Reject ID"><i class="fas fa-times"></i></button>';
        }
        html += '<button type="button" class="btn btn-danger btn-api-delete" data-id="' + row.id + '" title="Delete"><i class="fas fa-trash"></i></button></div>';
        return html;
    }

    var table = DRMS.apiDataTable('#tblVolunteers', '/volunteers', [
        { data: 'full_name' },
        { data: 'email' },
        { data: 'phone', defaultContent: '—' },
        { data: 'availability_status', render: function (s) { return (s || '').replace(/_/g, ' '); } },
        { data: 'barangay_name', defaultContent: '—' },
        { data: 'id_verification_status', render: function (s) { return idBadge(s); } },
        { data: null, orderable: false, searchable: false, render: function (row) { return volActions(row); } }
    ], { order: [[0, 'asc']] });

    $(document).on('click', '.btn-verify-id', function () {
        var id = $(this).data('id');
        DRMS.confirm('Approve ID?', 'Mark this volunteer ID as verified.', function () {
            DRMS.apiPost('/volunteers/' + id + '/verify-id', {}).then(function () {
                table.ajax.reload(null, false);
                DRMS.toast('Approved', 'ID verification approved.', 'success');
            }).catch(function (err) { DRMS.toast('Error', err.message, 'error'); });
        });
    });
    $(document).on('click', '.btn-reject-id', function () {
        var id = $(this).data('id');
        DRMS.confirm('Reject ID?', 'Volunteer will need to re-upload.', function () {
            DRMS.apiPost('/volunteers/' + id + '/reject-id', {}).then(function () {
                table.ajax.reload(null, false);
                DRMS.toast('Rejected', 'ID verification rejected.', 'warning');
            }).catch(function (err) { DRMS.toast('Error', err.message, 'error'); });
        });
    });

    DRMS.bindApiCrud({
        resource: '/volunteers',
        tableSelector: '#tblVolunteers',
        dataTable: table,
        form: '#formVolunteer',
        modal: '#volunteerModal',
        addBtn: '#btnAddVolunteer',
        idField: '#crudRecordId',
        mapPayload: function (form) {
            var p = DRMS.formToObject(form);
            if (!p.skills) {
                p.skills = [];
            }
            return p;
        },
        onReset: function () {
            $('#volunteerModalTitle').text('Add volunteer');
            $('.skill-check').prop('checked', false);
            $('#volunteerIdPreview').addClass('d-none').text('');
        },
        onLoad: function (d) {
            $('#volunteerModalTitle').text('Edit volunteer');
            $('input[name=full_name]').val(d.full_name || '');
            $('input[name=email]').val(d.email || '');
            $('input[name=phone]').val(d.phone || '');
            $('select[name=availability_status]').val(d.availability_status || 'available');
            $('input[name=experience_years]').val(d.experience_years || 0);
            $('select[name=preferred_hours]').val(d.preferred_hours || 8);
            $('select[name=barangay_id]').val(d.barangay_id || '');
            $('textarea[name=address]').val(d.address || '');
            $('textarea[name=notes]').val(d.notes || '');
            if (d.id_verification_path) {
                var idUrl = fileBase + '/' + String(d.id_verification_path).replace(/^\//, '');
                $('#volunteerIdPreview').removeClass('d-none').html(
                    'Current ID: <a href="' + idUrl + '" target="_blank" rel="noopener">View document</a> (' + (d.id_verification_status || 'pending') + ')'
                );
            } else {
                $('#volunteerIdPreview').addClass('d-none').text('');
            }
            $('.skill-check').prop('checked', false);
            var skills = (d.skills || []).map(function (s) { return s.skill_name || s; });
            $('.skill-check').each(function () {
                if (skills.indexOf($(this).val()) >= 0) {
                    $(this).prop('checked', true);
                }
            });
        },
        onSaved: function (res, recordId, finish) {
            finish();

            var volunteerId = recordId || (res.data && res.data.id);
            var fileInput = document.querySelector('#formVolunteer input[name=id_document]');
            if (!volunteerId || !fileInput || !fileInput.files || !fileInput.files.length) {
                return;
            }

            var mfd = new FormData();
            mfd.append('id_document', fileInput.files[0]);

            DRMS.apiPost('/volunteers/' + volunteerId + '/id-document', mfd).then(function () {
                DRMS.toast('ID uploaded', 'Document saved — status is now ID pending.', 'success');
                if (table) {
                    table.ajax.reload(null, false);
                }
            }).catch(function (err) {
                DRMS.toast('ID upload failed', err.message || 'Volunteer saved but ID file was not uploaded.', 'warning');
            });
        }
    });

    $(document).on('click', '.btn-approve-skills', function () {
        var id = $(this).data('id');
        DRMS.confirm('Approve skills?', 'The volunteer will immediately see matching incidents for these skills.', function () {
            DRMS.postData('<?= base_url('admin/volunteers/approve-skills') ?>/' + id, {}, function () {
                DRMS.toast('Approved', 'Volunteer skills updated.', 'success');
                window.location.reload();
            });
        });
    });

    $(document).on('click', '.btn-reject-skills', function () {
        var id = $(this).data('id');
        DRMS.confirm('Reject request?', 'The volunteer keeps their current approved skills.', function () {
            DRMS.postData('<?= base_url('admin/volunteers/reject-skills') ?>/' + id, {}, function () {
                DRMS.toast('Rejected', 'Skill request declined.', 'info');
                window.location.reload();
            });
        });
    });
});
</script>
<?= $this->endSection() ?>
