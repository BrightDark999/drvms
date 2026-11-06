<?php
$drms = drms_config();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->renderSection('title') ?> | <?= esc(drms_title_suffix()) ?></title>
    <link rel="icon" type="image/png" sizes="41x43" href="<?= asset_url('assets/logo/baras_seal_xs.png') ?>">
    <link rel="apple-touch-icon" sizes="185x193" href="<?= asset_url('assets/logo/baras_seal_l.png') ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= adminlte_asset('plugins/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= adminlte_asset('plugins/fontawesome-free/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('assets/css/drms-login.css') ?>">
    <?= $this->renderSection('pageStyles') ?>
</head>
<body class="drms-login-page">

<div class="drms-login-wrap">
    <aside class="drms-login-brand">
        <div class="drms-login-brand-inner">
            <div class="drms-login-logo">
                <img src="<?= asset_url('assets/logo/baras_seal_s.png') ?>" width="137" height="143" alt="Municipality of Baras seal">
            </div>
            <h1><?= esc($drms->appName) ?></h1>
            <span class="badge badge-light mb-2"><?= esc($drms->appFullName) ?></span>
            <p class="tagline"><?= esc($drms->appTagline) ?></p>
            <span class="area-badge">
                <i class="fas fa-map-marker-alt mr-1"></i>
                <?= esc($drms->serviceMunicipality) ?>, <?= esc($drms->serviceProvince) ?>
            </span>
            <ul class="drms-login-features">
                <li><i class="fas fa-check-circle"></i> Incident reporting &amp; verification</li>
                <li><i class="fas fa-check-circle"></i> Volunteer matching &amp; deployment</li>
                <li><i class="fas fa-check-circle"></i> Relief, evacuation &amp; operations analytics</li>
            </ul>
            <p class="drms-login-back mb-0">
                <a href="<?= base_url('/') ?>"><i class="fas fa-arrow-left mr-1"></i> Back to public homepage</a>
            </p>
        </div>
    </aside>

    <main class="drms-login-main">
        <?= $this->renderSection('main') ?>
    </main>
</div>

<!-- Fullscreen Centered Login Loader -->
<div id="loginLoaderOverlay" class="drms-login-loader-overlay" style="display: none;">
    <div class="drms-login-loader-content text-center">
        <img src="<?= asset_url('assets/logo/baras_seal_s.png') ?>" width="84" height="87" alt="Municipality of Baras seal" class="mb-3 drms-pulse-logo">
        <div class="spinner-border text-danger mb-3 d-block mx-auto" role="status" style="width: 2.5rem; height: 2.5rem;">
            <span class="sr-only">Loading...</span>
        </div>
        <h4 class="text-white font-weight-bold mb-1">Authenticating...</h4>
        <p class="text-white-50 small mb-0">Preparing your DRMS operations dashboard</p>
    </div>
</div>

<script src="<?= adminlte_asset('plugins/jquery/jquery.min.js') ?>"></script>
<script src="<?= adminlte_asset('plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<?= $this->renderSection('pageScripts') ?>
</body>
</html>
