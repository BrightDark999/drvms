<?php
helper('drms');

$matchClass = static function (int $pct): string {
    if ($pct >= 80) {
        return 'success';
    }
    if ($pct >= 60) {
        return 'warning';
    }

    return 'secondary';
};

$priorityBadge = static function (string $tag): string {
    return match (strtoupper($tag)) {
        'HIGH'   => 'danger',
        'MEDIUM' => 'warning',
        default  => 'secondary',
    };
};

$topThree = array_slice($ranked, 0, 3);
$totalGap = $fulfillment ? (int) (($fulfillment['rescue']['gap'] ?? 0) + ($fulfillment['medical']['gap'] ?? 0) + ($fulfillment['drivers']['gap'] ?? 0)) : 0;
$canDispatch = $canDispatch ?? drms_can_dispatch_volunteers();
$volunteerPortal = $volunteerPortal ?? drms_is_volunteer_dispatch_view();
$myMatchPct = (int) ($myMatch['match_pct'] ?? 0);
$myMatchClass = $matchClass($myMatchPct);
?>

<?= $this->extend('layouts/admin') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<?= $this->endSection() ?>

<?= $this->section('breadcrumb') ?>
<li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Home</a></li>
<li class="breadcrumb-item active"><?= $volunteerPortal ? 'My Dispatch' : 'Volunteer Dispatch' ?></li>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php if ($volunteerPortal): ?>
<div class="row">
    <div class="col-12">
        <div class="callout callout-info mb-3">
            <h5 class="mb-1"><i class="fas fa-eye mr-1"></i> Volunteer view — read only</h5>
            <p class="mb-0">You only see incidents that match your <strong>admin-approved skills</strong>. MDRRMO assigns deployments — you cannot deploy yourself. Update skills on <a href="<?= base_url('profile') ?>">My Profile</a> (requires approval).</p>
        </div>
    </div>
</div>
<?php endif ?>

<?php if (! empty($incidentAccessDenied)): ?>
<div class="row">
    <div class="col-12">
        <div class="alert alert-warning">That incident is not visible with your current approved skills. Request skill changes on your profile if you believe you should see it.</div>
    </div>
</div>
<?php endif ?>

<div class="row drms-match-stats mb-3">
    <?php if ($volunteerPortal): ?>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?= count($queue) ?></h3>
                <p>Active incidents</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= $selectedId > 0 && $myMatch ? $myMatchPct . '%' : '—' ?></h3>
                <p>Your match score</p>
            </div>
            <div class="icon"><i class="fas fa-percentage"></i></div>
        </div>
    </div>
    <div class="col-lg-4 col-12">
        <div class="small-box bg-<?= $myDeployment ? 'success' : 'secondary' ?>">
            <div class="inner">
                <h3><?= $myDeployment ? esc(ucfirst((string) ($myDeployment['status'] ?? 'assigned'))) : 'Not assigned' ?></h3>
                <p>Your assignment on selected incident</p>
            </div>
            <div class="icon"><i class="fas fa-user-check"></i></div>
        </div>
    </div>
    <?php else: ?>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= (int) ($poolStats['total'] ?? 0) ?></h3>
                <p>Pool available</p>
            </div>
            <div class="icon"><i class="fas fa-users"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= $selectedId > 0 ? (int) ($matchStats['avg_match'] ?? 0) . '%' : '—' ?></h3>
                <p>Avg match score</p>
            </div>
            <div class="icon"><i class="fas fa-percentage"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= ($matchStats['nearest_km'] ?? null) !== null ? esc((string) $matchStats['nearest_km']) . ' km' : '—' ?></h3>
                <p>Nearest volunteer</p>
            </div>
            <div class="icon"><i class="fas fa-map-marker-alt"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?= $selectedId > 0 ? $totalGap : '—' ?></h3>
                <p>Staffing gap</p>
            </div>
            <div class="icon"><i class="fas fa-user-clock"></i></div>
        </div>
    </div>
    <?php endif ?>
