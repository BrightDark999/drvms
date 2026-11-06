<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\BaseController;
use App\Services\EvacFamilyService;
use App\Services\EvacuationLookupService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

class Evacuation extends BaseController
{
    public function index(): string
    {
        helper('drms');

        $drms = drms_config();
        $lat  = (float) ($this->request->getGet('lat') ?: $drms->officeLatitude);
        $lng  = (float) ($this->request->getGet('lng') ?: $drms->officeLongitude);
        $svc  = new EvacuationLookupService();

        $centers = $svc->enrichCenters($svc->listOpenCenters());
        $nearest = $svc->nearestCenter($lat, $lng);

        $mapCenters = [];
        foreach ($centers as $c) {
            if ($c['latitude'] === null || $c['longitude'] === null) {
                continue;
            }
            $isNearest = $nearest && (int) ($nearest['id'] ?? 0) === (int) ($c['id'] ?? 0);
            $mapCenters[] = [
                'id'              => (int) ($c['id'] ?? 0),
                'name'            => $c['name'] ?? '',
                'address'         => $c['address'] ?? '',
                'barangay_name'   => $c['barangay_name'] ?? '',
                'latitude'        => (float) $c['latitude'],
                'longitude'       => (float) $c['longitude'],
                'capacity'        => (int) ($c['capacity'] ?? 0),
                'occupancy'       => (int) ($c['current_occupancy'] ?? 0),
                'available_slots' => (int) ($c['available_slots'] ?? 0),
                'contact_phone'   => $c['contact_phone'] ?? '',
                'contact_person'  => $c['contact_person'] ?? '',
                'intake_procedures' => $c['intake_procedures'] ?? '',
                'required_items'    => $c['required_items'] ?? '',
                'is_full'         => ! empty($c['is_full']),
                'is_nearest'      => $isNearest,
                'marker'          => drms_evac_map_marker($isNearest, ! empty($c['is_full'])),
            ];
        }

        return view('public/evac_centers', [
            'title'        => 'Evacuation Centers',
            'drms'         => $drms,
            'centers'      => $centers,
            'nearest'      => $nearest,
            'nearestSource' => $this->request->getGet('lat') !== null && $this->request->getGet('lng') !== null
                ? 'gps'
                : 'default',
            'alternatives' => $svc->suggestAlternatives($lat, $lng, 5),
            'lat'          => $lat,
            'lng'          => $lng,
            'mapConfig'        => drms_leaflet_map_config(),
            'mapCenters'       => $mapCenters,
            'hasGpsFromQuery'  => $this->request->getGet('lat') !== null && $this->request->getGet('lng') !== null,
        ]);
    }

    public function nearestApi(): ResponseInterface
    {
        $lat = (float) ($this->request->getGet('lat') ?? drms_config()->officeLatitude);
        $lng = (float) ($this->request->getGet('lng') ?? drms_config()->officeLongitude);
        $nearest = (new EvacuationLookupService())->nearestCenter($lat, $lng);

        return $this->response->setJSON([
            'status'  => 'ok',
            'nearest' => $nearest,
            'list'    => (new EvacuationLookupService())->suggestAlternatives($lat, $lng, 5),
        ]);
    }

    public function registerFamily(): RedirectResponse
    {
        $captchaSvc = new \App\Services\CaptchaService();
        $post = $this->request->getPost();

        if (! $captchaSvc->isHoneypotClean($post)) {
            return redirect()->back()->withInput()->with('error', 'Spam submission detected.');
        }

        if (! $captchaSvc->verify((string) ($post['captcha_code'] ?? ''))) {
            return redirect()->back()->withInput()->with('error', 'Invalid security verification code. Please check the CAPTCHA and try again.');
        }

        $rules = [
            'evacuation_center_id' => 'required|is_natural_no_zero',
            'family_head_name'     => 'required|min_length[2]|max_length[160]',
            'members_count'        => 'required|is_natural_no_zero',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $family = (new EvacFamilyService())->register($this->request->getPost());

            return redirect()->back()->with('message', 'Family registered. QR token: ' . ($family['family_qr_token'] ?? ''));
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function checkInFamily(): ResponseInterface|RedirectResponse
    {
        $token = trim((string) $this->request->getPost('family_qr_token'));
        if ($token === '') {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status'  => 'error',
                    'message' => 'Family QR token is required.',
                ]);
            }

            return redirect()->back()->with('error', 'Family QR token is required.');
        }

        try {
            $result = (new EvacFamilyService())->checkIn($token);

            if ($this->request->isAJAX()) {
                return $this->response->setJSON($result);
            }

            $name = (string) ($result['data']['family_head_name'] ?? '');
            $at   = (string) ($result['data']['checked_in_at'] ?? '');

            return redirect()->back()->with('message', 'Checked in: ' . $name . ' at ' . $at);
        } catch (RuntimeException $e) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                ]);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
