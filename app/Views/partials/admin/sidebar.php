<?php
$drms = $drms ?? drms_config();
helper('drms');
$menu = [
    ['icon' => 'fa-tachometer-alt', 'label' => 'Dashboard', 'url' => 'admin/dashboard', 'match' => 'admin/dashboard', 'perm' => ['admin.access']],
    ['icon' => 'fa-users-cog', 'label' => 'User Management', 'url' => 'admin/users', 'match' => 'admin/users', 'perm' => ['users.*', 'admin.*']],
    ['icon' => 'fa-exclamation-triangle', 'label' => 'Incidents', 'url' => 'admin/incidents', 'match' => 'admin/incidents', 'perm' => ['incidents.*', 'admin.*']],
    ['icon' => 'fa-hands-helping', 'label' => 'Volunteers', 'url' => 'admin/volunteers', 'match' => 'admin/volunteers', 'perm' => ['volunteers.*', 'admin.*']],
    ['icon' => 'fa-random', 'label' => 'Volunteer Dispatch', 'url' => 'admin/matching', 'match' => 'admin/matching', 'perm' => ['volunteers.*', 'matching.access', 'admin.*']],
    ['icon' => 'fa-home', 'label' => 'Evacuation Centers', 'url' => 'admin/evacuation-centers', 'match' => 'admin/evacuation-centers', 'perm' => ['evacuation.*', 'admin.*']],
    ['icon' => 'fa-medkit', 'label' => 'Emergency Supplies', 'url' => 'admin/inventory', 'match' => 'admin/inventory', 'perm' => ['admin.access']],
    ['icon' => 'fa-images', 'label' => 'Homepage Carousel', 'url' => 'admin/home-carousel', 'match' => 'admin/home-carousel', 'perm' => ['admin.*']],
    ['icon' => 'fa-chart-bar', 'label' => 'Reports & Analytics', 'url' => 'admin/reports', 'match' => 'admin/reports', 'perm' => ['reports.*', 'admin.*']],
    ['icon' => 'fa-history', 'label' => 'Audit Trail', 'url' => 'admin/audit-trail', 'match' => 'admin/audit-trail', 'perm' => ['audit.*', 'admin.*']],
    ['icon' => 'fa-database', 'label' => 'Database Backup', 'url' => 'admin/backup', 'match' => 'admin/backup', 'perm' => ['admin.*']],
];
$uri = uri_string();
$sidebarUser = drms_current_user_display();
?>
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="<?= base_url('admin/dashboard') ?>" class="brand-link">
        <img src="<?= asset_url('assets/logo/baras_seal_xs.png') ?>" width="41" height="43" alt="Municipality of Baras seal" class="brand-image drms-municipal-seal elevation-2">
        <span class="brand-text font-weight-light drms-brand-erp">
            <?= esc($drms->appName) ?>
            <small class="d-block drms-brand-erp-sub"><?= esc($drms->appFullName) ?></small>
        </span>
    </a>
    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="<?= esc($sidebarUser['photo']) ?>" class="img-circle elevation-2" alt="<?= esc($sidebarUser['name']) ?>" style="width:2.1rem;height:2.1rem;object-fit:cover;">
            </div>
            <div class="info drms-user-info">
                <?php if (auth()->loggedIn()): ?>
                    <a href="<?= base_url('profile') ?>" class="d-block"><?= esc($sidebarUser['name']) ?></a>
                    <small class="text-muted"><?= esc($sidebarUser['subtitle']) ?></small>
                    <?php if ($sidebarUser['name'] !== $sidebarUser['username']): ?>
                        <small class="text-muted d-block">@<?= esc($sidebarUser['username']) ?></small>
                    <?php endif ?>
                <?php else: ?>
                    <a href="<?= url_to('login') ?>" class="d-block">Sign in</a>
                <?php endif; ?>
            </div>
        </div>
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-header"><?= auth()->user()->inGroup('volunteer') && ! drms_user_can('incidents.*', 'admin.*') ? 'MY PORTAL' : (auth()->user()->inGroup('evac_manager') && ! drms_user_can('incidents.*', 'admin.*') ? 'EVACUATION OPS' : 'OPERATIONS') ?></li>
                <?php foreach ($menu as $item):
                    if (! drms_user_can(...$item['perm'])) {
                        continue;
                    }
                    $label = $item['label'];
                    if ($item['match'] === 'admin/dashboard' && auth()->user()->inGroup('volunteer') && ! drms_user_can('incidents.*', 'admin.*')) {
                        $label = 'My Dashboard';
                    }
                    if ($item['match'] === 'admin/dashboard' && auth()->user()->inGroup('evac_manager') && ! drms_user_can('incidents.*', 'admin.*')) {
                        $label = 'Evac Dashboard';
                    }
                    if ($item['match'] === 'admin/matching' && auth()->user()->inGroup('volunteer') && ! drms_user_can('volunteers.*', 'admin.*')) {
                        $label = 'My Dispatch';
                    }
                    $active = $uri === $item['match']
                        || str_starts_with($uri, $item['match'] . '/')
                        || ($item['match'] === 'admin/dashboard' && $uri === 'admin');
                    ?>
                <li class="nav-item">
                    <a href="<?= base_url($item['url']) ?>"
                       class="nav-link <?= $active ? 'active' : '' ?>">
                        <i class="nav-icon fas <?= esc($item['icon']) ?>"></i>
                        <p><?= esc($label) ?></p>
                    </a>
                </li>
                <?php endforeach; ?>
                <li class="nav-header">PUBLIC</li>
                <li class="nav-item">
                    <a href="<?= base_url('report') ?>" class="nav-link" target="_blank">
                        <i class="nav-icon fas fa-bullhorn"></i>
                        <p>Report incident</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('evac-centers') ?>" class="nav-link" target="_blank">
                        <i class="nav-icon fas fa-map-marker-alt"></i>
                        <p>Evacuation centers</p>
                    </a>
                </li>
                <li class="nav-header">SYSTEM</li>
                <li class="nav-item">
                    <a href="<?= base_url('profile') ?>" class="nav-link">
                        <i class="nav-icon fas fa-user"></i>
                        <p>My Profile</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
