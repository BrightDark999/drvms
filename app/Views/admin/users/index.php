<?= $this->extend('layouts/admin') ?>

<?= $this->section('breadcrumb') ?>
<li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Home</a></li>
<li class="breadcrumb-item active">User Management</li>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Site users — registration approval</h3>
        <div class="card-tools d-flex flex-wrap align-items-center">
            <button type="button" class="btn btn-sm btn-primary mr-2 drms-icon-action" id="btnAddUser" title="Add user" aria-label="Add user">
                <i class="fas fa-plus" aria-hidden="true"></i>
            </button>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary btn-filter-status active" data-filter="">All</button>
                <button type="button" class="btn btn-outline-warning btn-filter-status" data-filter="pending">Pending</button>
                <button type="button" class="btn btn-outline-success btn-filter-status" data-filter="approved">Approved</button>
                <button type="button" class="btn btn-outline-secondary btn-filter-status" data-filter="inactive">Inactive</button>
                <button type="button" class="btn btn-outline-dark btn-filter-status" data-filter="disabled">Disabled</button>
                <button type="button" class="btn btn-outline-danger btn-filter-status" data-filter="rejected">Rejected</button>
            </div>
        </div>
    </div>
    <div class="card-body table-responsive">
        <p class="text-muted small">New registrations join as <strong>residents</strong> and stay <strong>pending</strong> until you verify their ID and approve them. After approval, change their <strong>Role</strong> to Volunteer, MDRRMO / LGU Staff, or other personnel as needed. <strong>Delete</strong> performs a soft delete.</p>
        <table id="tblUsers" class="table table-bordered table-striped w-100">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="formUser" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" id="crudRecordId" value="">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalTitle">Edit user</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="userIdPreview" class="alert alert-light d-none mb-3">
                    <strong>ID document:</strong>
                    <a href="#" id="userIdLink" target="_blank" class="ml-2">View uploaded ID</a>
                    <span id="userIdStatus" class="badge badge-secondary ml-2"></span>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Full name</label>
                        <input type="text" name="full_name" class="form-control">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>Password <small class="text-muted" id="userPasswordHint">(required for new users)</small></label>
                    <input type="password" name="password" class="form-control" minlength="8">
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Role</label>
                        <select name="group" class="form-control">
                            <?php foreach ($groups as $key => $meta): ?>
                                <?php if (in_array($key, ['admin'], true)) {
                                    continue;
                                } ?>
                                <option value="<?= esc($key) ?>"><?= esc($meta['title'] ?? $key) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Account status</label>
                        <select name="account_status" class="form-control">
                            <?php foreach ($accountStatuses as $st): ?>
                                <option value="<?= esc($st) ?>"><?= esc(ucfirst($st)) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Status message <small class="text-muted">(optional note for user)</small></label>
                    <input type="text" name="status_message" class="form-control">
                </div>
                <div class="form-group">
                    <label>Barangay (optional)</label>
                    <select name="barangay_id" class="form-control">
                        <option value="">— None —</option>
                        <?php foreach ($barangays as $b): ?>
                            <option value="<?= (int) $b['id'] ?>"><?= esc($b['name'] ?? '') ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="form-group" id="userEvacCenterGroup" style="display:none">
                    <label>Assigned evacuation center</label>
                    <select name="assigned_evacuation_center_id" class="form-control">
                        <option value="">— Select shelter —</option>
                        <?php foreach ($evacCenters as $ec): ?>
                            <option value="<?= (int) $ec['id'] ?>"><?= esc($ec['name'] ?? '') ?></option>
                        <?php endforeach ?>
                    </select>
                    <small class="text-muted">Evacuation managers only see and manage families at this center.</small>
                </div>

                <div id="userGroupRosterSection" class="p-3 bg-light rounded border mb-3 d-none">
                    <h6 class="font-weight-bold text-danger mb-2"><i class="fas fa-users mr-1"></i> Volunteer Group Roster</h6>
                    <div class="small mb-1"><strong>Organization:</strong> <span id="userGroupOrgName"></span></div>
                    <div class="small mb-2"><strong>Head:</strong> <span id="userGroupHeadName"></span> (<span id="userGroupHeadContact"></span>)</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered bg-white mb-0" id="tblUserGroupMembers">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 30px;">#</th>
                                    <th>Member Name</th>
                                    <th>Contact</th>
                                    <th>Role / Skills</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="tblUserGroupMembersBody"></tbody>
                        </table>
                    </div>
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
    var statusFilter = '';
    var isCreate = false;
    var allUsers = [];

    function statusBadge(st) {
        var c = { approved: 'success', pending: 'warning', rejected: 'danger' };
        return '<span class="badge badge-' + (c[st] || 'secondary') + '">' + (st || '') + '</span>';
    }
    function idBadge(st) {
        var c = { approved: 'success', pending: 'warning', rejected: 'danger', none: 'secondary' };
        return '<span class="badge badge-' + (c[st] || 'secondary') + '">' + (st || 'none') + '</span>';
    }
    function userActions(row) {
        var html = '<div class="btn-group btn-group-xs">';
        if (row.account_status === 'pending') {
            html += '<button type="button" class="btn btn-success btn-user-approve" data-id="' + row.id + '"><i class="fas fa-check"></i></button>'
                + '<button type="button" class="btn btn-warning btn-user-reject" data-id="' + row.id + '"><i class="fas fa-times"></i></button>';
        }
        if (row.id_verification_status === 'pending') {
            html += '<button type="button" class="btn btn-outline-success btn-user-verify-id" data-id="' + row.id + '"><i class="fas fa-id-card"></i></button>';
        }
        html += '<button type="button" class="btn btn-info btn-drms-edit" data-id="' + row.id + '"><i class="fas fa-edit"></i></button>'
            + '<button type="button" class="btn btn-danger btn-user-delete" data-id="' + row.id + '"><i class="fas fa-trash"></i></button></div>';
        return html;
    }
    function filteredUsers() {
        if (!statusFilter) {
            return allUsers;
        }
        return allUsers.filter(function (u) { return u.account_status === statusFilter; });
    }
    function reloadTable() {
        if ($.fn.DataTable.isDataTable('#tblUsers')) {
            $('#tblUsers').DataTable().destroy();
        }
        $('#tblUsers').DataTable({
            data: filteredUsers(),
            columns: [
                { data: 'username' },
                { data: 'email' },
                { data: 'role' },
                { data: 'account_status', render: function (s) { return statusBadge(s); } },
                { data: 'created_at', defaultContent: '—' },
                { data: null, orderable: false, searchable: false, render: function (row) { return userActions(row); } }
            ],
            order: [[4, 'desc']],
            pageLength: 25
        });
    }
    function loadUsers() {
        DRMS.apiGet('/users?limit=100').then(function (res) {
            allUsers = res.data || [];
            reloadTable();
        }).catch(function (err) {
            DRMS.toast('Error', err.message, 'error');
        });
    }

    loadUsers();

    function resetUserForm() {
        isCreate = true;
        $('#crudRecordId').val('');
        $('#userModalTitle').text('Add user');
        $('#userPasswordHint').text('(required for new users)');
        $('input[name=password]').prop('required', true);
        $('#userIdPreview').addClass('d-none');
        $('#formUser')[0].reset();
        var csrf = DRMS.csrf();
        if (csrf.hash) {
            $('#formUser').find('input[name="' + csrf.name + '"]').val(csrf.hash);
        }
    }

    function toggleEvacCenterField() {
        var isEvac = $('select[name=group]').val() === 'evac_manager';
        $('#userEvacCenterGroup').toggle(isEvac);
    }

    function loadUserForm(d) {
        isCreate = false;
        $('#userModalTitle').text('Edit user');
        $('#userPasswordHint').text('(leave blank to keep)');
        $('input[name=password]').prop('required', false);
        $('input[name=username]').val(d.username || '');
        $('input[name=email]').val(d.email || '');
        $('input[name=full_name]').val(d.full_name || '');
        $('input[name=phone]').val(d.phone || '');
        $('textarea[name=address]').val(d.address || '');
        $('input[name=password]').val('');
        $('select[name=group]').val(d.group || d.role || 'resident');
        $('select[name=account_status]').val(d.account_status || 'pending');
        $('input[name=status_message]').val(d.status_message || '');
        $('select[name=barangay_id]').val(d.barangay_id || '');
        $('select[name=assigned_evacuation_center_id]').val(d.assigned_evacuation_center_id || '');
        toggleEvacCenterField();

        if (d.id_document_path) {
            $('#userIdPreview').removeClass('d-none');
            $('#userIdLink').attr('href', <?= json_encode(base_url('')) ?> + d.id_document_path.replace(/^\//, ''));
            $('#userIdStatus').text('ID: ' + (d.id_verification_status || 'none')).removeClass('badge-success badge-warning badge-danger')
                .addClass(d.id_verification_status === 'approved' ? 'badge-success' : (d.id_verification_status === 'pending' ? 'badge-warning' : 'badge-secondary'));
        } else {
            $('#userIdPreview').addClass('d-none');
        }

        if (d.volunteer_group) {
            $('#userGroupRosterSection').removeClass('d-none');
            $('#userGroupOrgName').text(d.volunteer_group.organization_name || '—');
            $('#userGroupHeadName').text(d.volunteer_group.head_name || '—');
            $('#userGroupHeadContact').text(d.volunteer_group.head_contact || '—');
            var mbHtml = '';
            (d.volunteer_group_members || []).forEach(function (m, idx) {
                var stBadge = m.status === 'approved' ? 'badge-success' : 'badge-warning';
                mbHtml += '<tr><td>' + (idx + 1) + '</td><td><strong>' + (m.full_name || '') + '</strong></td><td>' + (m.contact_number || '—') + '</td><td>' + (m.role_or_skills || '—') + '</td><td><span class="badge ' + stBadge + '">' + (m.status || 'pending') + '</span></td></tr>';
            });
            $('#tblUserGroupMembersBody').html(mbHtml);
        } else {
            $('#userGroupRosterSection').addClass('d-none');
        }
    }

    $('#btnAddUser').on('click', function () {
        resetUserForm();
        toggleEvacCenterField();
        $('#userModal').modal('show');
    });

    $('select[name=group]').on('change', toggleEvacCenterField);

    $('.btn-filter-status').on('click', function () {
        $('.btn-filter-status').removeClass('active');
        $(this).addClass('active');
        statusFilter = $(this).data('filter') || '';
        reloadTable();
    });

    $(document).on('click', '.btn-user-approve', function () {
        var id = $(this).data('id');
        DRMS.confirm('Approve user?', 'They will be able to log in and use DRMS.', function () {
            DRMS.apiPost('/users/' + id + '/approve', {}).then(function (res) {
                DRMS.toast('Approved', res.message, 'success');
                loadUsers();
            }).catch(function (err) { DRMS.toast('Error', err.message, 'error'); });
        });
    });

    $(document).on('click', '.btn-user-reject', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Reject registration?',
            input: 'textarea',
            inputPlaceholder: 'Optional reason for the user…',
            showCancelButton: true,
            confirmButtonText: 'Reject'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            DRMS.apiPost('/users/' + id + '/reject', { message: result.value || '' }).then(function (res) {
                DRMS.toast('Rejected', res.message, 'warning');
                loadUsers();
            }).catch(function (err) { DRMS.toast('Error', err.message, 'error'); });
        });
    });

    $(document).on('click', '.btn-user-verify-id', function () {
        var id = $(this).data('id');
        DRMS.confirm('Verify ID?', 'Mark this user ID document as verified.', function () {
            DRMS.apiPost('/users/' + id + '/verify-id', {}).then(function (res) {
                DRMS.toast('Verified', res.message, 'success');
                loadUsers();
            }).catch(function (err) { DRMS.toast('Error', err.message, 'error'); });
        });
    });

    $(document).on('click', '.btn-user-delete', function () {
        var id = $(this).data('id');
        DRMS.confirm('Delete Information?', 'This Data will be Deleted Permanently.', function () {
            DRMS.apiDelete('/users/' + id).then(function (res) {
                DRMS.toast('Deactivated', res.message, 'success');
                loadUsers();
            }).catch(function (err) { DRMS.toast('Error', err.message, 'error'); });
        });
    });

    $('#tblUsers').on('click', '.btn-drms-edit', function () {
        var id = $(this).data('id');
        DRMS.apiGet('/users/' + id).then(function (res) {
            $('#crudRecordId').val(id);
            loadUserForm(DRMS.apiUnwrap(res));
            $('#userModal').modal('show');
        }).catch(function (err) {
            DRMS.toast('Error', err.message, 'error');
        });
    });

    $('#formUser').on('submit', function (e) {
        e.preventDefault();
        var id = $('#crudRecordId').val();
        var payload = DRMS.formToObject(this);
        if (!id && !payload.password) {
            DRMS.toast('Error', 'Password is required for new users.', 'error');
            return;
        }
        if (id && !payload.password) {
            delete payload.password;
        }
        DRMS.apiFormSubmit({
            form: this,
            seqKey: '#formUser',
            request: function () {
                return id ? DRMS.apiPut('/users/' + id, payload) : DRMS.apiPost('/users', payload);
            },
            onSuccess: function (res) {
                $('#userModal').modal('hide');
                DRMS.toast('Saved', res.message, 'success');
                loadUsers();
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
