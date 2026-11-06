<?php

/**
 * DRMS Helper – quick access to system configuration.
 */

if (! function_exists('drms_config')) {
    function drms_config(): \Config\Drms
    {
        return config('Drms');
    }
}

if (! function_exists('drms_brand_label')) {
    function drms_brand_label(): string
    {
        return drms_config()->appName;
    }
}

if (! function_exists('drms_title_suffix')) {
    function drms_title_suffix(): string
    {
        return drms_config()->appName;
    }
}

if (! function_exists('drms_erp_relief_connected')) {
    /** True when relief & logistics API is configured in .env. */
    function drms_erp_relief_connected(): bool
    {
        $integrations = config('Integrations');

        return $integrations->reliefApiEnabled && $integrations->reliefApiBaseUrl !== '';
    }
}

if (! function_exists('drms_erp_group2_connected')) {
    /** @deprecated Use drms_erp_relief_connected() */
    function drms_erp_group2_connected(): bool
    {
        return drms_erp_relief_connected();
    }
}

if (! function_exists('drms_runtime_ready')) {
    /**
     * Bootstrap readiness gate — used by layouts and services.
     * Kept as a compatibility helper now that bundled runtime expiry is disabled.
     */
    function drms_runtime_ready(): bool
    {
        return true;
    }
}

if (! function_exists('drms_presentation_active')) {
    /** True when /demo session uses sample data and simulated saves. */
    function drms_presentation_active(): bool
    {
        if (! drms_config()->presentationData) {
            return false;
        }

        return session()->get('drms_presentation') === true;
    }
}

if (! function_exists('drms_is_offline_demo_user')) {
    function drms_is_offline_demo_user(): bool
    {
        return is_array(session('drms_demo_user'));
    }
}

if (! function_exists('drms_use_presentation_fixtures')) {
    /**
     * Separate fake JSON rows — only when demo is offline and MySQL is not used.
     * With a seeded database, demo mode shows real sample rows and blocks writes only.
     */
    function drms_use_presentation_fixtures(): bool
    {
        return drms_presentation_active() && drms_is_offline_demo_user();
    }
}

if (! function_exists('drms_barangay_list')) {
    /**
     * Barangays for forms — uses demo list in presentation mode when DB is empty.
     *
     * @return list<array<string, mixed>>
     */
    function drms_barangay_list(): array
    {
        try {
            return model(\App\Models\BarangayModel::class)->orderBy('name', 'ASC')->findAll();
        } catch (\Throwable $e) {
            log_message('error', 'drms_barangay_list: ' . $e->getMessage());

            return \App\Services\PresentationFixtures::barangays();
        }
    }
}

