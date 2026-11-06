<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>

<?php
$placeQuery = rawurlencode($drms->serviceMunicipality . ', ' . $drms->serviceProvince . ', ' . $drms->serviceCountry);
$lat        = $mapLat ?? $drms->officeLatitude;
$lng        = $mapLng ?? $drms->officeLongitude;
$zoomGoogle = (int) ($drms->mapZoomGoogle ?? 12);
if ($googleMapsEmbedApiKey !== '') {
    $mapSrc = 'https://www.google.com/maps/embed/v1/place?key=' . rawurlencode($googleMapsEmbedApiKey) . '&q=' . $placeQuery . '&zoom=' . $zoomGoogle;
} else {
    $mapSrc = 'https://maps.google.com/maps?q=' . rawurlencode((string) $lat . ',' . (string) $lng) . '&hl=en&z=' . $zoomGoogle . '&output=embed&iwloc=near';
}
?>

<!-- Hero carousel (managed in Admin → Homepage Carousel) -->
<?= view('partials/public/home_carousel', ['carouselSlides' => $carouselSlides ?? []]) ?>

<!-- Live operations snapshot -->
<section class="drms-stat-strip py-4">
    <div class="container">
        <div class="row text-center text-lg-left align-items-center">
            <div class="col-6 col-lg-3 mb-3 mb-lg-0">
                <div class="stat-strip-item">
                    <span class="stat-strip-value"><?= (int) ($stats['active_incidents'] ?? 0) ?></span>
                    <span class="stat-strip-label">Active incidents tracked</span>
                </div>
            </div>
            <div class="col-6 col-lg-3 mb-3 mb-lg-0">
                <div class="stat-strip-item">
                    <span class="stat-strip-value"><?= (int) ($stats['volunteers_on_duty'] ?? 0) ?></span>
                    <span class="stat-strip-label">Volunteers on duty</span>
                </div>
            </div>
            <div class="col-6 col-lg-3 mb-3 mb-lg-0">
                <div class="stat-strip-item">
                    <span class="stat-strip-value"><?= (int) ($stats['evacuation_centers'] ?? 0) ?></span>
                    <span class="stat-strip-label">Evacuation centers</span>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-strip-item">
                    <span class="stat-strip-value"><?= (int) ($stats['module_count'] ?? 0) ?></span>
                    <span class="stat-strip-label">System modules</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Public services -->
<section id="public-services" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Public &amp; community services</h2>
            <p class="text-muted col-lg-9 mx-auto mb-0">Anyone in <?= esc($drms->serviceMunicipality) ?> can use these pages without staff credentials. MDRRMO reviews registrations and incidents behind the scenes.</p>
        </div>
        <div class="row">
            <?php foreach ($publicFeatures as $pf): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 module-card border-0">
                    <div class="card-body d-flex flex-column">
                        <i data-lucide="<?= esc($pf['icon']) ?>" class="module-icon"></i>
                        <h5 class="font-weight-bold mt-2"><?= esc($pf['title']) ?></h5>
                        <p class="text-muted small flex-grow-1"><?= esc($pf['text']) ?></p>
                        <a href="<?= esc($pf['url']) ?>" class="btn btn-sm btn-drms-primary mt-2 align-self-start">Open</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- LGU Facebook page — full-screen embed -->
<?= view('partials/public/social_gallery', [
    'facebookPageUrl'          => $facebookPageUrl ?? drms_config()->facebookPageUrl,
    'facebookPageName'         => $facebookPageName ?? drms_config()->facebookPageName,
    'facebookPageEmbedEnabled' => $facebookPageEmbedEnabled ?? drms_config()->facebookPageEmbedEnabled,
]) ?>