</div>

<div class="row">
    <div class="col-lg-3">
        <div class="card card-outline card-danger drms-match-queue">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list-ul mr-1"></i> Incident queue</h3>
            </div>
            <div class="list-group list-group-flush drms-match-queue-list">
                <?php if ($queue === []): ?>
                    <div class="list-group-item text-muted small">
                        <?= $volunteerPortal
                            ? 'No active incidents match your approved skills yet.'
                            : 'No active incidents.' ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($queue as $q):
                        $qid = (int) ($q['id'] ?? 0);
                        $active = $selectedId === $qid;
                        $ptype = (string) ($q['priority_tag'] ?? 'LOW');
                        ?>
                        <a href="<?= base_url('admin/matching?incident_id=' . $qid) ?>"
                           class="list-group-item list-group-item-action <?= $active ? 'active' : '' ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <strong class="small"><?= esc($q['reference_code'] ?? '') ?></strong>
                                <span class="badge badge-<?= $priorityBadge($ptype) ?>"><?= esc($ptype) ?></span>
                            </div>
                            <div class="small <?= $active ? '' : 'text-muted' ?>"><?= esc($q['title'] ?? '') ?></div>
                            <div class="small mt-1">
                                <span class="badge badge-light"><?= esc(ucfirst($q['status'] ?? '')) ?></span>
                                <span class="badge badge-light"><?= esc(ucfirst($q['severity'] ?? '')) ?></span>
                            </div>
                        </a>
                    <?php endforeach ?>
                <?php endif ?>
            </div>
        </div>

        <?php if (! $volunteerPortal): ?>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Skill pool</h3></div>
            <div class="card-body py-2">
                <div class="drms-pool-row"><span>Rescue</span><strong><?= (int) ($poolStats['Rescue'] ?? 0) ?></strong></div>
                <div class="drms-pool-row"><span>Medical</span><strong><?= (int) ($poolStats['Medical'] ?? 0) ?></strong></div>
                <div class="drms-pool-row"><span>Driving</span><strong><?= (int) ($poolStats['Driving'] ?? 0) ?></strong></div>
            </div>
        </div>
        <?php endif ?>
    </div>

    <div class="col-lg-9">
        <?php if ($selectedId < 1 || ! $incident): ?>
            <div class="card card-outline card-secondary">
                <div class="card-body text-center py-5">
                    <i class="fas fa-hands-helping fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">Select an incident from the queue</h4>
                    <p class="text-muted mb-0"><?= $volunteerPortal ? 'See how you fit the incident and whether MDRRMO has assigned you.' : 'Ranked volunteers, map view, and staffing tracker appear here.' ?></p>
                </div>
            </div>
        <?php else:
            $itype = (string) ($incident['incident_type'] ?? 'other');
            $marker = drms_incident_map_marker($itype, (string) ($incident['severity'] ?? 'moderate'));
            ?>
            <div class="card card-outline card-danger drms-match-incident-header">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-start justify-content-between">
                        <div class="d-flex align-items-start">
                            <div class="drms-match-incident-icon mr-3" style="background:<?= esc($marker['marker_bg']) ?>;border-color:<?= esc($marker['border_color']) ?>">
                                <i class="fas <?= esc($marker['icon']) ?>"></i>
                            </div>
                            <div>
                                <div class="text-muted small"><?= esc($incident['reference_code'] ?? '') ?></div>
                                <h3 class="mb-1"><?= esc($incident['title'] ?? '') ?></h3>
                                <div class="mb-2">
                                    <span class="badge badge-<?= $priorityBadge((string) ($incident['priority_tag'] ?? 'LOW')) ?>"><?= esc($incident['priority_tag'] ?? 'LOW') ?></span>
                                    <span class="badge badge-secondary"><?= esc($incidentTypes[$itype] ?? ucfirst($itype)) ?></span>
                                    <span class="badge badge-light"><?= esc(ucfirst($incident['severity'] ?? '')) ?></span>
                                    <span class="badge badge-light"><?= esc(ucfirst($incident['status'] ?? '')) ?></span>
                                </div>
                                <?php if (! empty($incident['barangay_name'])): ?>
                                    <div class="small text-muted"><i class="fas fa-map-pin"></i> <?= esc($incident['barangay_name']) ?></div>
                                <?php endif ?>
                            </div>
                        </div>
                        <?php if ($canDispatch): ?>
                        <div class="text-right mt-2 mt-md-0">
                            <?php if ($autoTeam !== []): ?>
                                <button type="button" class="btn btn-danger btn-sm mb-1" id="btnAutoAssign"
                                    data-incident-id="<?= (int) $selectedId ?>">
                                    <i class="fas fa-magic"></i> Auto-assign team (<?= count($autoTeam) ?>)
                                </button>
                            <?php endif ?>
                            <button type="button" class="btn btn-outline-success btn-sm mb-1" id="btnDeploySelected" disabled>
                                <i class="fas fa-user-check"></i> Deploy selected
                            </button>
                        </div>
                        <?php endif ?>
                    </div>
                </div>
            </div>

            <?php if ($volunteerPortal): ?>
            <div class="card card-outline card-primary">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-user mr-1"></i> Your fit for this incident</h3></div>
                <div class="card-body">
                    <?php if ($myDeployment): ?>
                        <div class="alert alert-success mb-3">
                            <strong>You are assigned.</strong>
                            Status: <span class="badge badge-success"><?= esc(ucfirst((string) ($myDeployment['status'] ?? 'deployed'))) ?></span>
                            <?php if (! empty($myDeployment['deployed_at'])): ?>
                                · Deployed <?= esc($myDeployment['deployed_at']) ?>
                            <?php endif ?>
                        </div>
                    <?php elseif ($myMatch): ?>
                        <div class="row align-items-center">
                            <div class="col-md-4 text-center mb-3 mb-md-0">
                                <div class="drms-match-ring drms-match-ring--<?= $myMatchClass ?>" style="--pct: <?= $myMatchPct ?>">
                                    <span><?= $myMatchPct ?>%</span>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <p class="mb-2">MDRRMO uses this score when choosing volunteers. Keep your profile, skills, and availability up to date.</p>
                                <div class="mb-2">
                                    <?php foreach ($myMatch['matched_skills'] ?? [] as $sk): ?>
                                        <span class="badge badge-success"><?= esc($sk) ?></span>
                                    <?php endforeach ?>
                                    <?php foreach ($myMatch['missing_skills'] ?? [] as $sk): ?>
                                        <span class="badge badge-outline-missing"><?= esc($sk) ?> needed</span>
                                    <?php endforeach ?>
                                </div>
                                <p class="small text-muted mb-0">
                                    <?= ($myMatch['distance_km'] ?? null) !== null ? esc((string) $myMatch['distance_km']) . ' km away' : 'Distance unknown' ?>
                                    · Fatigue <?= (int) ($myMatch['fatigue_level'] ?? 0) ?>%
                                    · <?= esc(ucfirst((string) ($myMatch['availability_status'] ?? ''))) ?>
                                </p>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-2">You are not in the current match list for this incident. Common reasons:</p>
                        <ul class="text-muted small mb-0">
                            <li>Availability is <strong>unavailable</strong> or you are already <strong>deployed</strong> elsewhere</li>
                            <li>Required skills on your profile do not match this incident</li>
                            <li>MDRRMO has not yet opened this incident for volunteer matching</li>
                        </ul>
                        <a href="<?= base_url('profile') ?>" class="btn btn-outline-primary btn-sm mt-3">Update my profile</a>
                    <?php endif ?>
                    <?php if (! empty($requiredSkills)): ?>
                        <hr>
                        <p class="small text-muted mb-1">Skills MDRRMO is looking for:</p>
                        <?php foreach ($requiredSkills as $sk): ?>
                            <span class="badge badge-info"><?= esc($sk) ?></span>
                        <?php endforeach ?>
                    <?php endif ?>
                </div>
            </div>
            <?php endif ?>

            <?php if ($fulfillment): ?>
            <div class="card">
                <div class="card-header"><h3 class="card-title">Staffing fulfillment</h3></div>
                <div class="card-body">
                    <?php foreach (['rescue' => 'Rescuers', 'medical' => 'Medical', 'drivers' => 'Drivers'] as $key => $label):
                        $need = (int) ($fulfillment[$key]['needed'] ?? 0);
                        $assigned = (int) ($fulfillment[$key]['assigned'] ?? 0);
                        $pct = $need > 0 ? min(100, (int) round(($assigned / $need) * 100)) : ($assigned > 0 ? 100 : 0);
                        $barClass = $pct >= 100 ? 'bg-success' : ($pct >= 50 ? 'bg-warning' : 'bg-danger');
                        ?>
                        <div class="drms-staff-row mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span><?= esc($label) ?></span>
                                <span><strong><?= $assigned ?></strong> / <?= $need ?> <?= ($fulfillment[$key]['gap'] ?? 0) > 0 ? '<span class="text-danger">(gap ' . (int) $fulfillment[$key]['gap'] . ')</span>' : '' ?></span>
                            </div>
                            <div class="progress progress-sm">
                                <div class="progress-bar <?= $barClass ?>" style="width: <?= $pct ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach ?>
                    <p class="small text-muted mb-0">Required skills:
                        <?php if (empty($requiredSkills)): ?>
                            general availability
                        <?php else: ?>
                            <?php foreach ($requiredSkills as $sk): ?>
                                <span class="badge badge-info"><?= esc($sk) ?></span>
                            <?php endforeach ?>
                        <?php endif ?>
                    </p>
                </div>
            </div>
            <?php endif ?>

            <?php if (! $volunteerPortal && $topThree !== []): ?>
            <div class="row drms-match-top3">
                <?php foreach ($topThree as $idx => $r):
                    $pct = (int) ($r['match_pct'] ?? 0);
                    $cls = $matchClass($pct);
                    ?>
                    <div class="col-md-4">
                        <div class="card drms-match-card h-100 <?= $idx === 0 ? 'drms-match-card--best' : '' ?>">
                            <div class="card-body text-center">
                                <div class="drms-match-ring drms-match-ring--<?= $cls ?>" style="--pct: <?= $pct ?>">
                                    <span><?= $pct ?>%</span>
                                </div>
                                <h5 class="mb-1"><?= esc($r['full_name'] ?? '') ?></h5>
                                <div class="small text-muted mb-2">
                                    <?= $r['distance_km'] !== null ? esc((string) $r['distance_km']) . ' km' : 'Distance unknown' ?>
                                    · <?= (int) ($r['experience_years'] ?? 0) ?> yrs exp
                                </div>
                                <div class="mb-2">
                                    <?php foreach (array_slice($r['skills'] ?? [], 0, 3) as $sk): ?>
                                        <span class="badge badge-light"><?= esc($sk) ?></span>
                                    <?php endforeach ?>
                                </div>
                                <button type="button" class="btn btn-sm btn-success btn-deploy-one"
                                    data-volunteer-id="<?= (int) ($r['volunteer_id'] ?? 0) ?>"
                                    data-incident-id="<?= (int) $selectedId ?>">
                                    <i class="fas fa-user-check"></i> Deploy
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-match-detail"
                                    data-volunteer='<?= esc(json_encode($r), 'attr') ?>'>
                                    Details
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
            <?php endif ?>

            <div class="row">
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-map mr-1"></i> <?= $volunteerPortal ? 'Incident map' : 'Dispatch map' ?></h3></div>
                        <div class="card-body p-0">
                            <div id="drmsMatchMap" class="drms-match-map"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-history mr-1"></i> <?= $volunteerPortal ? 'Your assignment' : 'Deployments' ?></h3></div>
                        <div class="card-body p-0">
                            <?php if ($timeline === []): ?>
                                <p class="text-muted small p-3 mb-0"><?= $volunteerPortal ? 'You are not deployed on this incident yet.' : 'No volunteers deployed yet.' ?></p>
                            <?php else: ?>
                                <ul class="list-group list-group-flush drms-match-timeline">
                                    <?php foreach ($timeline as $t): ?>
                                        <li class="list-group-item">
                                            <div class="d-flex justify-content-between">
                                                <strong class="small"><?= esc($t['full_name'] ?? '') ?></strong>
                                                <span class="badge badge-<?= ($t['status'] ?? '') === 'deployed' ? 'success' : 'secondary' ?>"><?= esc(ucfirst($t['status'] ?? '')) ?></span>
                                            </div>
                                            <div class="small text-muted"><?= esc($t['deployed_at'] ?? '') ?></div>
                                        </li>
                                    <?php endforeach ?>
                                </ul>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (! $volunteerPortal): ?>
            <div class="card">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
                    <h3 class="card-title mb-2 mb-md-0">Ranked recommendations</h3>
                    <div class="drms-match-filters btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary active" data-filter="all">All</button>
                        <button type="button" class="btn btn-outline-secondary" data-filter="near">≤ 15 km</button>
                        <button type="button" class="btn btn-outline-secondary" data-filter="high">≥ 80% match</button>
                        <button type="button" class="btn btn-outline-secondary" data-filter="available">Available</button>
                    </div>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover mb-0" id="matchTable">
                        <thead>
                            <tr>
                                <th width="36"><input type="checkbox" id="matchSelectAll" title="Select all"></th>
                                <th>Volunteer</th>
                                <th>Match</th>
                                <th>Skills</th>
                                <th>Distance</th>
                                <th>Signals</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($ranked === []): ?>
                                <tr><td colspan="7" class="text-muted p-4">No available volunteers match this incident.</td></tr>
                            <?php else: ?>
                                <?php foreach ($ranked as $r):
                                    $pct = (int) ($r['match_pct'] ?? 0);
                                    $cls = $matchClass($pct);
                                    $dist = $r['distance_km'];
                                    ?>
                                    <tr class="drms-match-row"
                                        data-volunteer-id="<?= (int) ($r['volunteer_id'] ?? 0) ?>"
                                        data-match="<?= $pct ?>"
                                        data-distance="<?= $dist !== null ? (float) $dist : 9999 ?>"
                                        data-available="<?= ($r['availability_status'] ?? '') === 'available' ? '1' : '0' ?>">
                                        <td>
                                            <input type="checkbox" class="match-row-cb" value="<?= (int) ($r['volunteer_id'] ?? 0) ?>">
                                        </td>
                                        <td>
                                            <strong><?= esc($r['full_name'] ?? '') ?></strong>
                                            <div class="small text-muted">Fatigue <?= (int) ($r['fatigue_level'] ?? 0) ?>% · <?= esc(ucfirst($r['availability_status'] ?? '')) ?></div>
                                        </td>
                                        <td>
                                            <span class="drms-match-pill drms-match-pill--<?= $cls ?>"><?= $pct ?>%</span>
                                        </td>
                                        <td class="drms-match-skills">
                                            <?php foreach ($r['matched_skills'] ?? [] as $sk): ?>
                                                <span class="badge badge-success"><?= esc($sk) ?></span>
                                            <?php endforeach ?>
                                            <?php foreach ($r['missing_skills'] ?? [] as $sk): ?>
                                                <span class="badge badge-outline-missing"><?= esc($sk) ?></span>
                                            <?php endforeach ?>
                                            <?php if (empty($r['matched_skills']) && empty($r['missing_skills']) && ! empty($r['skills'])): ?>
                                                <?php foreach ($r['skills'] as $sk): ?>
                                                    <span class="badge badge-light"><?= esc($sk) ?></span>
                                                <?php endforeach ?>
                                            <?php endif ?>
                                        </td>
                                        <td><?= $dist !== null ? esc((string) $dist) . ' km' : '—' ?></td>
                                        <td><small class="text-muted"><?= esc(implode(' · ', array_slice($r['reasons'] ?? [], 0, 3))) ?></small></td>
                                        <td class="text-nowrap">
                                            <button type="button" class="btn btn-xs btn-success drms-icon-action btn-deploy-one"
                                                data-volunteer-id="<?= (int) ($r['volunteer_id'] ?? 0) ?>"
                                                data-incident-id="<?= (int) $selectedId ?>"
                                                title="Deploy" aria-label="Deploy">
                                                <i class="fas fa-user-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-xs btn-outline-secondary drms-icon-action btn-match-detail"
                                                data-volunteer='<?= esc(json_encode($r), 'attr') ?>'
                                                title="Score breakdown" aria-label="Score breakdown">
                                                <i class="fas fa-chart-bar"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            <?php endif ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif ?>
        <?php endif ?>
    </div>
