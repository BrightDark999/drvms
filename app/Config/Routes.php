<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// -------------------------------------------------------------------------
// Public routes
// -------------------------------------------------------------------------
$routes->get('/', 'Home::index');
$routes->get('about', 'Home::about');
$routes->get('api/weather/current', 'Home::weatherCurrent');

// Client demo hub (one-click role logins + sample data)
$routes->get('demo', 'Demo::index');
$routes->get('demo/login/(:segment)', 'Demo::loginAs/$1');
$routes->get('demo/logout', 'Demo::logout');

$routes->get('captcha/image', '\App\Controllers\CaptchaController::image');

// Public citizen features
$routes->get('report', '\App\Controllers\Public\Report::index');
$routes->post('report/submit', '\App\Controllers\Public\Report::submit');
$routes->get('report/success/(:num)', '\App\Controllers\Public\Report::success/$1');
$routes->get('evac-centers', '\App\Controllers\Public\Evacuation::index');
$routes->post('evac-centers/register-family', '\App\Controllers\Public\Evacuation::registerFamily');
$routes->post('evac-centers/check-in-family', '\App\Controllers\Public\Evacuation::checkInFamily');
$routes->get('api/evac/nearest', '\App\Controllers\Public\Evacuation::nearestApi');

$routes->group('profile', ['filter' => 'session'], static function ($routes) {
    $routes->get('/', 'Profile::index');
    $routes->post('update', 'Profile::update');
    $routes->post('upload-id', 'Profile::uploadId');
    $routes->post('upload-photo', 'Profile::uploadPhoto');
    $routes->post('availability', 'Profile::updateAvailability');
    $routes->post('request-skills', 'Profile::requestSkills');
    $routes->post('group-member/add', 'Profile::addGroupMember');
    $routes->post('group-member/delete/(:num)', 'Profile::deleteGroupMember/$1');
    $routes->get('notifications', 'Profile::notifications');
    $routes->post('notifications/read/(:num)', 'Profile::markNotificationRead/$1');
});

// Web cron (secret key) — for shared hosting without CLI cron
$routes->get('cron/notifications', '\App\Controllers\Cron\Notifications::process');

// -------------------------------------------------------------------------
// Shield authentication (login, logout, register, etc.)
// -------------------------------------------------------------------------
service('auth')->routes($routes, ['except' => ['register', 'login']]);
$routes->group('', ['namespace' => 'App\Controllers\Auth'], static function ($routes) {
    $routes->get('register', 'RegisterController::registerView', ['as' => 'register']);
    $routes->post('register', 'RegisterController::registerAction');
    $routes->get('login', 'LoginController::loginView', ['as' => 'login']);
    $routes->post('login', 'LoginController::loginAction');
});
$routes->get('auth/pending-approval', '\App\Controllers\Auth\PendingApproval::index', ['as' => 'pending-approval']);

// -------------------------------------------------------------------------
// Public API
// -------------------------------------------------------------------------
$routes->get('api', '\App\Controllers\Api\V1\PublicInventoryApi::docs');
$routes->group('api/v1', ['namespace' => 'App\Controllers\Api\V1'], static function ($routes) {
    $routes->get('health', 'Health::index');
    $routes->get('public/inventory', 'PublicInventoryApi::index');
    $routes->get('public/inventory/(:num)', 'PublicInventoryApi::show/$1');
    $routes->post('auth/login', 'AuthApi::login');
    $routes->post('incidents/report', 'IncidentsApi::publicReport');
    $routes->get('reports/dashboard-chart', 'ReportsApi::dashboardChart');
    $routes->get('reports/analytics', 'ReportsApi::analytics');
});

