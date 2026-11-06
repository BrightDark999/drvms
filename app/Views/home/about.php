<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<section class="py-5 mt-4">
    <div class="container">
        <h1 class="section-title">About <?= esc($drms->appName) ?></h1>
        <p class="lead text-muted"><?= esc($drms->appFullName) ?></p>
        <p><?= esc($drms->appTagline) ?></p>

        <div class="alert alert-light border mb-4">
            <strong><?= esc($drms->appName) ?></strong> serves <?= esc($drms->serviceMunicipality) ?> with <?= esc($drms->erpOperationsModuleLabel) ?> and <?= esc($drms->erpReliefModuleLabel) ?> — connected through shared data and REST APIs when the relief service is deployed.
        </div>

        <h2 class="h5 mt-4 font-weight-bold"><?= esc($drms->erpOperationsModuleLabel) ?></h2>
        <ul>
            <li>Public and admin incident reporting with map location</li>
            <li>Volunteer registration, ID verification, and skill-aware dispatch</li>
            <li>Operations dashboard, reports, and audit trail</li>
            <li>REST APIs for incidents, volunteers, and operational stats</li>
        </ul>

        <h2 class="h5 mt-4 font-weight-bold"><?= esc($drms->erpReliefModuleLabel) ?></h2>
        <ul>
            <li>Relief monitoring and distribution tracking</li>
            <li>Donation monitoring and allocation to incidents</li>
            <li>Shared inventory and warehouse visibility</li>
            <li>Evacuation center capacity and family registration</li>
        </ul>

        <h2 class="h5 mt-4 font-weight-bold">Integration</h2>
        <p>Shared users, roles, notifications, incidents, inventory, and audit logs — with phased API integration to the relief &amp; logistics service. See <a href="<?= base_url('/') ?>#modules">system modules on the homepage</a>.</p>

        <p class="text-muted small"><strong>Version:</strong> <?= esc($drms->appVersion) ?> · <strong>Service area:</strong> <?= esc($drms->serviceMunicipality) ?>, <?= esc($drms->serviceProvince) ?></p>

        <a href="<?= base_url('/') ?>#modules" class="btn btn-drms-primary">System modules</a>
        <a href="<?= base_url('/') ?>" class="btn btn-outline-secondary ml-2">Back to Home</a>
        <a href="<?= url_to('login') ?>" class="btn btn-outline-secondary ml-2">Login</a>
    </div>
</section>
<?= $this->endSection() ?>
