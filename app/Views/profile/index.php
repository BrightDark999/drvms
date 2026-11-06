<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<?php
$p = $profile['profile'] ?? [];
$photo = (string) ($p['profile_photo'] ?? '');
$idInfo = drms_id_verification_badge((string) ($p['id_verification_status'] ?? 'none'));
?>
<div class="container py-5 drms-profile-page">
    <div class="row">
        <div class="col-lg-9 mx-auto">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h1 class="h3 mb-3">My profile</h1>
                    <?php if (session('message')): ?><div class="alert alert-success"><?= esc(session('message')) ?></div><?php endif ?>
                    <?php if (session('error')): ?><div class="alert alert-danger"><?= esc(session('error')) ?></div><?php endif ?>
                    <?php if (session('errors')): ?>
                        <div class="alert alert-danger">
                            <?php foreach ((array) session('errors') as $err): ?>
                                <div><?= esc(is_array($err) ? implode(' ', $err) : $err) ?></div>
                            <?php endforeach ?>
                        </div>
                    <?php endif ?>

                    <div class="row mb-4">
                        <div class="col-md-3 text-center mb-3 mb-md-0">
                            <?php if ($photo !== ''): ?>
                                <img src="<?= base_url($photo) ?>" alt="Profile photo" class="rounded-circle img-thumbnail mb-2" style="width:120px;height:120px;object-fit:cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-2" style="width:120px;height:120px;">
                                    <i class="fas fa-user fa-3x text-muted"></i>
                                </div>
                            <?php endif ?>
                            <form action="<?= base_url('profile/upload-photo') ?>" method="post" enctype="multipart/form-data">
                                <?= csrf_field() ?>
                                <input type="file" name="profile_photo" class="form-control-file form-control-sm mb-2" accept="image/jpeg,image/png,image/webp" required>
                                <button type="submit" class="btn btn-outline-secondary btn-sm btn-block">Change photo</button>
                            </form>
                        </div>
                        <div class="col-md-9">
                            <p class="mb-1">Account: <strong><?= esc($profile['user']['username'] ?? '') ?></strong></p>
                            <p class="text-muted mb-2">Roles: <?= esc(implode(', ', $profile['groups'] ?? [])) ?></p>
                            <?php if ($idInfo['is_verified']): ?>
                                <span class="badge badge-<?= esc($idInfo['badge']) ?>"><i class="fas fa-check-circle mr-1"></i><?= esc($idInfo['label']) ?></span>
                            <?php elseif (! empty($p['id_document_path']) || $idInfo['status'] !== 'none'): ?>
                                <span class="badge badge-<?= esc($idInfo['badge']) ?>">ID verification: <?= esc($idInfo['label']) ?></span>
                            <?php endif ?>
                            <?php if (! empty($p['id_document_path'])): ?>
                                <a href="<?= base_url($p['id_document_path']) ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm ml-2">View ID</a>
                            <?php endif ?>
                        </div>
                    </div>

                    <form action="<?= base_url('profile/update') ?>" method="post" class="mb-4">
                        <?= csrf_field() ?>
                        <h2 class="h5">Account details</h2>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Full name</label>
                                <input type="text" name="full_name" class="form-control" value="<?= esc(old('full_name', $p['full_name'] ?? '')) ?>">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="<?= esc(old('email', $profile['user']['email'] ?? '')) ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?= esc(old('phone', $p['phone'] ?? '')) ?>">
                            </div>
                            <div class="form-group col-md-6">
                                <label>New password (leave blank to keep)</label>
                                <input type="password" name="password" class="form-control" minlength="8">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address" class="form-control" rows="2"><?= esc(old('address', $p['address'] ?? '')) ?></textarea>
                        </div>

                        <?php if (! empty($profile['is_volunteer'])): ?>
                            <?php $volunteerRow = $profile['volunteer'] ?? []; ?>
                            <hr>
                            <h2 class="h5">Volunteer profile</h2>
                            <?php
                            $volAvail = strtolower((string) ($volunteerRow['availability_status'] ?? 'available'));
                            $isDeployed = $volAvail === 'deployed';
                            ?>
                            <div class="form-group">
                                <label>Availability <span class="text-danger">*</span></label>
                                <?php if ($isDeployed): ?>
                                    <p class="form-control-plaintext">
                                        <span class="badge badge-danger">Deployed</span>
                                        <span class="text-muted small ml-2">You are on an active assignment. Contact MDRRMO to change status.</span>
                                    </p>
                                <?php else: ?>
                                    <select name="availability_status" class="form-control" required>
                                        <option value="available" <?= $volAvail === 'available' ? 'selected' : '' ?>>Available — ready for deployment</option>
                                        <option value="on_duty" <?= $volAvail === 'on_duty' ? 'selected' : '' ?>>On duty — already serving / standby</option>
                                        <option value="unavailable" <?= $volAvail === 'unavailable' ? 'selected' : '' ?>>Unavailable — cannot respond right now</option>
                                    </select>
                                    <small class="text-muted">Update this when you are not free to respond (e.g. work, travel, rest).</small>
                                <?php endif ?>
                            </div>
                            <div class="form-group">
                                <label>Experience (years)</label>
                                <input type="number" name="experience_years" class="form-control" value="<?= (int) old('experience_years', $volunteerRow['experience_years'] ?? 0) ?>" min="0">
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold d-block">Preferred Volunteer Shift Duration</label>
                                <?php
                                $prefHours = (int) old('preferred_hours', $volunteerRow['preferred_hours'] ?? ($volunteerRow['fatigue_level'] > 12 ? 24 : ($volunteerRow['fatigue_level'] > 8 ? 12 : 8)));
                                if (! in_array($prefHours, [8, 12, 24], true)) {
                                    $prefHours = 8;
                                }
                                ?>
                                <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                                    <label class="btn btn-outline-danger <?= $prefHours === 8 ? 'active' : '' ?>">
                                        <input type="radio" name="preferred_hours" value="8" <?= $prefHours === 8 ? 'checked' : '' ?>>
                                        <i class="fas fa-clock mr-1"></i> 8 hrs
                                    </label>
                                    <label class="btn btn-outline-danger <?= $prefHours === 12 ? 'active' : '' ?>">
                                        <input type="radio" name="preferred_hours" value="12" <?= $prefHours === 12 ? 'checked' : '' ?>>
                                        <i class="fas fa-clock mr-1"></i> 12 hrs
                                    </label>
                                    <label class="btn btn-outline-danger <?= $prefHours === 24 ? 'active' : '' ?>">
                                        <input type="radio" name="preferred_hours" value="24" <?= $prefHours === 24 ? 'checked' : '' ?>>
                                        <i class="fas fa-clock mr-1"></i> 24 hrs
                                    </label>
                                </div>
                                <small class="text-muted">Choose your preferred shift / response availability hours per deployment.</small>
                            </div>
                        <?php endif ?>
                        <button type="submit" class="btn btn-danger">Save profile</button>
                        <a href="<?= base_url('admin/dashboard') ?>" class="btn btn-outline-secondary ml-2">Dashboard</a>
                    </form>

                    <?php if (! empty($profile['is_volunteer'])): ?>
                        <?php
                        $approvedSkills = $profile['approved_skills'] ?? [];
                        $skillRequest   = $profile['skill_request'] ?? null;
                        $requestSkills  = old('skills', $skillRequest['requested_skills'] ?? $approvedSkills);
                        if (! is_array($requestSkills)) {
                            $requestSkills = [];
                        }
                        ?>
                        <hr>
                        <h2 class="h5">Volunteer skills</h2>
                        <p class="text-muted small">Approved skills control which incidents you see in <strong>My Dispatch</strong>. Changes must be approved by MDRRMO to prevent abuse.</p>

                        <div class="mb-3">
                            <span class="d-block small text-muted mb-1">Currently approved</span>
                            <?php if ($approvedSkills === []): ?>
                                <span class="badge badge-secondary">None yet — submit a request below</span>
                            <?php else: ?>
                                <?php foreach ($approvedSkills as $skill): ?>
                                    <span class="badge badge-success mr-1 mb-1"><?= esc($skill) ?></span>
                                <?php endforeach ?>
                            <?php endif ?>
                        </div>

                        <?php if ($skillRequest): ?>
                            <div class="alert alert-warning">
                                <strong>Pending approval.</strong> You requested:
                                <?php foreach ($skillRequest['requested_skills'] ?? [] as $skill): ?>
                                    <span class="badge badge-warning mr-1"><?= esc($skill) ?></span>
                                <?php endforeach ?>
                                <span class="d-block small mt-1 mb-0">MDRRMO will review this before it takes effect.</span>
                            </div>
                        <?php else: ?>
                            <form action="<?= base_url('profile/request-skills') ?>" method="post">
                                <?= csrf_field() ?>
                                <div class="form-group">
                                    <label>Request skill change</label>
                                    <div class="row">
                                        <?php foreach ($skills as $skill): ?>
                                            <div class="col-md-6">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" id="rs_<?= md5($skill) ?>" name="skills[]" value="<?= esc($skill) ?>" <?= in_array($skill, $requestSkills, true) ? 'checked' : '' ?>>
                                                    <label class="custom-control-label" for="rs_<?= md5($skill) ?>"><?= esc($skill) ?></label>
                                                </div>
                                            </div>
                                        <?php endforeach ?>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-outline-danger btn-sm">Submit for admin approval</button>
                            </form>
                        <?php endif ?>
                    <?php endif ?>

                    <form action="<?= base_url('profile/upload-id') ?>" method="post" enctype="multipart/form-data" class="mt-4">
                        <?= csrf_field() ?>
                        <h2 class="h5">ID verification</h2>
                        <p class="text-muted small">Upload a valid government ID (JPG, PNG, or PDF) so administrators can verify your identity.</p>
                        <?php if (! empty($p['id_document_path'])): ?>
                            <p class="small text-muted">Current file: <a href="<?= base_url($p['id_document_path']) ?>" target="_blank" rel="noopener"><?= esc(basename($p['id_document_path'])) ?></a></p>
                        <?php endif ?>
                        <div class="form-group">
                            <input type="file" name="id_document" class="form-control-file" accept="image/jpeg,image/png,image/webp,application/pdf" <?= empty($p['id_document_path']) ? 'required' : '' ?>>
                        </div>
                        <button type="submit" class="btn btn-outline-danger btn-sm">Upload / replace ID</button>
                    </form>
                </div>
            </div>

            <?php if (! empty($volunteerGroup)): ?>
            <div class="card shadow-sm border-0 mt-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h2 class="h5 mb-1"><i class="fas fa-users text-danger mr-1"></i> <?= esc($volunteerGroup['organization_name']) ?></h2>
                            <span class="badge badge-<?= $volunteerGroup['status'] === 'approved' ? 'success' : 'warning' ?>">Group <?= esc(ucfirst($volunteerGroup['status'])) ?></span>
                            <small class="text-muted ml-2">Head: <?= esc($volunteerGroup['head_name']) ?> (<?= esc($volunteerGroup['head_contact']) ?>)</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#modalAddGroupMember">
                            <i class="fas fa-user-plus mr-1"></i> Add Member
                        </button>
                    </div>

                    <p class="text-muted small">Group roster. Any additions or removals requested by the team leader will take effect once reviewed and approved by MDRRMO.</p>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered bg-white mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 30px;">#</th>
                                    <th>Member Name</th>
                                    <th>Contact</th>
                                    <th>Role / Skills</th>
                                    <th>Status</th>
                                    <th style="width: 80px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($volunteerGroupMembers as $idx => $m): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td><strong><?= esc($m['full_name']) ?></strong></td>
                                    <td><?= esc($m['contact_number'] ?: '—') ?></td>
                                    <td><?= esc($m['role_or_skills'] ?: '—') ?></td>
                                    <td>
                                        <?php if ($m['change_type'] === 'delete'): ?>
                                            <span class="badge badge-danger">Pending Deletion</span>
                                        <?php elseif ($m['change_type'] === 'add' || $m['status'] === 'pending'): ?>
                                            <span class="badge badge-warning">Pending Approval</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Approved</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($m['change_type'] !== 'delete'): ?>
                                        <form action="<?= base_url('profile/group-member/delete/' . (int) $m['id']) ?>" method="post" onsubmit="return confirm('Request removal of this group member? This requires MDRRMO approval.');" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-xs btn-outline-danger" title="Request Removal">
                                                <i class="fas fa-user-minus"></i> Remove
                                            </button>
                                        </form>
                                        <?php else: ?>
                                            <span class="small text-muted font-italic">Pending</span>
                                        <?php endif ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Add Group Member -->
            <div class="modal fade" id="modalAddGroupMember" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <form action="<?= base_url('profile/group-member/add') ?>" method="post" class="modal-content">
                        <?= csrf_field() ?>
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title font-weight-bold"><i class="fas fa-user-plus mr-1"></i> Add Group Member</h5>
                            <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Member Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control" required placeholder="Full Name">
                            </div>
                            <div class="form-group">
                                <label>Contact Number</label>
                                <input type="text" name="contact_number" class="form-control" placeholder="09xxxxxxxxx">
                            </div>
                            <div class="form-group">
                                <label>Email Address (optional)</label>
                                <input type="email" name="email" class="form-control" placeholder="member@example.com">
                            </div>
                            <div class="form-group">
                                <label>Role / Specialization / Skills</label>
                                <input type="text" name="role_or_skills" class="form-control" placeholder="e.g. First aid, Rescue, Driver">
                            </div>
                            <div class="form-group">
                                <label>Address (Sitio / Barangay)</label>
                                <input type="text" name="address" class="form-control" placeholder="Baras, Rizal">
                            </div>
                            <small class="text-muted d-block">Submitted members will be queued for MDRRMO verification and approval.</small>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger btn-sm">Submit Member for Approval</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <?php if (! empty($reports)): ?>
            <div class="card shadow-sm border-0 mt-4">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">My incident reports</h2>
                    <p class="text-muted small">Reports you submitted while logged in. Open photos or videos below each reference.</p>
                    <?php foreach ($reports as $report): ?>
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-start mb-2">
                                <div>
                                    <strong><?= esc($report['reference_code'] ?? '') ?></strong>
                                    <span class="text-muted"> — <?= esc($report['title'] ?? '') ?></span>
                                </div>
                                <span class="badge badge-secondary"><?= esc(ucfirst(str_replace('_', ' ', (string) ($report['status'] ?? 'reported')))) ?></span>
                            </div>
                            <p class="small text-muted mb-2">
                                Reported: <?= esc($report['reported_at'] ?? $report['created_at'] ?? '') ?>
                                <?php if (! empty($report['barangay_name'])): ?>
                                    · <?= esc($report['barangay_name']) ?>
                                <?php endif ?>
                            </p>
                            <?= view('partials/incident_media_gallery', ['media' => $report['media'] ?? []]) ?>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
            <?php endif ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