<!-- Map & weather -->
<section id="location" class="py-5 drms-section-alt">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Service area &amp; weather context</h2>
            <p class="text-muted col-lg-9 mx-auto mb-0">
                Situational maps and rainfall readings for <strong><?= esc($drms->serviceMunicipality) ?></strong>, <?= esc($drms->serviceProvince) ?>.
                Use alongside official advisories from <strong>PAGASA</strong> when planning deployments or public warnings.
            </p>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="drms-map-card drms-windy-card">
                    <div class="drms-map-header d-flex flex-wrap justify-content-between align-items-center">
                        <span><i data-lucide="wind" class="icon-inline"></i> Weather map — <?= esc($drms->serviceMunicipality) ?></span>
                        <div class="d-flex flex-wrap align-items-center">
                            <div class="btn-group btn-group-sm mr-2 mb-1" id="windyOverlayBtns" role="group" aria-label="Weather layer">
                                <button type="button" class="btn btn-outline-primary active" data-overlay="wind">Wind</button>
                                <button type="button" class="btn btn-outline-primary" data-overlay="rain">Rain</button>
                                <button type="button" class="btn btn-outline-primary" data-overlay="temp">Temp</button>
                                <button type="button" class="btn btn-outline-primary" data-overlay="clouds">Clouds</button>
                            </div>
                            <a href="<?= esc($windyForecastUrl) ?>" class="btn btn-sm btn-outline-secondary mb-1" target="_blank" rel="noopener noreferrer">
                                Full forecast <i data-lucide="external-link" class="icon-inline icon-xs"></i>
                            </a>
                        </div>
                    </div>
                    <p class="small text-muted px-3 pt-2 mb-0">Rain and wind layers help duty officers anticipate flood risk in low-lying barangays.</p>
                    <div class="drms-windy-frame-wrap"
                         id="drms-windy"
                         data-lat="<?= esc((string) $lat) ?>"
                         data-lon="<?= esc((string) $lng) ?>"
                         data-zoom="<?= (int) ($mapZoomWindy ?? $drms->mapZoomWindy) ?>">
                        <iframe
                            id="windyEmbedFrame"
                            title="Weather map — <?= esc($drms->serviceMunicipality) ?>"
                            src="<?= esc($windyEmbedUrl) ?>"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            allowfullscreen></iframe>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-7 mb-4 mb-lg-0">
                <div class="drms-map-card">
                    <div class="drms-map-header d-flex justify-content-between align-items-center">
                        <span><i data-lucide="map-pin" class="icon-inline"></i> <?= esc($drms->serviceMunicipality) ?> — service area</span>
                    </div>
                    <div class="drms-map-frame-wrap">
                        <iframe
                            title="Map of <?= esc($drms->serviceMunicipality) ?>"
                            src="<?= esc($mapSrc) ?>"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            allowfullscreen></iframe>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card drms-weather-card border-0 h-100" id="drms-weather"
                     data-lat="<?= esc((string) $lat) ?>"
                     data-lon="<?= esc((string) $lng) ?>"
                     data-weather-url="<?= esc(base_url('api/weather/current')) ?>">
                    <div class="card-body">
                        <h5 class="font-weight-bold mb-1"><i data-lucide="cloud-sun" class="icon-inline"></i> Current conditions</h5>
                        <p class="small text-muted mb-3"><?= esc($drms->serviceMunicipality) ?> · updated from live readings</p>
                        <div id="drms-weather-loading" class="drms-weather-loading text-muted small">Loading latest conditions…</div>
                        <div id="drms-weather-body" class="d-none">
                            <div class="drms-weather-temp" id="drms-weather-temp">—</div>
                            <p class="mb-2 text-muted small" id="drms-weather-desc"></p>
                            <ul class="list-unstyled small text-muted mb-0 drms-weather-meta">
                                <li><span class="text-dark font-weight-bold">Feels like</span> <span id="drms-weather-apparent">—</span></li>
                                <li><span class="text-dark font-weight-bold">Humidity</span> <span id="drms-weather-humidity">—</span></li>
                                <li><span class="text-dark font-weight-bold">Wind</span> <span id="drms-weather-wind">—</span></li>
                            </ul>
                            <p class="small text-muted mt-3 mb-0" id="drms-weather-updated"></p>
                        </div>
                        <div id="drms-weather-error" class="alert alert-warning small mb-0 d-none" role="alert"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modules -->
<section id="modules" class="py-5">
    <div class="container">
        <h2 class="section-title text-center mb-2">System modules</h2>
        <p class="text-center text-muted mb-5 col-lg-9 mx-auto">Each module supports a specific part of disaster response — from the first report to audit-ready operations summaries.</p>
        <div class="row">
            <?php foreach ($modules as $i => $mod):
                $module = $mod['erp_module'] ?? 'operations';
                $moduleLabel = match ($module) {
                    'relief'     => 'Relief',
                    'platform'   => 'Platform',
                    default      => 'Operations',
                };
                $moduleBadge = match ($module) {
                    'relief'     => 'info',
                    'platform'   => 'secondary',
                    default      => 'danger',
                };
            ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 module-card border-0">
                    <div class="card-body">
                        <!-- <span class="module-num"><?//= $i + 1 ?></span> -->
                        <span class="badge badge-<?= $moduleBadge ?> badge-pill float-right small"><?= esc($moduleLabel) ?></span>
                        <i data-lucide="<?= esc($mod['icon']) ?>" class="module-icon"></i>
                        <h5 class="font-weight-bold"><?= esc($mod['title']) ?></h5>
                        <p class="text-muted small mb-0"><?= esc($mod['description']) ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Roles -->
<section id="roles" class="py-5 drms-section-alt">
    <div class="container">
        <h2 class="section-title text-center mb-2">Who uses <?= esc($drms->appName) ?>?</h2>
        <p class="text-center text-muted mb-5 col-lg-8 mx-auto">Each role sees only the modules they need — from full MDRRMO control to volunteer portals and donor records.</p>
        <div class="row justify-content-center">
            <?php foreach ($roles as $key => $label): ?>
            <div class="col-6 col-md-4 col-lg-3 mb-3 text-center">
                <span class="role-badge"><?= esc($label) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="text-center text-muted small mt-4 mb-0">New resident accounts stay <strong>pending</strong> until MDRRMO verifies their ID and approves them. Staff can then assign roles such as Volunteer or MDRRMO / LGU Staff in User Management.</p>
    </div>