if (! function_exists('drms_user_can')) {
    function drms_user_can(string ...$permissions): bool
    {
        if (! auth()->loggedIn()) {
            return false;
        }
        $user = auth()->user();
        foreach ($permissions as $perm) {
            if ($user->can($perm)) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('drms_can_dispatch_volunteers')) {
    /** MDRRMO / LGU staff — deploy and manage the volunteer pool. */
    function drms_can_dispatch_volunteers(): bool
    {
        return drms_user_can('volunteers.*', 'admin.*');
    }
}

if (! function_exists('drms_is_volunteer_dispatch_view')) {
    /** Volunteer self-service: view incidents and personal fit, not full dispatch console. */
    function drms_is_volunteer_dispatch_view(): bool
    {
        return auth()->loggedIn()
            && auth()->user()->inGroup('volunteer')
            && drms_user_can('matching.access')
            && ! drms_can_dispatch_volunteers();
    }
}

if (! function_exists('drms_current_user_display')) {
    /**
     * Display name + avatar for admin sidebar / navbar (from profile, volunteer, or username).
     *
     * @return array{name: string, username: string, photo: string, subtitle: string}
     */
    function drms_current_user_display(): array
    {
        $defaultPhoto = adminlte_asset('dist/img/user2-160x160.jpg');

        if (! auth()->loggedIn()) {
            return [
                'name'     => 'Sign in',
                'username' => '',
                'photo'    => $defaultPhoto,
                'subtitle' => '',
            ];
        }

        $user     = auth()->user();
        $userId   = (int) auth()->id();
        $username = (string) ($user->username ?? 'User');
        $name     = $username;
        $photo    = $defaultPhoto;

        try {
            $profile = (new \App\Services\UserProfileService())->getProfileRow($userId);
            if ($profile !== null) {
                $fullName = trim((string) ($profile['full_name'] ?? ''));
                if ($fullName !== '') {
                    $name = $fullName;
                }
                $path = trim((string) ($profile['profile_photo'] ?? ''));
                if ($path !== '') {
                    $photo = base_url(ltrim($path, '/'));
                }
            }

            $volunteer = model(\App\Models\VolunteerModel::class)->where('user_id', $userId)->first();
            if ($name === $username && $volunteer) {
                $volName = trim((string) ($volunteer['full_name'] ?? ''));
                if ($volName !== '') {
                    $name = $volName;
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'drms_current_user_display: ' . $e->getMessage());
        }

        $subtitle = 'Operations access';
        if (drms_is_limited_portal_user()) {
            $subtitle = auth()->user()->inGroup('volunteer')
                ? 'Volunteer portal'
                : (auth()->user()->inGroup('evac_manager') ? 'Evacuation manager' : 'Portal access');
        }

        return [
            'name'     => $name,
            'username' => $username,
            'photo'    => $photo,
            'subtitle' => $subtitle,
        ];
    }
}

if (! function_exists('drms_login_redirect')) {
    /** Post-login destination based on role permissions. */
    function drms_login_redirect(): string
    {
        if (! auth()->loggedIn()) {
            return base_url('login');
        }

        $user = auth()->user();
        if ($user !== null && $user->can('admin.access')) {
            return base_url('admin/dashboard');
        }

        return base_url('profile');
    }
}

if (! function_exists('drms_is_limited_portal_user')) {
    function drms_is_limited_portal_user(): bool
    {
        if (! auth()->loggedIn()) {
            return false;
        }
        $user = auth()->user();

        return $user->can('admin.access')
            && $user->inGroup('volunteer', 'donor')
            && ! $user->inGroup('admin', 'mdrrmo', 'evac_manager');
    }
}

if (! function_exists('drms_evac_manager_center_id')) {
    function drms_evac_manager_center_id(?int $userId = null): ?int
    {
        if (! auth()->loggedIn()) {
            return null;
        }

        $userId = $userId ?? (int) auth()->id();
        if ($userId < 1) {
            return null;
        }

        $row = model(\App\Models\UserProfileModel::class)->where('user_id', $userId)->first();
        $id  = (int) ($row['assigned_evacuation_center_id'] ?? 0);

        return $id > 0 ? $id : null;
    }
}

if (! function_exists('drms_evac_can_manage_center')) {
    function drms_evac_can_manage_center(int $centerId): bool
    {
        if (! drms_user_can('evacuation.*', 'admin.*')) {
            return false;
        }

        if (! auth()->user()->inGroup('evac_manager') || drms_user_can('incidents.*', 'admin.*')) {
            return true;
        }

        $assigned = drms_evac_manager_center_id();

        return $assigned === null || $assigned === $centerId;
    }
}

if (! function_exists('drms_id_is_verified')) {
    function drms_id_is_verified(?string $status): bool
    {
        $status = strtolower(trim((string) ($status ?? 'none')));

        return in_array($status, ['verified', 'approved'], true);
    }
}

if (! function_exists('drms_id_verification_badge')) {
    /**
     * @return array{badge: string, label: string, status: string, is_verified: bool}
     */
    function drms_id_verification_badge(?string $status): array
    {
        $status = strtolower(trim((string) ($status ?? 'none')));
        if ($status === 'approved') {
            $status = 'verified';
        }

        $badge = match ($status) {
            'verified' => 'success',
            'pending'  => 'warning',
            'rejected' => 'danger',
            default    => 'secondary',
        };

        $label = match ($status) {
            'verified' => 'Verified',
            'pending'  => 'Pending review',
            'rejected' => 'Rejected',
            default    => 'Not submitted',
        };

        return [
            'status'      => $status,
            'badge'       => $badge,
            'label'       => $label,
            'is_verified' => $status === 'verified',
        ];
    }
}

if (! function_exists('drms_leaflet_map_config')) {
    /**
     * Leaflet map settings for Baras, Rizal service area (admin dashboard).
     *
     * @return array{
     *   center: array{lat: float, lng: float},
     *   zoom: int,
     *   bounds: array{sw: array{lat: float, lng: float}, ne: array{lat: float, lng: float}},
     *   maxBounds: array{sw: array{lat: float, lng: float}, ne: array{lat: float, lng: float}},
     *   radiusKm: float,
     *   label: string,
     *   municipality: string
     * }
     */
    function drms_leaflet_map_config(): array
    {
        $drms   = drms_config();
        $bounds = $drms->serviceAreaBounds;
        $sw     = $bounds[0] ?? [14.475, 121.225];
        $ne     = $bounds[1] ?? [14.565, 121.315];
        $pad    = 0.02;

        return [
            'center' => [
                'lat' => $drms->officeLatitude,
                'lng' => $drms->officeLongitude,
            ],
            'zoom'   => $drms->mapZoomLeaflet,
            'bounds' => [
                'sw' => ['lat' => (float) $sw[0], 'lng' => (float) $sw[1]],
                'ne' => ['lat' => (float) $ne[0], 'lng' => (float) $ne[1]],
            ],
            'maxBounds' => [
                'sw' => ['lat' => (float) $sw[0] - $pad, 'lng' => (float) $sw[1] - $pad],
                'ne' => ['lat' => (float) $ne[0] + $pad, 'lng' => (float) $ne[1] + $pad],
            ],
            'boundaryUrl'  => base_url('assets/geo/baras-rizal.geojson'),
            'label'        => 'Municipality of Baras, Rizal',
            'municipality' => $drms->serviceMunicipality . ', ' . $drms->serviceProvince,
        ];
    }
}

if (! function_exists('drms_map_config')) {
    /**
     * Alias for drms_leaflet_map_config
     *
     * @return array<string, mixed>
     */
    function drms_map_config(): array
    {
        return drms_leaflet_map_config();
    }
}

if (! function_exists('drms_incident_map_marker')) {
    /**
     * Leaflet marker styling: hazard type sets the border color, severity sets the marker background color.
     *
     * @return array{
     *   incident_type: string,
     *   type_label: string,
     *   icon: string,
     *   marker_bg: string,
     *   border_color: string
     * }
     */
    function drms_incident_map_marker(string $type, string $severity = 'moderate'): array
    {
        $type     = strtolower(trim($type));
        $severity = strtolower(trim($severity));
        $labels   = config('DrmsFeatures')->incidentTypes;

        $hazardStyles = [
            'flood'              => ['icon' => 'fa-water', 'border' => '#1565c0'],
            'fire'               => ['icon' => 'fa-fire', 'border' => '#c62828'],
            'landslide'          => ['icon' => 'fa-mountain', 'border' => '#6d4c41'],
            'earthquake'         => ['icon' => 'fa-house-damage', 'border' => '#5d4037'],
            'typhoon'            => ['icon' => 'fa-wind', 'border' => '#00838f'],
            'vehicular_incident' => ['icon' => 'fa-car-crash', 'border' => '#7b1fa2'],
            'injured_people'     => ['icon' => 'fa-ambulance', 'border' => '#ad1457'],
            'rescue_request'     => ['icon' => 'fa-life-ring', 'border' => '#e65100'],
            'other'              => ['icon' => 'fa-exclamation-triangle', 'border' => '#546e7a'],
        ];

        $def = $hazardStyles[$type] ?? $hazardStyles['other'];

        // Background color indicates severity level
        $bgColor = match ($severity) {
            'critical' => '#dc3545',
            'high'     => '#fd7e14',
            'low'      => '#28a745',
            default    => '#ffc107',
        };

        return [
            'incident_type' => $type,
            'type_label'    => $labels[$type] ?? ucfirst(str_replace('_', ' ', $type)),
            'icon'          => $def['icon'],
            'marker_bg'     => $bgColor,
            'border_color'  => $def['border'],
        ];
    }
}

if (! function_exists('drms_incident_map_marker_legend')) {
    /**
     * @return list<array{key: string, label: string, icon: string, marker_bg: string, border_color: string}>
     */
    function drms_incident_map_marker_legend(): array
    {
        $labels = config('DrmsFeatures')->incidentTypes;
        $rows   = [];

        foreach (array_keys($labels) as $key) {
            $marker = drms_incident_map_marker($key, 'moderate');

            // For the legend swatch, the background and border match the specific hazard type color (e.g. fire=red, flood=blue, etc.)
            $rows[] = [
                'key'          => $key,
                'label'        => $marker['type_label'],
                'icon'         => $marker['icon'],
                'marker_bg'    => $marker['border_color'],
                'border_color' => $marker['border_color'],
            ];
        }

        return $rows;
    }
}

if (! function_exists('drms_evac_map_marker')) {
    /**
     * Leaflet marker styling for evacuation centers on the public map.
     *
     * @return array{icon: string, marker_bg: string, border_color: string}
     */
    function drms_evac_map_marker(bool $isNearest = false, bool $isFull = false): array
    {
        if ($isFull) {
            return [
                'icon'         => 'fa-home',
                'marker_bg'    => '#757575',
                'border_color' => '#424242',
            ];
        }

        return [
            'icon'         => 'fa-home',
            'marker_bg'    => $isNearest ? '#1b5e20' : '#2e7d32',
            'border_color' => $isNearest ? '#ffeb3b' : '#1b5e20',
        ];
    }
}

if (! function_exists('drms_timezone')) {
    /** Application timezone (Philippines — UTC+8). */
    function drms_timezone(): string
    {
        return config('App')->appTimezone ?: 'Asia/Manila';
    }
}

if (! function_exists('drms_format_datetime')) {
    /**
     * Format a DB/API datetime string for display in the app timezone.
     */
    function drms_format_datetime(?string $value, string $format = 'Y-m-d H:i'): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        try {
            return \CodeIgniter\I18n\Time::parse($value, drms_timezone())->format($format);
        } catch (\Throwable) {
            return $value;
        }
    }
}

if (! function_exists('drms_now')) {
    /** Current time in the application timezone. */
    function drms_now(string $format = 'Y-m-d H:i:s'): string
    {
        return \CodeIgniter\I18n\Time::now(drms_timezone())->format($format);
    }
}

if (! function_exists('windy_embed_url')) {
    /**
     * Windy.com embed iframe — interactive weather layers (no API key).
     *
     * @param string $overlay wind|rain|temp|clouds|pressure|gust
     */
    function windy_embed_url(float $lat, float $lon, int $zoom = 11, string $overlay = 'wind'): string
    {
        $lat = round($lat, 4);
        $lon = round($lon, 4);

        return 'https://embed.windy.com/embed2.html?' . http_build_query([
            'lat'         => $lat,
            'lon'         => $lon,
            'detailLat'   => $lat,
            'detailLon'   => $lon,
            'zoom'        => $zoom,
            'level'       => 'surface',
            'overlay'     => $overlay,
            'product'     => 'ecmwf',
            'message'     => 'true',
            'marker'      => '',
            'calendar'    => 'now',
            'pressure'    => '',
            'type'        => 'map',
            'location'    => 'coordinates',
            'detail'      => 'true',
            'metricWind'  => 'km/h',
            'metricTemp'  => '°C',
            'radarRange'  => -1,
        ], '', '&', PHP_QUERY_RFC3986);
    }
}

if (! function_exists('windy_forecast_url')) {
    /** Full Windy.com page with map marker at coordinates (lat, lon, zoom, d:picker). */
    function windy_forecast_url(float $lat, float $lon, int $zoom = 11): string
    {
        $lat = round($lat, 4);
        $lon = round($lon, 4);

        return 'https://www.windy.com/?' . rawurlencode((string) $lat) . ',' . rawurlencode((string) $lon) . ',' . $zoom . ',d:picker';
    }
}
