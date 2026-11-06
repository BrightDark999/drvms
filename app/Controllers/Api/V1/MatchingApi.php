<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Models\IncidentModel;
use App\Services\VolunteerDeploymentService;
use App\Services\VolunteerMatchingService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use RuntimeException;

class MatchingApi extends ResourceController
{
    protected $format = 'json';

    public function queue(): ResponseInterface
    {
        $svc = new VolunteerMatchingService();

        return $this->respond([
            'data' => [
                'queue'     => $svc->incidentQueue(),
                'poolStats' => $svc->poolStats(),
            ],
        ]);
    }

    public function recommend($incidentId = null): ResponseInterface
    {
        $incidentId = (int) $incidentId;
        if ($incidentId < 1) {
            return $this->fail('Invalid incident id.', 422);
        }

        $incident = model(IncidentModel::class)->find($incidentId);
        if (! $incident) {
            return $this->failNotFound('Incident not found.');
        }

        $svc         = new VolunteerMatchingService();
        $matchResult = $svc->recommendForIncident($incidentId);

        return $this->respond([
            'data' => [
                'incident'    => $incident,
                'required'    => $matchResult['required_skills'] ?? [],
                'staffing'    => $matchResult['staffing'] ?? [],
                'ranked'      => $matchResult['ranked'] ?? [],
                'fulfillment' => $svc->staffingFulfillment($incidentId),
                'autoTeam'    => $svc->suggestAutoTeam($incidentId),
                'timeline'    => (new VolunteerDeploymentService())->timelineForIncident($incidentId),
                'stats'       => $svc->matchStats($matchResult['ranked'] ?? []),
            ],
        ]);
    }

    public function deploy(): ResponseInterface
    {
        return $this->runDeploy([(int) ($this->input()['volunteer_id'] ?? 0)]);
    }

    public function deployBulk(): ResponseInterface
    {
        $input = $this->input();
        $ids   = $input['volunteer_ids'] ?? [];
        if (! is_array($ids)) {
            $ids = [];
        }

        return $this->runDeploy(array_map('intval', $ids));
    }

    public function autoAssign(): ResponseInterface
    {
        $incidentId = (int) ($this->input()['incident_id'] ?? 0);
        if ($incidentId < 1) {
            return $this->fail('Invalid incident.', 422);
        }

        $picks = (new VolunteerMatchingService())->suggestAutoTeam($incidentId);
        if ($picks === []) {
            return $this->respond([
                'message'  => 'Staffing needs are already met or no suitable volunteers found.',
                'deployed' => 0,
            ]);
        }

        $ids = array_map(static fn (array $p): int => (int) ($p['volunteer_id'] ?? 0), $picks);

        return $this->runDeploy($ids, $incidentId);
    }

    /**
     * @return array<string, mixed>
     */
    private function input(): array
    {
        return $this->request->getJSON(true) ?? $this->request->getPost() ?? [];
    }

    /**
     * @param list<int> $volunteerIds
     */
    private function runDeploy(array $volunteerIds, ?int $incidentId = null): ResponseInterface
    {
        $input      = $this->input();
        $incidentId = $incidentId ?? (int) ($input['incident_id'] ?? 0);
        $volunteerIds = array_values(array_filter($volunteerIds, static fn (int $id): bool => $id > 0));

        if ($incidentId < 1 || $volunteerIds === []) {
            return $this->fail('Invalid volunteer or incident.', 422);
        }

        try {
            $result = (new VolunteerDeploymentService())->deployMany($volunteerIds, $incidentId, auth()->id());
            $msg    = $result['deployed'] . ' volunteer(s) deployed.';
            if ($result['skipped'] > 0) {
                $msg .= ' ' . $result['skipped'] . ' skipped.';
            }

            return $this->respond([
                'message'  => $msg,
                'deployed' => $result['deployed'],
                'skipped'  => $result['skipped'],
            ]);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        }
    }
}
