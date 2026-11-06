<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('title') ?><?= lang('Auth.register') ?><?= $this->endSection() ?>

<?= $this->section('main') ?>

<style>
.reg-type-choice-card {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 1.75rem 1.25rem;
    text-align: center;
    background: #ffffff;
    cursor: pointer;
    transition: all 0.25s ease-in-out;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.04);
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.reg-type-choice-card:hover {
    border-color: #c62828;
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(198, 40, 40, 0.12);
}
.reg-type-choice-card .icon-circle {
    width: 72px;
    height: 72px;
    margin: 0 auto 1.25rem;
    background: #fdeaea;
    color: #c62828;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.9rem;
    transition: all 0.25s ease;
}
.reg-type-choice-card:hover .icon-circle {
    background: #c62828;
    color: #ffffff;
    transform: scale(1.06);
}
.reg-type-choice-card h4 {
    font-size: 1.15rem;
    font-weight: 700;
    color: #212529;
    margin-bottom: 0.5rem;
}
.reg-type-choice-card p {
    font-size: 0.88rem;
    color: #6c757d;
    line-height: 1.45;
    margin-bottom: 1.25rem;
}
</style>

<?php
$hasInitialType = old('registration_type') !== null || session('errors') !== null || session('error') !== null;
$selectedType = old('registration_type', 'individual');
?>

