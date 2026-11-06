<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * DRMS – Disaster Relief Management System
 * Central config for labels, modules, and tech stack (used on homepage & docs).
 */
class Drms extends BaseConfig
{
    public string $appName        = 'DRMS';
    public string $appFullName   = 'Disaster Response & Volunteer Matching System';
    public string $appTagline    = 'Unified disaster response for LGUs — incidents, volunteers, relief, evacuation, and accountable operations.';

    /**
     * Modular integration labels (operations + relief logistics).
     *
     * @see details/ERP-INTEGRATION-STRATEGY.md
     */
    public string $erpOperationsModuleLabel = 'Response & Volunteer Management';
    public string $erpReliefModuleLabel      = 'Relief & Logistics Management';
    public string $appVersion    = '1.0.0-dev';
    public string $organization  = 'Local Government Unit';

    /**
     * Client demo hub at /demo — one-click logins and sample data (local / thesis only).
     */
    public bool $demoModeEnabled = true;

    /**
     * When true (session flag set from /demo), list/chart APIs use sample data and POST actions are simulated.
     */
    public bool $presentationData = true;

    /** Try seeded DB login first; fall back to offline demo session if DB is unavailable. */
    public bool $demoPreferDatabaseLogin = true;

    /** Shared password for database demo logins (see TEST_ACCOUNTS.md). */
    public string $demoPassword = 'Drms@2026';

    /**
     * One-click demo accounts (role key => credentials).
     *
     * @var array<string, array{label:string, username:string, email:string, group:string, redirect:string}>
     */
    public array $demoAccounts = [
        'admin' => [
            'label'    => 'Admin',
            'username' => 'admin',
            'email'    => 'admin@drms.local',
            'group'    => 'admin',
            'redirect' => 'admin/dashboard',
        ],
        'mdrrmo' => [
            'label'    => 'MDRRMO / LGU Staff',
            'username' => 'mdrrmo',
            'email'    => 'mdrrmo@drms.local',
            'group'    => 'mdrrmo',
            'redirect' => 'admin/dashboard',
        ],
        'volunteer' => [
            'label'    => 'Volunteer',
            'username' => 'volunteer',
            'email'    => 'volunteer@drms.local',
            'group'    => 'volunteer',
            'redirect' => 'admin/dashboard',
        ],
        // 'donor' => [
        //     'label'    => 'Donor',
        //     'username' => 'donor',
        //     'email'    => 'donor@drms.local',
        //     'group'    => 'donor',
        //     'redirect' => 'admin/donations',
        // ],
        'evac' => [
            'label'    => 'Evacuation Manager',
            'username' => 'evac_manager',
            'email'    => 'evac@drms.local',
            'group'    => 'evac_manager',
            'redirect' => 'admin/evacuation-centers',
        ],
    ];

    /**
     * Primary service area – used on the public homepage for map & weather (Municipality of Baras, Rizal).
     */
    public string $serviceMunicipality = 'Municipality of Baras';
    public string $serviceProvince     = 'Rizal';
    public string $serviceCountry      = 'Philippines';

    /** Official LGU Facebook page — linked from homepage social gallery. */
    public string $facebookPageUrl  = 'https://www.facebook.com/LGUBarasRizal';
    public string $facebookPageName = 'LGU Baras Rizal';

    /** Max items in the public 3-column gallery (3 × 9 grid). */
    public int $socialGalleryLimit = 27;

    /** Show official Facebook Page timeline embed on the public homepage. */
    public bool $facebookPageEmbedEnabled = true;

    /** Municipal hall / centroid coordinates for map pin & Open-Meteo */
    public float $officeLatitude  = 14.5171;
    public float $officeLongitude = 121.2672;

    /** Google Maps embed zoom (12 ≈ Baras municipality; 14 ≈ street level) */
    public int $mapZoomGoogle = 12;

    /** Windy embed zoom (10–11 ≈ Baras & nearby Rizal; 8 ≈ wider CALABARZON) */
    public int $mapZoomWindy = 11;

    /** Admin Leaflet incidents map — 12 = Baras municipality; 13–14 = barangay level */
    public int $mapZoomLeaflet = 12;

    /**
     * Municipality of Baras, Rizal — map bounds [[south, west], [north, east]] (lat, lng).
     * Keeps the dashboard map focused on the LGU service area.
     */
    /**
     * Baras municipal bounding box [[south, west], [north, east]] — from OSM boundary.
     * @see public/assets/geo/baras-rizal.geojson
     */
    public array $serviceAreaBounds = [
        [14.4956, 121.2433],
        [14.6367, 121.3263],
    ];

