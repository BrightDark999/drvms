<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BarangayModel;
use App\Models\IncidentModel;
use App\Models\VolunteerDeploymentModel;
use App\Models\VolunteerModel;

/**
 * Skill-aware volunteer matching with match percentage (thesis main feature).
 */
class VolunteerMatchingService
{
    /** @var array<int, array<string, mixed>> */
    private array $barangayCache = [];

    public function __construct(
        private VolunteerSkillService $skillService = new VolunteerSkillService(),
        private IncidentClassificationService $classifier = new IncidentClassificationService(),
    ) {
    }

    /**
     * @return array{
     *   required_skills:list<string>,
     *   staffing:array{rescue:int, medical:int, drivers:int},
     *   ranked:list<array<string,mixed>>
     * }
     */
    public function recommendForIncident(int $incidentId, int $limit = 50): array
    {
        $incident = model(IncidentModel::class)->find($incidentId);
        if (! $incident) {
            return ['required_skills' => [], 'staffing' => ['rescue' => 0, 'medical' => 0, 'drivers' => 0], 'ranked' => []];
        }

        $requiredSkills = $this->classifier->requiredSkillsForIncident($incident);
        $staffing       = [
            'rescue'  => (int) ($incident['needs_rescue'] ?? 0),
            'medical' => (int) ($incident['needs_medical'] ?? 0),
            'drivers' => (int) ($incident['needs_drivers'] ?? 0),
        ];

        $incLat = $this->resolveIncidentLat($incident);
        $incLng = $this->resolveIncidentLng($incident);

        $skillMap   = $this->skillService->mapAllByVolunteer();
        $workloads  = $this->activeDeploymentCounts();
        $volunteers = model(VolunteerModel::class)->whereIn('availability_status', ['available', 'on_duty'])->findAll(200);

        $this->warmBarangayCache($volunteers, $incident);

        $ranked = [];
        foreach ($volunteers as $v) {
            $vid = (int) $v['id'];
            if (($v['availability_status'] ?? '') === 'deployed') {
                continue;
            }

            $skills      = $skillMap[$vid] ?? [];
            $score       = 30.0;
            $reasons     = [];
            $skillHits   = 0;
            $matched     = [];
            $missing     = [];

            foreach ($requiredSkills as $req) {
                $found = false;
                foreach ($skills as $sk) {
                    if (strcasecmp($sk, $req) === 0) {
                        $skillHits++;
                        $matched[] = $req;
                        $score += 12;
                        $reasons[] = 'Skill: ' . $req;
                        $found     = true;
                        break;
                    }
                }
                if (! $found) {
                    $missing[] = $req;
                }
            }

            if ($skills !== [] && $requiredSkills !== []) {
                $score += ($skillHits / max(1, count($requiredSkills))) * 15;
            }

            if (($v['availability_status'] ?? '') === 'available') {
                $score += 20;
                $reasons[] = 'Available now';
            }

            $exp = (int) ($v['experience_years'] ?? 0);
            if ($exp >= 3) {
                $score += min(10, $exp * 2);
                $reasons[] = $exp . ' yrs experience';
            }

            $fatigue = (int) ($v['fatigue_level'] ?? 0);
            if ($fatigue <= 20) {
                $score += 6;
                $reasons[] = 'Low fatigue';
            } elseif ($fatigue >= 70) {
                $score -= 20;
                $reasons[] = 'High fatigue (' . $fatigue . '%)';
            }

            $deployCount = $workloads[$vid] ?? 0;
            if ($deployCount === 0) {
                $score += 8;
                $reasons[] = 'No active deployments';
            } elseif ($deployCount >= 2) {
                $score -= 15;
                $reasons[] = 'High workload (' . $deployCount . ' active)';
            }

            if (! empty($incident['barangay_id']) && (int) ($v['barangay_id'] ?? 0) === (int) $incident['barangay_id']) {
                $score += 15;
                $reasons[] = 'Same barangay';
            }

            $distanceKm = null;
            $vLat       = $this->volunteerLat($v);
            $vLng       = $this->volunteerLng($v);
            if ($incLat !== null && $incLng !== null && $vLat !== null && $vLng !== null) {
                $distanceKm = $this->haversineKm($incLat, $incLng, $vLat, $vLng);
                if ($distanceKm <= 5) {
                    $score += 20;
                    $reasons[] = 'Within 5 km';
                } elseif ($distanceKm <= 15) {
                    $score += 12;
                    $reasons[] = 'Within 15 km';
                } elseif ($distanceKm <= 30) {
                    $score += 5;
                }
            }

            $matchPct = (int) min(99, max(35, round($score * 0.85)));

            $ranked[] = [
                'volunteer_id'        => $vid,
                'full_name'           => (string) $v['full_name'],
                'skills'              => $skills,
                'matched_skills'      => $matched,
                'missing_skills'      => $missing,
                'score'               => round($score, 2),
                'match_pct'           => $matchPct,
                'reasons'             => array_values(array_unique($reasons)),
                'distance_km'         => $distanceKm !== null ? round($distanceKm, 1) : null,
                'workload'            => $deployCount,
                'latitude'            => $vLat,
                'longitude'           => $vLng,
                'availability_status' => (string) ($v['availability_status'] ?? ''),
                'experience_years'    => $exp,
                'fatigue_level'       => $fatigue,
            ];
        }

        usort($ranked, static fn ($a, $b) => $b['score'] <=> $a['score']);

        return [
            'required_skills' => $requiredSkills,
            'staffing'        => $staffing,
            'ranked'          => array_slice($ranked, 0, $limit),
        ];
    }