<div class="drms-login-card" style="max-width: <?= $selectedType === 'group' ? '820px' : '560px' ?>; transition: max-width 0.3s ease;" id="registerCard">
    <div class="drms-login-card-header">
        <h2>Volunteer Registration</h2>
        <p>Sign up to support disaster risk reduction & response in Baras, Rizal</p>
    </div>
    <div class="drms-login-card-body">

        <?php if (session('error') !== null) : ?>
            <div class="alert alert-danger" role="alert"><?= esc(session('error')) ?></div>
        <?php elseif (session('errors') !== null) : ?>
            <div class="alert alert-danger" role="alert">
                <?php if (is_array(session('errors'))) : ?>
                    <?php foreach (session('errors') as $error) : ?>
                        <?= esc($error) ?><br>
                    <?php endforeach ?>
                <?php else : ?>
                    <?= esc(session('errors')) ?>
                <?php endif ?>
            </div>
        <?php endif ?>

        <!-- STEP 1: INITIAL REGISTRATION TYPE SELECTOR -->
        <div id="stepChoice" class="<?= $hasInitialType ? 'd-none' : '' ?>">
            <div class="text-center mb-4">
                <h3 class="h5 font-weight-bold text-dark mb-1">Select Registration Option</h3>
                <p class="text-muted small">Please choose how you would like to register with DRMS Baras:</p>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="reg-type-choice-card" onclick="chooseRegType('individual')">
                        <div>
                            <div class="icon-circle">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <h4>Individual Volunteer</h4>
                            <span class="badge badge-danger mb-2">Single Account</span>
                            <p>Register as a solo volunteer to receive emergency dispatches, participate in relief operations, or assist in evacuation centers.</p>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm btn-block font-weight-bold">
                            Register as Individual <i class="fas fa-arrow-right ml-1"></i>
                        </button>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="reg-type-choice-card" onclick="chooseRegType('group')">
                        <div>
                            <div class="icon-circle">
                                <i class="fas fa-users-cog"></i>
                            </div>
                            <h4>Group / Organization</h4>
                            <span class="badge badge-danger mb-2">Min. 7 Members</span>
                            <p>Register an entire organization, NGO, civic club, or rescue brigade with a designated team leader and volunteer roster.</p>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm btn-block font-weight-bold">
                            Register as Group <i class="fas fa-arrow-right ml-1"></i>
                        </button>
                    </div>
                </div>
            </div>

            <p class="drms-login-footer-links mt-4 mb-0 text-center">
                <?= lang('Auth.haveAccount') ?> <a href="<?= url_to('login') ?>"><?= lang('Auth.login') ?></a>
            </p>
        </div>

        <!-- STEP 2: REGISTRATION FORM -->
        <div id="stepForm" class="<?= $hasInitialType ? '' : 'd-none' ?>">
            <form action="<?= url_to('register') ?>" method="post" enctype="multipart/form-data" id="registerForm">
                <?= csrf_field() ?>

                <input type="hidden" name="registration_type" id="inputRegType" value="<?= esc($selectedType) ?>">

                <!-- Selected Type Banner & Switch Button -->
                <div class="d-flex justify-content-between align-items-center p-2 mb-3 bg-light rounded border">
                    <div>
                        <small class="text-muted d-block">Registering as:</small>
                        <strong class="text-danger" id="displayTypeName">
                            <i class="fas <?= $selectedType === 'group' ? 'fa-users' : 'fa-user' ?> mr-1"></i>
                            <?= $selectedType === 'group' ? 'Group / Organization Volunteer' : 'Individual Volunteer' ?>
                        </strong>
                    </div>
                    <button type="button" class="btn btn-xs btn-outline-secondary" onclick="backToChoice()">
                        <i class="fas fa-exchange-alt mr-1"></i> Change Option
                    </button>
                </div>

                <div class="alert alert-light border small mb-3">
                    <i class="fas fa-info-circle text-danger mr-1"></i> All new accounts default to <strong>PENDING</strong>. MDRRMO will verify your credentials before authorizing access.
                </div>

                <!-- GROUP ONLY SECTION -->
                <div id="groupSection" class="<?= $selectedType === 'group' ? '' : 'd-none' ?>">
                    <div class="p-3 bg-light rounded border mb-3">
                        <h5 class="text-danger font-weight-bold mb-3"><i class="fas fa-building mr-1"></i> Organization Details</h5>
                        
                        <div class="form-group">
                            <label for="regOrgName">Organization / Group Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="regOrgName" name="organization_name" value="<?= old('organization_name') ?>" placeholder="e.g. Baras Volunteer Rescue Squad">
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="regHeadName">Head / Team Leader Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="regHeadName" name="head_name" value="<?= old('head_name') ?>" placeholder="Full Name of Group Leader">
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="regHeadContact">Head Contact Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="regHeadContact" name="head_contact" value="<?= old('head_contact') ?>" placeholder="09xxxxxxxxx">
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label for="regGroupDesc">Organization Focus / Specialization</label>
                            <textarea class="form-control" id="regGroupDesc" name="group_description" rows="2" placeholder="e.g. First aid, search and rescue, relief distribution, transport"><?= old('group_description') ?></textarea>
                        </div>
                    </div>

                    <!-- Members Roster (Min 7) -->
                    <div class="p-3 bg-light rounded border mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="text-danger font-weight-bold mb-0"><i class="fas fa-user-friends mr-1"></i> Group Roster Members <span class="badge badge-danger">Min. 7 members</span></h5>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="addMemberRow()">
                                <i class="fas fa-plus mr-1"></i> Add Member
                            </button>
                        </div>
                        <p class="small text-muted mb-3">Provide the full names and details of at least 7 roster members for your group.</p>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered bg-white mb-2" id="membersTable">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 35px;">#</th>
                                        <th>Full Name <span class="text-danger">*</span></th>
                                        <th>Contact Number</th>
                                        <th>Role / Skills</th>
                                        <th style="width: 40px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="membersTableBody">
                                    <?php for ($i = 0; $i < 7; $i++): ?>
                                    <tr>
                                        <td class="text-center align-middle row-index"><?= $i + 1 ?></td>
                                        <td>
                                            <input type="text" name="members[<?= $i ?>][full_name]" class="form-control form-control-sm member-name" placeholder="Member <?= $i + 1 ?> Full Name" value="<?= old("members.{$i}.full_name") ?>">
                                        </td>
                                        <td>
                                            <input type="text" name="members[<?= $i ?>][contact_number]" class="form-control form-control-sm" placeholder="09xxxxxxxxx" value="<?= old("members.{$i}.contact_number") ?>">
                                        </td>
                                        <td>
                                            <input type="text" name="members[<?= $i ?>][role_or_skills]" class="form-control form-control-sm" placeholder="e.g. Medical / Rescue" value="<?= old("members.{$i}.role_or_skills") ?>">
                                        </td>
                                        <td class="text-center align-middle">
                                            <button type="button" class="btn btn-sm text-danger p-0 btn-remove-member" onclick="removeMemberRow(this)" title="Remove member">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted" id="memberCountNotice">Current rows: 7. Minimum 7 required.</small>
                    </div>
                </div>

                <!-- ACCOUNT & PERSONAL CREDENTIALS -->
                <div class="form-group">
                    <label for="regFullName" id="lblFullName"><?= $selectedType === 'group' ? 'Account Representative / Head Full Name' : 'Full Legal Name' ?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="regFullName" name="full_name" value="<?= old('full_name') ?>" required placeholder="Your full legal name">
                </div>

                <div class="form-group">
                    <label for="regEmail"><?= lang('Auth.email') ?> <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="regEmail" name="email" value="<?= old('email') ?>" required autocomplete="email" placeholder="you@example.com">
                    <small class="text-muted">You will sign in using this email address.</small>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="regPhone">Mobile Phone</label>
                        <input type="text" class="form-control" id="regPhone" name="phone" value="<?= old('phone') ?>" placeholder="09xxxxxxxxx">
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="regBarangay">Barangay</label>
                        <select name="barangay_id" id="regBarangay" class="form-control">
                            <option value="">— Select barangay —</option>
                            <?php foreach (model(\App\Models\BarangayModel::class)->orderBy('name', 'ASC')->findAll() as $b): ?>
                                <option value="<?= (int) $b['id'] ?>" <?= (string) old('barangay_id') === (string) $b['id'] ? 'selected' : '' ?>><?= esc($b['name']) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="regAddress">Address / Location</label>
                    <textarea class="form-control" id="regAddress" name="address" rows="2" placeholder="Street, Sitio, or Landmark"><?= old('address') ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="regPassword"><?= lang('Auth.password') ?> <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="regPassword" name="password" required minlength="8" autocomplete="new-password" placeholder="••••••••">
                        <small class="text-muted">Min 8 characters.</small>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="regPasswordConfirm"><?= lang('Auth.passwordConfirm') ?> <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="regPasswordConfirm" name="password_confirm" required minlength="8" autocomplete="new-password" placeholder="••••••••">
                    </div>
                </div>

                <div class="form-group">
                    <label for="regIdDoc">Government / Valid ID Document <span class="text-danger">*</span></label>
                    <input type="file" class="form-control-file" id="regIdDoc" name="id_document" accept="image/jpeg,image/png,image/webp,application/pdf" required>
                    <small class="text-muted">Upload a clear photo or scan of your valid ID (or Head ID for organizations) for verification.</small>
                </div>

                <!-- CAPTCHA & Honeypot Section -->
                <?= view('partials/security/captcha_honeypot') ?>

                <button type="submit" class="btn btn-drms-login btn-block font-weight-bold" id="btnSubmitRegister">
                    <i class="fas fa-user-check mr-1"></i> Submit Registration
                </button>

                <p class="drms-login-footer-links mt-3 mb-0 text-center">
                    <?= lang('Auth.haveAccount') ?> <a href="<?= url_to('login') ?>"><?= lang('Auth.login') ?></a>
                </p>
            </form>
        </div>

    </div>
