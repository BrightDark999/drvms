<?php $drms = $drms ?? drms_config(); ?>
<nav class="navbar navbar-expand-lg navbar-dark drms-nav sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?= base_url('/') ?>">
            <img src="<?= asset_url('assets/logo/baras_seal_xs.png') ?>" width="41" height="43" class="brand-icon mr-2" alt="Municipality of Baras seal">
            <span><strong><?= esc($drms->appName) ?></strong></span>
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#publicNav" aria-controls="publicNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="publicNav">
            <ul class="navbar-nav ml-auto align-items-lg-center">
                <li class="nav-item dropdown drms-nav-dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navExplore" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Explore
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navExplore">
                        <a class="dropdown-item" href="<?= base_url('/') ?>#modules">System modules</a>
                        <a class="dropdown-item" href="<?= base_url('/') ?>#public-services">Public services</a>
                        <a class="dropdown-item" href="<?= base_url('/') ?>#lgu-updates">LGU Facebook updates</a>
                        <a class="dropdown-item" href="<?= base_url('/') ?>#location">Map &amp; weather</a>
                    </div>
                </li>
                <li class="nav-item dropdown drms-nav-dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navCitizen" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Citizen tools
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navCitizen">
                        <a class="dropdown-item" href="<?= base_url('report') ?>">Report incident</a>
                        <a class="dropdown-item" href="<?= base_url('evac-centers') ?>">Evacuation centers</a>
                        <a class="dropdown-item" href="<?= url_to('register') ?>">Register</a>
                    </div>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('/') ?>#location">Map &amp; weather</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('about') ?>">About</a>
                </li>
                <?php if (auth()->loggedIn()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('profile') ?>">Profile</a>
                </li>
                <?php endif ?>
                <li class="nav-item">
                    <?php if (auth()->loggedIn()): ?>
                        <a class="btn btn-drms-light btn-sm ml-lg-2 mt-2 mt-lg-0" href="<?= base_url('admin/dashboard') ?>">
                            <i data-lucide="layout-dashboard" class="icon-inline"></i> Dashboard
                        </a>
                    <?php else: ?>
                        <a class="btn btn-drms-light btn-sm ml-lg-2 mt-2 mt-lg-0" href="<?= url_to('login') ?>">
                            <i data-lucide="log-in" class="icon-inline"></i> Login
                        </a>
                    <?php endif ?>
                </li>
            </ul>
        </div>
    </div>
</nav>
