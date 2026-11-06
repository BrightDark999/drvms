<?php $drms = $drms ?? drms_config(); ?>
<footer class="drms-footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4 mb-lg-0">
                <div class="d-flex align-items-center mb-3">
                    <img src="<?= asset_url('assets/logo/baras_seal_xs.png') ?>" width="41" height="43" class="footer-brand-icon mr-2" alt="Municipality of Baras seal">
                    <h5 class="mb-0 text-white font-weight-bold"><?= esc($drms->appName) ?></h5>
                </div>
                <p class="small text-muted mb-2"><?= esc($drms->appFullName) ?></p>
                <p class="small text-muted mb-0">Serving <strong class="text-white-50"><?= esc($drms->serviceMunicipality) ?></strong>, <?= esc($drms->serviceProvince) ?> · Version <?= esc($drms->appVersion) ?></p>
            </div>
            <div class="col-sm-6 col-lg-4 mb-4 mb-lg-0">
                <h6 class="text-white font-weight-bold mb-3">Explore</h6>
                <ul class="list-unstyled small drms-footer-links mb-0">
                    <li><a href="<?= base_url('/') ?>#features">Features</a></li>
                    <li><a href="<?= base_url('/') ?>#disasters">Disaster resilience</a></li>
                    <li><a href="<?= base_url('/') ?>#location">Map &amp; weather</a></li>
                    <li><a href="<?= base_url('/') ?>#modules">System modules</a></li>
                    <li><a href="<?= base_url('/') ?>#modules">System modules</a></li>
                    <li><a href="<?= base_url('/') ?>#public-services">Public services</a></li>
                    <li><a href="<?= base_url('about') ?>">About DRMS</a></li>
                    <?php if ($drms->demoModeEnabled ?? false) : ?>
                    <li><a href="<?= base_url('demo') ?>">Client demo</a></li>
                    <?php endif ?>
                </ul>
            </div>
            <div class="col-sm-6 col-lg-4">
                <h6 class="text-white font-weight-bold mb-3">Operations</h6>
                <ul class="list-unstyled small drms-footer-links mb-0">
                    <li><a href="<?= base_url('admin/dashboard') ?>">Admin dashboard</a></li>
                    <li><a href="<?= base_url('api/v1/health') ?>" target="_blank" rel="noopener">API health</a></li>
                    <li><a href="https://www.pagasa.dost.gov.ph/" target="_blank" rel="noopener">PAGASA official advisories</a></li>
                </ul>
                <p class="small text-muted mt-3 mb-0">Weather on this site is informational (Open-Meteo). Always follow official government warnings.</p>
            </div>
        </div>
        <hr class="border-secondary mt-4 mb-3">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-left small text-muted mb-2 mb-md-0">
                &copy; <?= date('Y') ?> <?= esc($drms->organization) ?> For Thesis/Capstone Purpose not for Production. All rights reserved.
            </div>
            <div class="col-md-6 text-center text-md-right small text-muted">
                <?= esc($drms->appName) ?> · <?= esc($drms->serviceMunicipality) ?>
            </div>
        </div>
    </div>
</footer>