    /**
     * Core operations modules (DRMS).
     *
     * @var list<array{icon:string, title:string, description:string, api?:string}>
     */
    public array $erpModuleGroup1 = [
        ['icon' => 'megaphone', 'title' => 'Incident reporting', 'description' => 'Public and admin reporting, verification, GIS map, media upload.', 'api' => '/api/v1/incidents'],
        ['icon' => 'users', 'title' => 'Volunteer registration', 'description' => 'Skills, ID verification, availability, barangay linkage.', 'api' => '/api/v1/volunteers'],
        ['icon' => 'git-merge', 'title' => 'Automated volunteer matching', 'description' => 'Skill-aware ranking, dispatch console, bulk and auto-assign.', 'api' => '/admin/matching'],
        ['icon' => 'users-cog', 'title' => 'User & role management', 'description' => 'Account approval, Shield roles, REST API for users and roles catalog.', 'api' => '/api/v1/users'],
        ['icon' => 'layout-dashboard', 'title' => 'Operations dashboard', 'description' => 'Live KPIs, incident map, heatmap, alerts.', 'api' => '/api/v1/stats'],
        ['icon' => 'bell', 'title' => 'Notifications', 'description' => 'Email/SMS queue and deployment alerts across all modules.', 'api' => '—'],
    ];

    /**
     * Relief & logistics modules — integrated via REST APIs.
     *
     * @var list<array{icon:string, title:string, description:string, api?:string, bridge?:bool}>
     */
    public array $erpModuleGroup2 = [
        ['icon' => 'package', 'title' => 'Relief monitoring', 'description' => 'Distributions, beneficiary tracking, stock movements.', 'api' => '/api/v1/integrations/relief/*'],
        ['icon' => 'heart-handshake', 'title' => 'Donation monitoring', 'description' => 'Donor records, cash/in-kind logs, allocation to incidents.', 'api' => '/api/donations'],
        ['icon' => 'home', 'title' => 'Evacuation management', 'description' => 'Shelter capacity, family registration, occupancy maps.', 'api' => '/api/v1/evacuation-centers', 'bridge' => true],
        ['icon' => 'warehouse', 'title' => 'Shared inventory', 'description' => 'Master stock database used by both groups (single source of truth).', 'api' => '/api/inventory'],
        ['icon' => 'bar-chart-3', 'title' => 'Relief dashboard', 'description' => 'Analytics for logistics officers and warehouse staff.', 'api' => '—'],
    ];

    /**
     * Shared master data (joint ERP data dictionary — single DB or synced via API).
     *
     * @var list<array{table:string, used_by:string}>
     */
    public array $erpSharedMasterData = [
        ['table' => 'users', 'used_by' => 'All modules (SSO / RBAC)'],
        ['table' => 'roles', 'used_by' => 'All modules'],
        ['table' => 'notifications', 'used_by' => 'All modules'],
        ['table' => 'inventory', 'used_by' => 'All modules'],
        ['table' => 'incidents', 'used_by' => 'Operations + Relief'],
        ['table' => 'audit_logs', 'used_by' => 'All modules'],
        ['table' => 'volunteers', 'used_by' => 'Operations'],
        ['table' => 'tasks', 'used_by' => 'Operations'],
        ['table' => 'evacuation_centers', 'used_by' => 'Relief & logistics'],
        ['table' => 'donations', 'used_by' => 'Relief & logistics'],
    ];

    /**
     * Integrated workflow after module connection (ERP Phase 6).
     *
     * @var list<array{step:string, title:string, owner:string}>
     */
    public array $erpIntegratedWorkflow = [
        ['step' => '1', 'title' => 'Incident reported', 'owner' => 'Operations'],
        ['step' => '2', 'title' => 'Volunteer matching & deploy', 'owner' => 'Operations'],
        ['step' => '3', 'title' => 'Task assignment', 'owner' => 'Operations'],
        ['step' => '4', 'title' => 'Inventory availability check', 'owner' => 'Relief'],
        ['step' => '5', 'title' => 'Donation allocation', 'owner' => 'Relief'],
        ['step' => '6', 'title' => 'Relief distribution', 'owner' => 'Relief'],
        ['step' => '7', 'title' => 'Evacuation monitoring', 'owner' => 'Relief'],
        ['step' => '8', 'title' => 'Notifications sent', 'owner' => 'Platform'],
        ['step' => '9', 'title' => 'Dashboard & audit logs', 'owner' => 'Platform'],
    ];

    /**
     * Role access by ERP module (SSO target state).
     *
     * @var list<array{role:string, operations:bool, relief:bool}>
     */
    public array $erpRoleAccessMatrix = [
        ['role' => 'Admin', 'operations' => true, 'relief' => true],
        ['role' => 'DRRM Officer', 'operations' => true, 'relief' => true],
        ['role' => 'Volunteer', 'operations' => true, 'relief' => false],
        ['role' => 'Donor', 'operations' => false, 'relief' => true],
        ['role' => 'Warehouse Staff', 'operations' => true, 'relief' => true],
        ['role' => 'Evacuation Manager', 'operations' => false, 'relief' => true],
    ];