</div>

<div class="modal fade" id="matchDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Match breakdown</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="matchDetailBody"></div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
$(function () {
    var incidentId = <?= (int) $selectedId ?>;
    var mapCfg = <?= json_encode($mapConfig) ?>;
    var mapIncident = <?= json_encode($mapIncident) ?>;
    var mapVolunteers = <?= json_encode($mapVolunteers) ?>;
    var canDispatch = <?= $canDispatch ? 'true' : 'false' ?>;

    function deployVolunteers(ids, label) {
        if (!canDispatch || !ids.length || incidentId < 1) return;
        DRMS.confirm(label || 'Deploy volunteer(s)?', 'They will be marked deployed and notified by email if configured.', function () {
            var url = ids.length === 1
                ? '<?= base_url('admin/matching/deploy') ?>'
                : '<?= base_url('admin/matching/deploy-bulk') ?>';
            var payload = { incident_id: incidentId };
            if (ids.length === 1) {
                payload.volunteer_id = ids[0];
            } else {
                payload.volunteer_ids = ids;
            }
            DRMS.postData(url, payload, function (res) {
                DRMS.toast('Deployed', res.message, 'success');
                setTimeout(function () { window.location.reload(); }, 800);
            });
        });
    }

    $('.btn-deploy-one').on('click', function () {
        deployVolunteers([parseInt($(this).data('volunteer-id'), 10)], 'Deploy this volunteer?');
    });

    $('#btnDeploySelected').on('click', function () {
        var ids = $('.match-row-cb:checked').map(function () { return parseInt(this.value, 10); }).get();
        deployVolunteers(ids, 'Deploy ' + ids.length + ' selected volunteer(s)?');
    });

    $('#btnAutoAssign').on('click', function () {
        DRMS.confirm('Auto-assign team?', 'DRMS will deploy the best volunteers to fill remaining staffing gaps.', function () {
            DRMS.postData('<?= base_url('admin/matching/auto-assign') ?>', { incident_id: incidentId }, function (res) {
                DRMS.toast('Team assigned', res.message, 'success');
                setTimeout(function () { window.location.reload(); }, 800);
            });
        });
    });

    function updateBulkBtn() {
        var n = $('.match-row-cb:checked').length;
        $('#btnDeploySelected').prop('disabled', n === 0).text(n > 0 ? 'Deploy selected (' + n + ')' : 'Deploy selected');
    }

    $('#matchSelectAll').on('change', function () {
        $('.match-row-cb:visible').prop('checked', this.checked);
        updateBulkBtn();
    });

    $(document).on('change', '.match-row-cb', updateBulkBtn);

    $('.drms-match-filters [data-filter]').on('click', function () {
        var f = $(this).data('filter');
        $(this).addClass('active').siblings().removeClass('active');
        $('#matchSelectAll').prop('checked', false);
        $('.drms-match-row').each(function () {
            var $r = $(this);
            var show = true;
            if (f === 'near') show = parseFloat($r.data('distance')) <= 15;
            if (f === 'high') show = parseInt($r.data('match'), 10) >= 80;
            if (f === 'available') show = String($r.data('available')) === '1';
            $r.toggle(show);
        });
        updateBulkBtn();
    });

    $('.btn-match-detail').on('click', function () {
        var v;
        try { v = JSON.parse($(this).attr('data-volunteer')); } catch (e) { return; }
        var html = '<p><strong>' + (v.full_name || '') + '</strong> — <span class="badge badge-success">' + (v.match_pct || 0) + '% match</span></p>';
        html += '<ul class="list-unstyled small mb-0">';
        (v.reasons || []).forEach(function (r) {
            html += '<li><i class="fas fa-check text-success mr-1"></i> ' + r + '</li>';
        });
        html += '</ul>';
        if ((v.missing_skills || []).length) {
            html += '<p class="small text-danger mt-2 mb-0">Missing: ' + v.missing_skills.join(', ') + '</p>';
        }
        $('#matchDetailBody').html(html);
        $('#matchDetailModal').modal('show');
    });

    if (typeof L === 'undefined' || !document.getElementById('drmsMatchMap')) {
        return;
    }

    var map = L.map('drmsMatchMap', { minZoom: 11, maxZoom: 17 }).setView(
        [mapCfg.center.lat, mapCfg.center.lng],
        mapCfg.zoom || 13
    );
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    var bounds = [];
    var markers = {};

    if (mapIncident) {
        var m = mapIncident.marker || {};
        var incIcon = L.divIcon({
            className: 'drms-incident-marker-wrap',
            html: '<div class="drms-incident-marker" style="background:' + (m.marker_bg || '#c62828') + ';border-color:' + (m.border_color || '#ffc107') + '"><i class="fas ' + (m.icon || 'fa-exclamation-triangle') + '"></i></div>',
            iconSize: [38, 38],
            iconAnchor: [19, 19]
        });
        var incMarker = L.marker([mapIncident.latitude, mapIncident.longitude], { icon: incIcon, zIndexOffset: 1000 })
            .bindPopup('<strong>' + (mapIncident.reference || '') + '</strong><br>' + (mapIncident.title || ''))
            .addTo(map);
        bounds.push([mapIncident.latitude, mapIncident.longitude]);
        markers.incident = incMarker;
    }

    mapVolunteers.forEach(function (v) {
        var size = v.rank <= 3 ? 32 : 26;
        var volIcon = L.divIcon({
            className: 'drms-vol-marker-wrap',
            html: '<div class="drms-vol-marker" style="width:' + size + 'px;height:' + size + 'px;font-size:' + (size * 0.35) + 'px">' + v.match_pct + '</div>',
            iconSize: [size, size],
            iconAnchor: [size / 2, size / 2]
        });
        var latLng = [v.latitude, v.longitude];
        var mk = L.marker(latLng, { icon: volIcon })
            .bindPopup('<strong>' + v.full_name + '</strong><br>' + v.match_pct + '% match')
            .addTo(map);
        markers[v.volunteer_id] = mk;
        bounds.push(latLng);
    });

    if (bounds.length > 1) {
        map.fitBounds(bounds, { padding: [30, 30], maxZoom: 14 });
    } else if (bounds.length === 1) {
        map.setView(bounds[0], 14);
    }

    $('.drms-match-row').on('click', function (e) {
        if ($(e.target).is('input, button, a, i')) return;
        var id = parseInt($(this).data('volunteer-id'), 10);
        if (markers[id]) {
            map.setView(markers[id].getLatLng(), Math.max(map.getZoom(), 14));
            markers[id].openPopup();
        }
    });

    setTimeout(function () { map.invalidateSize(); }, 300);
});
</script>
<?= $this->endSection() ?>