    /**
     * Staffing gaps vs volunteers already deployed to this incident.
     *
     * @return array{
     *   rescue:array{needed:int, assigned:int, gap:int},
     *   medical:array{needed:int, assigned:int, gap:int},
     *   drivers:array{needed:int, assigned:int, gap:int},
     *   total_needed:int,
     *   total_assigned:int
     * }
     */
    public function staffingFulfillment(int $incidentId): array
    {
        $incident = model(IncidentModel::class)->find($incidentId);
        if (! $incident) {
            return $this->emptyFulfillment();
        }

        $needed = [
            'rescue'  => (int) ($incident['needs_rescue'] ?? 0),
            'medical' => (int) ($incident['needs_medical'] ?? 0),
            'drivers' => (int) ($incident['needs_drivers'] ?? 0),
        ];

        $assigned = ['rescue' => 0, 'medical' => 0, 'drivers' => 0];
        $rows     = model(VolunteerDeploymentModel::class)
            ->where('incident_id', $incidentId)
            ->where('status', 'deployed')
            ->findAll(100);

        $skillMap = $this->skillService->mapAllByVolunteer();
        foreach ($rows as $row) {
            $skills = $skillMap[(int) ($row['volunteer_id'] ?? 0)] ?? [];
            if ($this->hasSkill($skills, 'Rescue')) {
                $assigned['rescue']++;
            }
            if ($this->hasSkill($skills, 'Medical')) {
                $assigned['medical']++;
            }
            if ($this->hasSkill($skills, 'Driving')) {
                $assigned['drivers']++;
            }
        }

        $out = [];
        foreach (['rescue', 'medical', 'drivers'] as $role) {
            $out[$role] = [
                'needed'   => $needed[$role],
                'assigned' => min($assigned[$role], $needed[$role]),
                'gap'      => max(0, $needed[$role] - $assigned[$role]),
            ];
        }

        $out['total_needed']   = array_sum($needed);
        $out['total_assigned'] = (int) count($rows);

        return $out;
    }

    /**
     * Pick volunteers to fill remaining staffing gaps (does not deploy).
     *
     * @return list<array<string, mixed>>
     */
    public function suggestAutoTeam(int $incidentId): array
    {
        $fulfillment = $this->staffingFulfillment($incidentId);
        $result      = $this->recommendForIncident($incidentId, 50);
        $roleSkills  = ['rescue' => 'Rescue', 'medical' => 'Medical', 'drivers' => 'Driving'];
        $picks       = [];
        $used        = [];

        foreach (['rescue', 'medical', 'drivers'] as $role) {
            $gap = (int) ($fulfillment[$role]['gap'] ?? 0);
            if ($gap <= 0) {
                continue;
            }

            $targetSkill = $roleSkills[$role];
            foreach ($result['ranked'] as $row) {
                if ($gap <= 0) {
                    break;
                }
                $vid = (int) ($row['volunteer_id'] ?? 0);
                if (in_array($vid, $used, true)) {
                    continue;
                }
                if (! $this->hasSkill($row['skills'] ?? [], $targetSkill)) {
                    continue;
                }

                $picks[] = array_merge($row, ['suggested_role' => $role]);
                $used[]  = $vid;
                $gap--;
            }
        }

        return $picks;
    }

