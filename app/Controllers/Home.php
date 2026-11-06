<?php

namespace App\Controllers;

use App\Services\OpenMeteoService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Public-facing pages (homepage, about, contact placeholders).
 */
class Home extends BaseController
{
    /**
     * System homepage — public services, modules, roles, and live operations snapshot.
     */
    public function index(): string
    {
        $drms = drms_config();

        $lat = $drms->officeLatitude;
        $lng = $drms->officeLongitude;

        return view('home/index', [
            'title'                 => $drms->appFullName,
            'drms'                  => $drms,
            'carouselSlides'        => (new \App\Services\HomeCarouselService())->listActive(),
            'facebookPageUrl'       => $drms->facebookPageUrl,
            'facebookPageName'      => $drms->facebookPageName,
            'facebookPageEmbedEnabled' => $drms->facebookPageEmbedEnabled,
            'modules'               => $drms->modules,
            'roles'                 => $drms->roles,
            'stats'                 => $this->homepageStats(),
            'publicFeatures'        => $this->publicFeatures(),
            'googleMapsEmbedApiKey' => (string) (env('google.maps.embedApiKey') ?? ''),
            'windyEmbedUrl'         => windy_embed_url($lat, $lng, $drms->mapZoomWindy, 'wind'),
            'windyForecastUrl'      => windy_forecast_url($lat, $lng, $drms->mapZoomWindy),
            'mapLat'                => $lat,
            'mapLng'                => $lng,
            'mapZoomWindy'          => $drms->mapZoomWindy,
        ]);
    }

    /**
     * @return array<string, int|string>
     */
    private function homepageStats(): array
    {
        try {
            $overview = (new \App\Services\DashboardStatsService())->getOverview();

            return [
                'active_incidents'     => (int) ($overview['active_incidents'] ?? 0),
                'volunteers_on_duty'   => (int) ($overview['volunteers_on_duty'] ?? 0),
                'evacuation_centers'   => (int) ($overview['evacuation_centers'] ?? 0),
                'module_count'         => count(drms_config()->modules),
            ];
        } catch (\Throwable) {
            return [
                'active_incidents'   => 0,
                'volunteers_on_duty' => 0,
                'evacuation_centers' => 0,
                'module_count'       => count(drms_config()->modules),
            ];
        }
    }

    /**
     * @return list<array{icon:string, title:string, text:string, url:string}>
     */
    private function publicFeatures(): array
    {
        return [
            [
                'icon'  => 'megaphone',
                'title' => 'Report an incident',
                'text'  => 'Citizens can submit disaster reports with photos, location, and severity — records go straight to MDRRMO for verification.',
                'url'   => base_url('report'),
            ],
            [
                'icon'  => 'home',
                'title' => 'Evacuation centers',
                'text'  => 'Find the nearest open shelter, see capacity, and register your family with a unique check-in token.',
                'url'   => base_url('evac-centers'),
            ],
            [
                'icon'  => 'user-plus',
                'title' => 'Volunteer registration',
                'text'  => 'Sign up as a volunteer. New accounts require administrator approval before login.',
                'url'   => url_to('register'),
            ],
            [
                'icon'  => 'user',
                'title' => 'My profile',
                'text'  => 'Approved volunteers manage skills, upload ID for verification, and receive deployment notifications.',
                'url'   => base_url('profile'),
            ],
            [
                'icon'  => 'layout-dashboard',
                'title' => 'Operations dashboard',
                'text'  => 'MDRRMO and LGU staff use a live dashboard with incident maps, alerts, and role-based modules.',
                'url'   => base_url('admin/dashboard'),
            ],
        ];
    }

    /**
     * Same-origin weather JSON for the homepage (server proxies Open-Meteo — no browser CORS).
     */
    public function weatherCurrent(): ResponseInterface
    {
        $drms = drms_config();
        $lat  = (float) ($this->request->getGet('lat') ?? $drms->officeLatitude);
        $lng  = (float) ($this->request->getGet('lon') ?? $drms->officeLongitude);

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Invalid coordinates.',
            ]);
        }

        try {
            $result = (new OpenMeteoService())->getCurrentConditions($lat, $lng);

            $payload = [
                'status'  => 'ok',
                'current' => $result['current'],
                'source'  => $result['source'] ?? 'open-meteo',
                'stale'   => (bool) ($result['stale'] ?? false),
            ];
            if (! empty($result['description'])) {
                $payload['description'] = $result['description'];
            }

            return $this->response->setJSON($payload);
        } catch (\Throwable $e) {
            log_message('error', 'Home::weatherCurrent — ' . $e->getMessage());

            return $this->response->setStatusCode(502)->setJSON([
                'status'  => 'error',
                'message' => 'Weather data is temporarily unavailable. Please try again later.',
            ]);
        }
    }

    /**
     * About page (can expand later).
     */
    public function about(): string
    {
        $drms = drms_config();

        return view('home/about', [
            'title' => 'About – ' . $drms->appName,
            'drms'  => $drms,
        ]);
    }
}
