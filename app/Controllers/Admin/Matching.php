<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BarangayModel;
use App\Models\IncidentModel;
use App\Services\IncidentGeoService;
use App\Services\VolunteerDeploymentService;
use App\Services\VolunteerMatchingService;
use App\Services\VolunteerSkillRequestService;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

/**
 * Skill-aware volunteer dispatch console.
 */
class Matching extends BaseController
{
    public function index(): string
    {
        helper('drms');

        $svc              = new VolunteerMatchingService();
        $incidentId       = (int) ($this->request->getGet('incident_id') ?? 0);
        $volunteerPortal  = drms_is_volunteer_dispatch_view();
        $canDispatch      = drms_can_dispatch_volunteers();
        $queue            = $svc->incidentQueue();
        $poolStats        = $volunteerPortal ? [] : $svc->poolStats();
        $matchResult      = ['required_skills' => [], 'staffing' => ['rescue' => 0, 'medical' => 0, 'drivers' => 0], 'ranked' => []];
        $incident         = null;
        $fulfillment      = null;
        $timeline         = [];
        $mapIncident      = null;
        $mapVolunteers    = [];
        $stats            = ['available' => 0, 'avg_match' => 0, 'nearest_km' => null];
        $autoTeam         = [];
        $myVolunteer          = null;
        $myMatch              = null;
        $myDeployment         = null;
        $approvedSkills       = [];
        $incidentAccessDenied = false;

        if ($volunteerPortal) {
            $myVolunteer = (new \App\Services\ProfileService())->getVolunteerRecord((int) auth()->id());
            if ($myVolunteer) {
                $volunteerId    = (int) $myVolunteer['id'];
                $approvedSkills = (new VolunteerSkillRequestService())->skillNamesForVolunteer($volunteerId);
                $queue          = $svc->incidentQueueForVolunteer($volunteerId);
            } else {
                $queue = [];
            }
        }

        if ($incidentId > 0) {
            $incident = model(IncidentModel::class)->find($incidentId);

            if ($volunteerPortal && $incident && $myVolunteer) {
                $volunteerId = (int) $myVolunteer['id'];
                $myDeployment = (new VolunteerDeploymentService())->findForVolunteerIncident($volunteerId, $incidentId);
                if (! $myDeployment && ! $svc->incidentVisibleToVolunteer($incident, $approvedSkills)) {
                    $incidentAccessDenied = true;
                    $incidentId           = 0;
                    $incident             = null;
                }
            }
        }

        if ($incidentId > 0) {
            $incident    = $incident ?? model(IncidentModel::class)->find($incidentId);
            $matchResult = $svc->recommendForIncident($incidentId);

            if ($volunteerPortal && $myVolunteer) {
                $volunteerId = (int) $myVolunteer['id'];
                foreach ($matchResult['ranked'] ?? [] as $row) {
                    if ((int) ($row['volunteer_id'] ?? 0) === $volunteerId) {
                        $myMatch = $row;
                        break;
                    }
                }
                $myDeployment = (new VolunteerDeploymentService())->findForVolunteerIncident($volunteerId, $incidentId);
                if ($myDeployment) {
                    $timeline = [[
                        'id'           => (int) ($myDeployment['id'] ?? 0),
                        'volunteer_id' => $volunteerId,
                        'full_name'    => (string) ($myVolunteer['full_name'] ?? 'You'),
                        'status'       => (string) ($myDeployment['status'] ?? ''),
                        'deployed_at'  => (string) ($myDeployment['deployed_at'] ?? $myDeployment['created_at'] ?? ''),
                    ]];
                }
            } else {
                $fulfillment = $svc->staffingFulfillment($incidentId);
                $timeline    = (new VolunteerDeploymentService())->timelineForIncident($incidentId);
                $stats       = $svc->matchStats($matchResult['ranked'] ?? []);
                $autoTeam    = $svc->suggestAutoTeam($incidentId);
            }

            if ($incident) {
                $geo = (new IncidentGeoService())->resolve($incident);
                if ($geo) {
                    $type   = (string) ($incident['incident_type'] ?? 'other');
                    $marker = drms_incident_map_marker($type, (string) ($incident['severity'] ?? 'moderate'));
                    $mapIncident = [
                        'latitude'  => $geo['lat'],
                        'longitude' => $geo['lng'],
                        'title'     => (string) ($incident['title'] ?? ''),
                        'reference' => (string) ($incident['reference_code'] ?? ''),
                        'marker'    => $marker,
                    ];
                }

                if (! empty($incident['barangay_id'])) {
                    $brgy = model(BarangayModel::class)->find((int) $incident['barangay_id']);
                    $incident['barangay_name'] = $brgy['name'] ?? '';
                }

                $rankedForMap = $volunteerPortal && $myMatch
                    ? [$myMatch]
                    : ($matchResult['ranked'] ?? []);

                foreach ($rankedForMap as $i => $row) {
                    if ($row['latitude'] === null || $row['longitude'] === null) {
                        continue;
                    }
                    $mapVolunteers[] = [
                        'volunteer_id' => (int) ($row['volunteer_id'] ?? 0),
                        'full_name'    => $volunteerPortal ? 'You' : (string) ($row['full_name'] ?? ''),
                        'match_pct'    => (int) ($row['match_pct'] ?? 0),
                        'latitude'     => (float) $row['latitude'],
                        'longitude'    => (float) $row['longitude'],
                        'rank'         => $i + 1,
                    ];
                }
            }
        }

        return view('admin/matching/index', [
            'title'           => $volunteerPortal ? 'My Dispatch' : 'Volunteer Dispatch',
            'drms'            => drms_config(),
            'queue'           => $queue,
            'selectedId'      => $incidentId,
            'incident'        => $incident,
            'requiredSkills'  => $matchResult['required_skills'] ?? [],
            'staffing'        => $matchResult['staffing'] ?? [],
            'ranked'          => $volunteerPortal ? [] : ($matchResult['ranked'] ?? []),
            'fulfillment'     => $volunteerPortal ? null : $fulfillment,
            'timeline'        => $timeline,
            'matchStats'      => $stats,
            'poolStats'       => $poolStats,
            'autoTeam'        => $volunteerPortal ? [] : $autoTeam,
            'mapConfig'       => drms_leaflet_map_config(),
            'mapIncident'     => $mapIncident,
            'mapVolunteers'   => $mapVolunteers,
            'incidentTypes'   => config('DrmsFeatures')->incidentTypes,
            'canDispatch'     => $canDispatch,
            'volunteerPortal' => $volunteerPortal,
            'myVolunteer'          => $myVolunteer,
            'myMatch'              => $myMatch,
            'myDeployment'         => $myDeployment,
            'approvedSkills'       => $approvedSkills,
            'incidentAccessDenied' => $incidentAccessDenied,
        ]);
    }