    /**
     * Summary stats for the matching dashboard header.
     *
     * @param list<array<string, mixed>> $ranked
     * @return array{available:int, avg_match:int, nearest_km:float|null}
     */
    public function matchStats(array $ranked): array
    {
        if ($ranked === []) {
            return ['available' => 0, 'avg_match' => 0, 'nearest_km' => null];
        }

        $sum   = 0;
        $near  = null;
        foreach ($ranked as $r) {
            $sum += (int) ($r['match_pct'] ?? 0);
            $d = $r['distance_km'] ?? null;
            if ($d !== null && ($near === null || $d < $near)) {
                $near = (float) $d;
            }
        }

        return [
            'available'  => count($ranked),
            'avg_match'  => (int) round($sum / count($ranked)),
            'nearest_km' => $near,
        ];
    }

    /**
     * Available volunteer pool counts by core skill.
     *
     * @return array{Rescue:int, Medical:int, Driving:int, total:int}
     */
    public function poolStats(): array
    {
        $skillMap = $this->skillService->mapAllByVolunteer();
        $counts   = ['Rescue' => 0, 'Medical' => 0, 'Driving' => 0, 'total' => 0];

        $volunteers = model(VolunteerModel::class)->whereIn('availability_status', ['available', 'on_duty'])->findAll(200);
        foreach ($volunteers as $v) {
            $skills = $skillMap[(int) $v['id']] ?? [];
            $counts['total']++;
            foreach (['Rescue', 'Medical', 'Driving'] as $sk) {
                if ($this->hasSkill($skills, $sk)) {
                    $counts[$sk]++;
                }
            }
        }

        return $counts;
    }

    /**
     * Active incidents that may need volunteer dispatch, highest priority first.
     *
     * @return list<array<string, mixed>>
     */
    public function incidentQueue(int $limit = 20): array
    {
        $rows = model(IncidentModel::class)
            ->whereIn('status', ['reported', 'verified', 'in_progress'])
            ->orderBy('id', 'DESC')
            ->findAll($limit);

        $priorityOrder = ['HIGH' => 0, 'MEDIUM' => 1, 'LOW' => 2];
        $severityOrder = ['critical' => 0, 'high' => 1, 'moderate' => 2, 'low' => 3];

        usort($rows, static function (array $a, array $b) use ($priorityOrder, $severityOrder): int {
            $pa = $priorityOrder[strtoupper((string) ($a['priority_tag'] ?? 'LOW'))] ?? 9;
            $pb = $priorityOrder[strtoupper((string) ($b['priority_tag'] ?? 'LOW'))] ?? 9;
            if ($pa !== $pb) {
                return $pa <=> $pb;
            }
            $sa = $severityOrder[strtolower((string) ($a['severity'] ?? 'moderate'))] ?? 9;
            $sb = $severityOrder[strtolower((string) ($b['severity'] ?? 'moderate'))] ?? 9;
            if ($sa !== $sb) {
                return $sa <=> $sb;
            }

            return (int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0);
        });

        return $rows;
    }

    /**
     * Incidents a volunteer may view — at least one approved skill matches required skills.
     *
     * @return list<array<string, mixed>>
     */
    public function incidentQueueForVolunteer(int $volunteerId, int $limit = 20): array
    {
        $skillNames = (new VolunteerSkillRequestService())->skillNamesForVolunteer($volunteerId);
        $queue      = $this->incidentQueue(max(50, $limit * 3));
        $filtered   = [];

        foreach ($queue as $incident) {
            if ($this->incidentVisibleToVolunteer($incident, $skillNames)) {
                $filtered[] = $incident;
            }
            if (count($filtered) >= $limit) {
                break;
            }
        }

        return $filtered;
    }