    /** Live modules in this deployment */
    public array $modules = [
        [
            'icon'        => 'layout-dashboard',
            'title'       => 'Operations Dashboard',
            'description' => 'Real-time statistics, incident map, heatmap, and operational alerts.',
            'erp_module'  => 'operations',
        ],
        [
            'icon'        => 'alert-triangle',
            'title'       => 'Disaster Incident Management',
            'description' => 'Reporting, verification, severity, map location, and status tracking.',
            'erp_module'  => 'operations',
        ],
        [
            'icon'        => 'users',
            'title'       => 'Volunteer Management',
            'description' => 'Registration, skills, availability, deployment tracking.',
            'erp_module'  => 'operations',
        ],
        [
            'icon'        => 'git-merge',
            'title'       => 'Volunteer Dispatch',
            'description' => 'Skill-aware matching, map, staffing fulfillment, bulk deploy.',
            'erp_module'  => 'operations',
        ],
        [
            'icon'        => 'home',
            'title'       => 'Evacuation Center Management',
            'description' => 'Capacity monitoring, family registration, occupancy maps.',
            'erp_module'  => 'relief',
        ],
        [
            'icon'        => 'package',
            'title'       => 'Emergency Supplies Inventory',
            'description' => 'Medical and emergency stock from the shared inventory master (relief system holds the full catalog).',
            'erp_module'  => 'operations',
        ],
        [
            'icon'        => 'users-cog',
            'title'       => 'User Management',
            'description' => 'Account approval, roles, ID verification, and access control.',
            'erp_module'  => 'platform',
        ],
        [
            'icon'        => 'bell',
            'title'       => 'Notification System',
            'description' => 'Email/SMS queue and deployment alerts.',
            'erp_module'  => 'platform',
        ],
        [
            'icon'        => 'file-bar-chart',
            'title'       => 'Reports & Analytics',
            'description' => 'Charts, live KPIs, PDF/Excel export.',
            'erp_module'  => 'operations',
        ],
        [
            'icon'        => 'shield-check',
            'title'       => 'Audit Trail',
            'description' => 'Append-only activity log across the platform.',
            'erp_module'  => 'platform',
        ],
    ];

    /** User roles (CodeIgniter Shield) */
    public array $roles = [
        'admin'             => 'Admin',
        'mdrrmo'            => 'MDRRMO / LGU Staff',
        'volunteer'         => 'Volunteer',
        'resident'          => 'Resident',
        'donor'             => 'Donor',
        'evac_manager'      => 'Evacuation Center Manager',
    ];

    /**
     * Libraries & plugins (local AdminLTE bundle + planned stack).
     * Shown on public homepage – no extra install required for listed local assets.
     */
    public array $techStack = [
        'backend' => [
            ['name' => 'CodeIgniter 4', 'note' => 'MVC, REST API, PHP 8+'],
            ['name' => 'MySQL / PostgreSQL', 'note' => 'Relational database'],
            ['name' => 'CodeIgniter Shield', 'note' => 'Auth & RBAC (planned)'],
        ],
        'frontend' => [
            ['name' => 'AdminLTE 3', 'note' => 'Dashboard template (local)'],
            ['name' => 'Bootstrap 4', 'note' => 'Bundled with AdminLTE'],
            ['name' => 'jQuery', 'note' => 'DOM & AJAX (local)'],
            ['name' => 'DataTables', 'note' => 'Server-side tables (local)'],
            ['name' => 'Chart.js', 'note' => 'Dashboard charts (local)'],
            ['name' => 'SweetAlert2', 'note' => 'Toasts & confirmations (local)'],
            ['name' => 'Font Awesome', 'note' => 'Admin icons (local)'],
            ['name' => 'Lucide Icons', 'note' => 'Public homepage icons (CDN)'],
        ],
        'optional' => [
            ['name' => 'Windy embed', 'note' => 'Homepage weather map (embed.windy.com, no key)'],
            ['name' => 'Windy Map Forecast API', 'note' => 'Custom Leaflet map – requires api.windy.com key'],
            ['name' => 'Leaflet.js', 'note' => 'Interactive maps – confirm before add'],
            ['name' => 'PWA / Service Worker', 'note' => 'Offline support – confirm before add'],
        ],
    ];

    public function __construct()
    {
        parent::__construct();

        // filter_var(null, FILTER_VALIDATE_BOOLEAN) is false — must not treat "unset" env as disabled.
        $this->demoModeEnabled         = $this->envBool('drms.demoMode', $this->demoModeEnabled);
        $this->presentationData        = $this->envBool('drms.presentationData', $this->presentationData);
        $this->demoPreferDatabaseLogin = $this->envBool('drms.demoPreferDatabaseLogin', $this->demoPreferDatabaseLogin);
        $demoPass                      = env('drms.demoPassword');
        if (is_string($demoPass) && $demoPass !== '') {
            $this->demoPassword = $demoPass;
        }
    }

    private function envBool(string $key, bool $default): bool
    {
        $value = env($key);
        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