    public function deploy(): ResponseInterface
    {
        return $this->runDeploy([(int) $this->request->getPost('volunteer_id')]);
    }

    public function deployBulk(): ResponseInterface
    {
        $ids = $this->request->getPost('volunteer_ids');
        if (! is_array($ids)) {
            $ids = [];
        }

        return $this->runDeploy(array_map('intval', $ids));
    }

    public function autoAssign(): ResponseInterface
    {
        $incidentId = (int) $this->request->getPost('incident_id');
        if ($incidentId < 1) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Invalid incident.',
            ]);
        }

        $picks = (new VolunteerMatchingService())->suggestAutoTeam($incidentId);
        if ($picks === []) {
            return $this->response->setJSON([
                'status'  => 'ok',
                'message' => 'Staffing needs are already met or no suitable volunteers found.',
                'deployed' => 0,
            ]);
        }

        $ids = array_map(static fn (array $p): int => (int) ($p['volunteer_id'] ?? 0), $picks);

        return $this->runDeploy($ids, $incidentId);
    }

    /**
     * @param list<int> $volunteerIds
     */
    private function runDeploy(array $volunteerIds, ?int $incidentId = null): ResponseInterface
    {
        if (! drms_can_dispatch_volunteers()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Only MDRRMO / LGU staff can deploy volunteers.',
            ]);
        }

        $incidentId = $incidentId ?? (int) $this->request->getPost('incident_id');
        $volunteerIds = array_values(array_filter($volunteerIds, static fn (int $id): bool => $id > 0));

        if ($incidentId < 1 || $volunteerIds === []) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Invalid volunteer or incident.',
            ]);
        }

        try {
            $result = (new VolunteerDeploymentService())->deployMany($volunteerIds, $incidentId, auth()->id());
            $msg    = $result['deployed'] . ' volunteer(s) deployed.';
            if ($result['skipped'] > 0) {
                $msg .= ' ' . $result['skipped'] . ' skipped.';
            }

            return $this->response->setJSON([
                'status'   => 'ok',
                'message'  => $msg,
                'deployed' => $result['deployed'],
                'skipped'  => $result['skipped'],
            ]);
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }
}