    /**
     * @param list<string> $volunteerSkillNames
     */
    public function incidentVisibleToVolunteer(array $incident, array $volunteerSkillNames): bool
    {
        $required = $this->classifier->requiredSkillsForIncident($incident);

        return (new VolunteerSkillRequestService())->incidentMatchesVolunteerSkills($required, $volunteerSkillNames);
    }

    /** @return array<int, int> */
    private function activeDeploymentCounts(): array
    {
        $counts = [];
        $rows   = model(VolunteerDeploymentModel::class)->where('status', 'deployed')->findAll(500);
        foreach ($rows as $row) {
            $vid = (int) ($row['volunteer_id'] ?? 0);
            $counts[$vid] = ($counts[$vid] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * @param list<array<string, mixed>> $volunteers
     * @param array<string, mixed> $incident
     */
    private function warmBarangayCache(array $volunteers, array $incident): void
    {
        $ids = [];
        if (! empty($incident['barangay_id'])) {
            $ids[] = (int) $incident['barangay_id'];
        }
        foreach ($volunteers as $v) {
            if (! empty($v['barangay_id'])) {
                $ids[] = (int) $v['barangay_id'];
            }
        }
        $ids = array_values(array_unique(array_filter($ids)));
        if ($ids === []) {
            return;
        }

        foreach (model(BarangayModel::class)->whereIn('id', $ids)->findAll() as $b) {
            $this->barangayCache[(int) $b['id']] = $b;
        }
    }

    private function resolveIncidentLat(array $incident): ?float
    {
        $lat = $this->floatOrNull($incident['latitude'] ?? null);
        if ($lat !== null) {
            return $lat;
        }
        if (! empty($incident['barangay_id'])) {
            $b = $this->barangay((int) $incident['barangay_id']);

            return $b ? $this->floatOrNull($b['latitude'] ?? null) : null;
        }

        return null;
    }

    private function resolveIncidentLng(array $incident): ?float
    {
        $lng = $this->floatOrNull($incident['longitude'] ?? null);
        if ($lng !== null) {
            return $lng;
        }
        if (! empty($incident['barangay_id'])) {
            $b = $this->barangay((int) $incident['barangay_id']);

            return $b ? $this->floatOrNull($b['longitude'] ?? null) : null;
        }

        return null;
    }

    private function volunteerLat(array $v): ?float
    {
        if (empty($v['barangay_id'])) {
            return null;
        }
        $b = $this->barangay((int) $v['barangay_id']);

        return $b ? $this->floatOrNull($b['latitude'] ?? null) : null;
    }

    private function volunteerLng(array $v): ?float
    {
        if (empty($v['barangay_id'])) {
            return null;
        }
        $b = $this->barangay((int) $v['barangay_id']);

        return $b ? $this->floatOrNull($b['longitude'] ?? null) : null;
    }

    /** @return array<string, mixed>|null */
    private function barangay(int $id): ?array
    {
        if (isset($this->barangayCache[$id])) {
            return $this->barangayCache[$id];
        }
        $b = model(BarangayModel::class)->find($id);
        if ($b) {
            $this->barangayCache[$id] = $b;
        }

        return $b ?: null;
    }

    /**
     * @param list<string> $skills
     */
    private function hasSkill(array $skills, string $target): bool
    {
        foreach ($skills as $sk) {
            if (strcasecmp($sk, $target) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{
     *   rescue:array{needed:int, assigned:int, gap:int},
     *   medical:array{needed:int, assigned:int, gap:int},
     *   drivers:array{needed:int, assigned:int, gap:int},
     *   total_needed:int,
     *   total_assigned:int
     * }
     */
    private function emptyFulfillment(): array
    {
        $zero = ['needed' => 0, 'assigned' => 0, 'gap' => 0];

        return [
            'rescue'          => $zero,
            'medical'         => $zero,
            'drivers'         => $zero,
            'total_needed'    => 0,
            'total_assigned'  => 0,
        ];
    }

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a    = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function floatOrNull(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }

        return (float) $v;
    }
}