// -------------------------------------------------------------------------
// Admin (AdminLTE) — requires Shield permission: admin.access
// -------------------------------------------------------------------------
$routes->group('admin', ['namespace' => 'App\Controllers\Admin', 'filter' => ['permission:admin.access', 'drms.portal']], static function ($routes) {
    $routes->get('/', 'Dashboard::index');
    $routes->get('dashboard/incidents-map', 'Dashboard::incidentsMap');
    $routes->get('dashboard/incidents-heatmap', 'Dashboard::incidentsHeatmap');
    $routes->get('dashboard/incidents-chart', 'Dashboard::incidentsChart');
    $routes->get('dashboard/live', 'Dashboard::liveStats');
    $routes->get('dashboard/portal-live', 'Dashboard::portalLive');
    $routes->get('dashboard', 'Dashboard::index');

    $routes->get('incidents', 'Incidents::index');
    $routes->get('incidents/data', 'Incidents::data');
    $routes->get('incidents/show/(:num)', 'Incidents::show/$1');
    $routes->post('incidents/store', 'Incidents::store');
    $routes->post('incidents/update/(:num)', 'Incidents::update/$1');
    $routes->post('incidents/delete/(:num)', 'Incidents::delete/$1');
    $routes->post('incidents/verify/(:num)', 'Incidents::verify/$1');
    $routes->post('incidents/media/(:num)', 'Incidents::uploadMedia/$1');

    $routes->post('matching/deploy', 'Matching::deploy');
    $routes->post('matching/deploy-bulk', 'Matching::deployBulk');
    $routes->post('matching/auto-assign', 'Matching::autoAssign');

    $routes->get('volunteers', 'Volunteers::index');
    $routes->get('volunteers/data', 'Volunteers::data');
    $routes->get('volunteers/show/(:num)', 'Volunteers::show/$1');
    $routes->post('volunteers/store', 'Volunteers::store');
    $routes->post('volunteers/update/(:num)', 'Volunteers::update/$1');
    $routes->post('volunteers/delete/(:num)', 'Volunteers::delete/$1');
    $routes->post('volunteers/verify-id/(:num)', 'Volunteers::verifyId/$1');
    $routes->post('volunteers/reject-id/(:num)', 'Volunteers::rejectId/$1');
    $routes->post('volunteers/upload-id/(:num)', 'Volunteers::uploadId/$1');
    $routes->get('volunteers/skill-requests', 'Volunteers::skillRequests');
    $routes->post('volunteers/approve-skills/(:num)', 'Volunteers::approveSkills/$1');
    $routes->post('volunteers/reject-skills/(:num)', 'Volunteers::rejectSkills/$1');
    $routes->post('volunteers/group-member/approve/(:num)', 'Volunteers::approveMember/$1');
    $routes->post('volunteers/group-member/reject/(:num)', 'Volunteers::rejectMember/$1');

    $routes->get('matching', 'Matching::index');

    $routes->get('inventory', 'Inventory::index');

    $routes->get('home-carousel', 'HomeCarousel::index');
    $routes->get('home-carousel/list', 'HomeCarousel::list');
    $routes->get('home-carousel/show/(:num)', 'HomeCarousel::show/$1');
    $routes->post('home-carousel/store', 'HomeCarousel::store');
    $routes->post('home-carousel/update/(:num)', 'HomeCarousel::update/$1');
    $routes->post('home-carousel/delete/(:num)', 'HomeCarousel::delete/$1');

    $routes->get('social-gallery', 'SocialGallery::index');
    $routes->get('social-gallery/list', 'SocialGallery::list');
    $routes->get('social-gallery/show/(:num)', 'SocialGallery::show/$1');
    $routes->post('social-gallery/store', 'SocialGallery::store');
    $routes->post('social-gallery/update/(:num)', 'SocialGallery::update/$1');
    $routes->post('social-gallery/delete/(:num)', 'SocialGallery::delete/$1');

    $routes->get('evacuation-centers/families/(:num)', 'EvacuationCenters::families/$1');
    $routes->post('evacuation-centers/families/(:num)', 'EvacuationCenters::registerFamily/$1');
    $routes->post('evacuation-centers/families/(:num)/checkout/(:num)', 'EvacuationCenters::checkoutFamily/$1/$2');
    $routes->get('evacuation-centers', 'EvacuationCenters::index');
    $routes->get('evacuation-centers/summary', 'EvacuationCenters::summary');
    $routes->get('evacuation-centers/data', 'EvacuationCenters::data');
    $routes->get('evacuation-centers/show/(:num)', 'EvacuationCenters::show/$1');
    $routes->post('evacuation-centers/store', 'EvacuationCenters::store');
    $routes->post('evacuation-centers/update/(:num)', 'EvacuationCenters::update/$1');
    $routes->post('evacuation-centers/delete/(:num)', 'EvacuationCenters::delete/$1');

    $routes->get('reports', 'Reports::index');
    $routes->get('reports/stats', 'Reports::stats');
    $routes->get('reports/analytics', 'Reports::analytics');
    $routes->get('reports/export/(:segment)', 'Reports::export/$1');

    $routes->get('audit-trail', 'AuditTrail::index');
    $routes->get('audit-trail/data', 'AuditTrail::data');
    $routes->get('audit-trail/summary', 'AuditTrail::summary');
    $routes->get('audit-trail/timeline', 'AuditTrail::timeline');
    $routes->get('audit-trail/show/(:num)', 'AuditTrail::show/$1');
    $routes->get('audit-trail/export', 'AuditTrail::export');

    $routes->get('users', 'Users::index');
    $routes->get('users/data', 'Users::data');
    $routes->get('users/show/(:num)', 'Users::show/$1');
    $routes->post('users/store', 'Users::store');
    $routes->post('users/update/(:num)', 'Users::update/$1');
    $routes->post('users/approve/(:num)', 'Users::approve/$1');
    $routes->post('users/reject/(:num)', 'Users::reject/$1');
    $routes->post('users/verify-id/(:num)', 'Users::verifyId/$1');
    $routes->post('users/delete/(:num)', 'Users::delete/$1');

    $routes->get('backup', 'Backup::index');
    $routes->get('backup/download', 'Backup::download');
});

// -------------------------------------------------------------------------
// Protected REST API — session, access token, or JWT (Shield chain auth)
// -------------------------------------------------------------------------
$routes->group('api/v1', ['namespace' => 'App\Controllers\Api\V1', 'filter' => 'apichain'], static function ($routes) {
    $routes->get('auth/me', 'AuthApi::me');
});