</section>

<!-- CTA -->
<section class="py-5 drms-cta">
    <div class="container text-center">
        <h2 class="text-white mb-3">Need to report, volunteer, or run operations?</h2>
        <p class="text-white-75 mb-4">Use the public tools below or sign in if you are MDRRMO / LGU staff or an approved volunteer.</p>
        <div class="mb-4">
            <a href="<?= base_url('report') ?>" class="btn btn-outline-light btn-lg mr-2 mb-2">Report incident</a>
            <a href="<?= url_to('register') ?>" class="btn btn-outline-light btn-lg mr-2 mb-2">Register</a>
            <a href="<?= url_to('login') ?>" class="btn btn-drms-light btn-lg mb-2">Staff login</a>
        </div>
        <a href="<?= base_url('admin/dashboard') ?>" class="btn btn-link text-white-50">Open operations dashboard</a>
    </div>
</section>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    'use strict';

    function wmoLabel(code) {
        if (code === 0) return 'Clear sky';
        if (code === 1) return 'Mainly clear';
        if (code === 2) return 'Partly cloudy';
        if (code === 3) return 'Overcast';
        if (code >= 45 && code <= 48) return 'Fog';
        if (code >= 51 && code <= 67) return 'Rain';
        if (code >= 80 && code <= 82) return 'Rain showers';
        if (code >= 95) return 'Thunderstorm';
        return 'Current conditions';
    }

    var el = document.getElementById('drms-weather');
    if (!el) return;

    var lat = parseFloat(el.getAttribute('data-lat'));
    var lon = parseFloat(el.getAttribute('data-lon'));
    var loading = document.getElementById('drms-weather-loading');
    var body = document.getElementById('drms-weather-body');
    var errBox = document.getElementById('drms-weather-error');

    if (Number.isNaN(lat) || Number.isNaN(lon)) {
        if (loading) loading.classList.add('d-none');
        if (errBox) {
            errBox.textContent = 'Weather coordinates are not configured.';
            errBox.classList.remove('d-none');
        }
        return;
    }

    var apiUrl = window.DRMS_WEATHER_URL || el.getAttribute('data-weather-url') || '';
    if (!apiUrl || apiUrl.indexOf('open-meteo.com') !== -1) {
        apiUrl = <?= json_encode(base_url('api/weather/current'), JSON_THROW_ON_ERROR) ?>;
    }

    var url = apiUrl + (apiUrl.indexOf('?') >= 0 ? '&' : '?') +
        'lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lon);

    fetch(url, { method: 'GET', credentials: 'same-origin', headers: { Accept: 'application/json' } })
        .then(function (res) {
            return res.json().then(function (data) {
                if (!res.ok || data.status !== 'ok') {
                    throw new Error(data.message || 'Weather unavailable');
                }
                return data;
            });
        })
        .then(function (data) {
            var cur = data.current;
            if (!cur) throw new Error('No weather data');

            if (loading) loading.classList.add('d-none');
            if (errBox) errBox.classList.add('d-none');
            if (body) body.classList.remove('d-none');

            var tempEl = document.getElementById('drms-weather-temp');
            var descEl = document.getElementById('drms-weather-desc');
            var appEl = document.getElementById('drms-weather-apparent');
            var humEl = document.getElementById('drms-weather-humidity');
            var windEl = document.getElementById('drms-weather-wind');
            var updatedEl = document.getElementById('drms-weather-updated');

            if (tempEl && cur.temperature_2m != null) {
                tempEl.textContent = Math.round(cur.temperature_2m) + '°C';
            }
            if (descEl) {
                descEl.textContent = data.description || wmoLabel(cur.weather_code);
            }
            if (appEl && cur.apparent_temperature != null) {
                appEl.textContent = Math.round(cur.apparent_temperature) + '°C';
            }
            if (humEl && cur.relative_humidity_2m != null) {
                humEl.textContent = Math.round(cur.relative_humidity_2m) + '%';
            }
            if (windEl && cur.wind_speed_10m != null) {
                windEl.textContent = Math.round(cur.wind_speed_10m) + ' km/h';
            }
            if (updatedEl && cur.time) {
                var src = data.source === 'wttr.in' ? 'wttr.in' : 'Open-Meteo';
                updatedEl.textContent = 'Updated: ' + String(cur.time).replace('T', ' ') + ' · ' + src +
                    (data.stale ? ' (cached)' : '');
            }

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        })
        .catch(function (err) {
            if (loading) loading.classList.add('d-none');
            if (body) body.classList.add('d-none');
            if (errBox) {
                errBox.textContent = (err && err.message) ?
                    err.message :
                    'Could not load weather. Please refresh the page.';
                errBox.classList.remove('d-none');
            }
        });
})();
</script>
<?= $this->endSection() ?>
