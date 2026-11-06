<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= esc($drms->appTagline ?? '') ?>">
    <title><?= esc($title ?? drms_brand_label()) ?></title>
    <link rel="icon" type="image/png" sizes="41x43" href="<?= asset_url('assets/logo/baras_seal_xs.png') ?>">
    <link rel="apple-touch-icon" sizes="185x193" href="<?= asset_url('assets/logo/baras_seal_l.png') ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">

    <!-- Bootstrap 4 (from AdminLTE bundle – same as dashboard for consistency) -->
    <link rel="stylesheet" href="<?= adminlte_asset('plugins/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= adminlte_asset('plugins/fontawesome-free/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset_versioned('assets/css/public-home.css') ?>">

    <script>
        window.DRMS_WEATHER_URL = <?= json_encode(base_url('api/weather/current'), JSON_THROW_ON_ERROR) ?>;
    </script>

    <!-- Lucide Icons (homepage only – lightweight SVG icons) -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
    <?= $this->renderSection('styles') ?>
</head>
<body class="drms-public-body drms-public-theme">
    <?= $this->include('partials/public/navbar') ?>

    <main>
        <?= $this->renderSection('content') ?>
    </main>

    <?= $this->include('partials/public/footer') ?>

    <script src="<?= adminlte_asset('plugins/jquery/jquery.min.js') ?>"></script>
    <script src="<?= adminlte_asset('plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= asset_versioned('assets/js/public-home.js') ?>"></script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