</div>

<script>
function chooseRegType(type) {
    document.getElementById('inputRegType').value = type;
    document.getElementById('stepChoice').classList.add('d-none');
    document.getElementById('stepForm').classList.remove('d-none');
    applyRegTypeDisplay(type);
}

function backToChoice() {
    document.getElementById('stepForm').classList.add('d-none');
    document.getElementById('stepChoice').classList.remove('d-none');
    document.getElementById('registerCard').style.maxWidth = '560px';
}

function applyRegTypeDisplay(type) {
    var card = document.getElementById('registerCard');
    var groupSec = document.getElementById('groupSection');
    var displayTypeName = document.getElementById('displayTypeName');
    var lblFullName = document.getElementById('lblFullName');
    var orgInput = document.getElementById('regOrgName');
    var headInput = document.getElementById('regHeadName');
    var headContact = document.getElementById('regHeadContact');

    if (type === 'group') {
        card.style.maxWidth = '820px';
        groupSec.classList.remove('d-none');
        displayTypeName.innerHTML = '<i class="fas fa-users mr-1"></i> Group / Organization Volunteer';
        lblFullName.innerHTML = 'Account Representative / Head Full Name <span class="text-danger">*</span>';
        if (orgInput) orgInput.setAttribute('required', 'required');
        if (headInput) headInput.setAttribute('required', 'required');
        if (headContact) headContact.setAttribute('required', 'required');
    } else {
        card.style.maxWidth = '560px';
        groupSec.classList.add('d-none');
        displayTypeName.innerHTML = '<i class="fas fa-user mr-1"></i> Individual Volunteer';
        lblFullName.innerHTML = 'Full Legal Name <span class="text-danger">*</span>';
        if (orgInput) orgInput.removeAttribute('required');
        if (headInput) headInput.removeAttribute('required');
        if (headContact) headContact.removeAttribute('required');
    }
}

function updateRowIndices() {
    var rows = document.querySelectorAll('#membersTableBody tr');
    rows.forEach(function(r, idx) {
        var badge = r.querySelector('.row-index');
        if (badge) badge.textContent = idx + 1;
        var inputs = r.querySelectorAll('input');
        inputs.forEach(function(inp) {
            var name = inp.getAttribute('name');
            if (name) {
                inp.setAttribute('name', name.replace(/members\[\d+\]/, 'members[' + idx + ']'));
            }
        });
    });
    var notice = document.getElementById('memberCountNotice');
    if (notice) {
        notice.textContent = 'Current rows: ' + rows.length + '. Minimum 7 required for group registration.';
    }
}

function addMemberRow() {
    var tbody = document.getElementById('membersTableBody');
    var count = tbody.querySelectorAll('tr').length;
    var tr = document.createElement('tr');
    tr.innerHTML = `
        <td class="text-center align-middle row-index">${count + 1}</td>
        <td><input type="text" name="members[${count}][full_name]" class="form-control form-control-sm member-name" placeholder="Member ${count + 1} Full Name"></td>
        <td><input type="text" name="members[${count}][contact_number]" class="form-control form-control-sm" placeholder="09xxxxxxxxx"></td>
        <td><input type="text" name="members[${count}][role_or_skills]" class="form-control form-control-sm" placeholder="e.g. Rescue / Logistics"></td>
        <td class="text-center align-middle">
            <button type="button" class="btn btn-sm text-danger p-0 btn-remove-member" onclick="removeMemberRow(this)" title="Remove member"><i class="fas fa-times"></i></button>
        </td>
    `;
    tbody.appendChild(tr);
    updateRowIndices();
}

function removeMemberRow(btn) {
    var tbody = document.getElementById('membersTableBody');
    var rows = tbody.querySelectorAll('tr');
    if (rows.length <= 7) {
        alert('Group registrations must maintain at least 7 member rows.');
        return;
    }
    var tr = btn.closest('tr');
    if (tr) {
        tr.remove();
        updateRowIndices();
    }
}
</script>

<?= $this->endSection() ?>
