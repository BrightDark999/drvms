<?php
$drms = $drms ?? drms_config();
$sidebarUser = drms_current_user_display();
?>
<nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom-0">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="<?= base_url('/') ?>" class="nav-link" target="_blank"><i class="fas fa-home mr-1"></i> Public Site</a>
        </li>
    </ul>
    <ul class="navbar-nav ml-auto">
        <?php if (auth()->loggedIn()): ?>
        <li class="nav-item dropdown" id="navNotifDropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-bell"></i>
                <span class="badge badge-warning navbar-badge" id="navNotifCount" style="display:none">0</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" id="navNotifMenu">
                <span class="dropdown-item dropdown-header" id="navNotifHeader">Notifications</span>
                <div class="dropdown-divider"></div>
                <div id="navNotifItems"><span class="dropdown-item text-muted">Loading…</span></div>
            </div>
        </li>
        <?php endif ?>
        <li class="nav-item">
            <a class="nav-link" href="<?= base_url('profile') ?>" title="Profile">
                <img src="<?= esc($sidebarUser['photo']) ?>" alt="" class="img-circle mr-1" style="width:28px;height:28px;object-fit:cover;">
                <span class="d-none d-md-inline"><?= esc($sidebarUser['name']) ?></span>
            </a>
        </li>
        <li class="nav-item">
            <?php if (auth()->loggedIn()): ?>
                <a class="nav-link" href="<?= url_to('logout') ?>" title="Sign out">Logout</a>
            <?php else: ?>
                <a class="nav-link" href="<?= url_to('login') ?>">Login</a>
            <?php endif; ?>
        </li>
    </ul>
</nav>
