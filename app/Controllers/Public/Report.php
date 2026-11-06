<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\BaseController;
use App\Models\BarangayModel;
use App\Services\IncidentMediaService;
use App\Services\IncidentService;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;

class Report extends BaseController
{
    public function index(): string
    {
        helper('drms');

        return view('public/report', [
            'title'         => 'Report a Disaster',
            'drms'          => drms_config(),
            'barangays'     => drms_barangay_list(),
            'incidentTypes' => config('DrmsFeatures')->incidentTypes,
            'mapConfig'     => drms_leaflet_map_config(),
        ]);
    }

    public function submit(): RedirectResponse
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
            'title'         => 'required|min_length[5]|max_length[255]',
            'description'   => 'permit_empty|max_length[5000]',
            'incident_type' => 'required|max_length[64]',
            'severity'      => 'required|in_list[low,moderate,high,critical]',
            'barangay_id'   => 'required|is_natural_no_zero',
            'latitude'      => 'permit_empty|decimal',
            'longitude'     => 'permit_empty|decimal',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $userId = auth()->loggedIn() ? auth()->id() : null;
            $incident = (new IncidentService())->create($this->request->getPost(), $userId);

            $files = $this->request->getFileMultiple('media');
            if ($files !== []) {
                (new IncidentMediaService())->attachToIncident((int) $incident['id'], $files);
            }

            return redirect()->to(base_url('report/success/' . (int) $incident['id']))
                ->with('message', 'Incident reported. Reference: ' . ($incident['reference_code'] ?? ''));
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function success(int $id): string
    {
        if (drms_presentation_active() && $id === 9001) {
            $incident = [
                'id'             => 9001,
                'reference_code' => 'INC-DEMO-9001',
                'title'          => 'Demo community flood report',
                'status'         => 'reported',
                'severity'       => 'high',
            ];
        } else {
            $incident = model(\App\Models\IncidentModel::class)->find($id);
        }

        $media = [];
        if (is_array($incident) && ! empty($incident['id'])) {
            $mediaSvc = new IncidentMediaService();
            $media    = $mediaSvc->formatForApi($mediaSvc->listForIncident((int) $incident['id']));
        }

        return view('public/report_success', [
            'title'    => 'Report Submitted',
            'drms'     => drms_config(),
            'incident' => $incident,
            'media'    => $media,
        ]);
    }
}
