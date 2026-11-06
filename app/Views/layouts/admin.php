<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="<?= esc(config('Security')->tokenName) ?>" content="<?= csrf_hash() ?>">
    <title><?= esc($title ?? 'Dashboard') ?> | <?= esc(drms_title_suffix()) ?></title>
    <link rel="icon" type="image/png" sizes="41x43" href="<?= asset_url('assets/logo/baras_seal_xs.png') ?>">
    <link rel="apple-touch-icon" sizes="185x193" href="<?= asset_url('assets/logo/baras_seal_l.png') ?>">

    <!-- AdminLTE 3 + bundled plugins (all local – InfinityFree friendly) -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="<?= adminlte_asset('plugins/fontawesome-free/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= adminlte_asset('plugins/overlayScrollbars/css/OverlayScrollbars.min.css') ?>">
    <link rel="stylesheet" href="<?= adminlte_asset('dist/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= adminlte_asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') ?>">
    <link rel="stylesheet" href="<?= adminlte_asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') ?>">
    <link rel="stylesheet" href="<?= adminlte_asset('plugins/sweetalert2/sweetalert2.min.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('assets/css/drms-admin.css') ?>">
    <?= $this->renderSection('styles') ?>
</head>
<body class="hold-transition sidebar-mini layout-fixed drms-admin-theme">
<div class="wrapper">

    <?= $this->include('partials/admin/navbar') ?>
    <?= $this->include('partials/admin/sidebar') ?>

    <?= $this->include('partials/admin/demo_banner') ?>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><?= esc($title ?? 'Dashboard') ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Home</a></li>
                            <?= $this->renderSection('breadcrumb') ?>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <?= $this->renderSection('content') ?>
            </div>
        </section>
    </div>

    <?= $this->include('partials/admin/footer') ?>
</div>

<!-- Scripts -->
<script src="<?= adminlte_asset('plugins/jquery/jquery.min.js') ?>"></script>
<script src="<?= adminlte_asset('plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= adminlte_asset('plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') ?>"></script>
<script src="<?= adminlte_asset('dist/js/adminlte.min.js') ?>"></script>
<script src="<?= adminlte_asset('plugins/chart.js/Chart.min.js') ?>"></script>
<script src="<?= adminlte_asset('plugins/datatables/jquery.dataTables.min.js') ?>"></script>
<script src="<?= adminlte_asset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') ?>"></script>
<script src="<?= adminlte_asset('plugins/datatables-responsive/js/dataTables.responsive.min.js') ?>"></script>
<script src="<?= adminlte_asset('plugins/datatables-responsive/js/responsive.bootstrap4.min.js') ?>"></script>
<script src="<?= adminlte_asset('plugins/sweetalert2/sweetalert2.min.js') ?>"></script>
<script>window.DRMS_API_BASE = <?= json_encode(rtrim(base_url('api/v1'), '/')) ?>; window.DRMS_API_SESSION_ONLY = true;</script>
<script src="<?= asset_url('assets/js/drms-admin.js') ?>"></script>
<?= $this->include('partials/admin/navbar_scripts') ?>
<?= $this->renderSection('scripts') ?>
</body>
</html>