$routes->group('api/v1', ['namespace' => 'App\Controllers\Api\V1', 'filter' => ['apichain', 'apipermission:admin.access']], static function ($routes) {
    $routes->get('stats', 'DashboardApi::overview');
    $routes->get('dashboard/live', 'DashboardApi::live');
    $routes->get('dashboard/incidents-map', 'DashboardApi::incidentsMap');

    $routes->get('incidents', 'IncidentsApi::index');
    $routes->get('incidents/(:num)', 'IncidentsApi::show/$1');
    $routes->post('incidents', 'IncidentsApi::create');
    $routes->put('incidents/(:num)', 'IncidentsApi::update/$1');
    $routes->patch('incidents/(:num)', 'IncidentsApi::update/$1');
    $routes->delete('incidents/(:num)', 'IncidentsApi::delete/$1');
    $routes->post('incidents/(:num)/verify', 'IncidentsApi::verify/$1');
    $routes->post('incidents/(:num)/media', 'IncidentsApi::uploadMedia/$1');

    $routes->get('inventory', 'InventoryApi::index');
    $routes->get('inventory/(:num)', 'InventoryApi::show/$1');
    $routes->post('inventory', 'InventoryApi::create');
    $routes->put('inventory/(:num)', 'InventoryApi::update/$1');
    $routes->patch('inventory/(:num)', 'InventoryApi::update/$1');
    $routes->delete('inventory/(:num)', 'InventoryApi::delete/$1');
    $routes->post('inventory/(:num)/adjust', 'InventoryApi::adjust/$1');

    $routes->get('volunteers', 'VolunteersApi::index');
    $routes->get('volunteers/(:num)', 'VolunteersApi::show/$1');
    $routes->post('volunteers', 'VolunteersApi::create');
    $routes->put('volunteers/(:num)', 'VolunteersApi::update/$1');
    $routes->patch('volunteers/(:num)', 'VolunteersApi::update/$1');
    $routes->delete('volunteers/(:num)', 'VolunteersApi::delete/$1');
    $routes->post('volunteers/(:num)/verify-id', 'VolunteersApi::verifyId/$1');
    $routes->post('volunteers/(:num)/reject-id', 'VolunteersApi::rejectId/$1');
    $routes->post('volunteers/(:num)/id-document', 'VolunteersApi::uploadIdDocument/$1');

    $routes->get('evacuation-centers/summary', 'EvacuationCentersApi::summary');
    $routes->get('evacuation-centers', 'EvacuationCentersApi::index');
    $routes->post('evacuation-centers', 'EvacuationCentersApi::create');
    $routes->get('evacuation-centers/(:num)/families', 'EvacuationCentersApi::families/$1');
    $routes->post('evacuation-centers/(:num)/families', 'EvacuationCentersApi::registerFamily/$1');
    $routes->post('evacuation-centers/(:num)/families/(:num)/checkout', 'EvacuationCentersApi::checkoutFamily/$1/$2');
    $routes->get('evacuation-centers/(:num)', 'EvacuationCentersApi::show/$1');
    $routes->put('evacuation-centers/(:num)', 'EvacuationCentersApi::update/$1');
    $routes->patch('evacuation-centers/(:num)', 'EvacuationCentersApi::update/$1');
    $routes->delete('evacuation-centers/(:num)', 'EvacuationCentersApi::delete/$1');

    $routes->get('reports/analytics', 'ReportsApi::analytics');

    $routes->get('audit-trail', 'AuditTrailApi::index');
    $routes->get('audit-trail/summary', 'AuditTrailApi::summary');
    $routes->get('audit-trail/timeline', 'AuditTrailApi::timeline');
    $routes->get('audit-trail/(:num)', 'AuditTrailApi::show/$1');

    $routes->get('matching/queue', 'MatchingApi::queue');
    $routes->get('matching/incidents/(:num)', 'MatchingApi::recommend/$1');
    $routes->post('matching/deploy', 'MatchingApi::deploy');
    $routes->post('matching/deploy-bulk', 'MatchingApi::deployBulk');
    $routes->post('matching/auto-assign', 'MatchingApi::autoAssign');

    $routes->get('roles', 'RolesApi::index');
    $routes->get('integrations/relief/status', 'IntegrationsApi::reliefStatus');
    $routes->get('integrations/relief/inventory', 'IntegrationsApi::reliefInventory');
    $routes->get('integrations/relief/distributions', 'IntegrationsApi::reliefDistributions');
});

$routes->group('api/v1', ['namespace' => 'App\Controllers\Api\V1', 'filter' => 'apichain'], static function ($routes) {
    $routes->get('users', 'UsersApi::index');
    $routes->get('users/(:num)', 'UsersApi::show/$1');
    $routes->post('users', 'UsersApi::create');
    $routes->put('users/(:num)', 'UsersApi::update/$1');
    $routes->patch('users/(:num)', 'UsersApi::update/$1');
    $routes->delete('users/(:num)', 'UsersApi::delete/$1');
    $routes->post('users/(:num)/approve', 'UsersApi::approve/$1');
    $routes->post('users/(:num)/reject', 'UsersApi::reject/$1');
    $routes->post('users/(:num)/verify-id', 'UsersApi::verifyId/$1');
});
