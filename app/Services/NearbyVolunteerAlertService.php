<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BarangayModel;
use App\Models\IncidentModel;
use App\Models\VolunteerModel;
use Config\Automation;

class NearbyVolunteerAlertService
{
    public function __construct(
        private NotificationService $notifications = new NotificationService(),
        private UserNotificationService $inApp = new UserNotificationService(),
        private VolunteerSkillService $skills = new VolunteerSkillService(),
        private Automation $config = new Automation(),
    ) {
    }

    public function alertForIncident(array $incident): int
    {
        $incLat = $this->resolveLat($incident);
        $incLng = $this->resolveLng($incident);
        if ($incLat === null || $incLng === null) {
            return 0;
        }

        $required = json_decode((string) ($incident['required_skills_json'] ?? '[]'), true);
        if (! is_array($required)) {
            $required = [];
        }

        $skillMap   = $this->skills->mapAllByVolunteer();
        $volunteers = model(VolunteerModel::class)->where('availability_status', 'available')->findAll(200);
        $barangays  = $this->loadBarangayCoords();
        $ref        = $incident['reference_code'] ?? 'INC';
        $title      = $incident['title'] ?? 'Disaster incident';
        $count      = 0;

        foreach ($volunteers as $v) {
            $vid = (int) $v['id'];
            $vLat = $this->volunteerLat($v, $barangays);
            $vLng = $this->volunteerLng($v, $barangays);
            if ($vLat === null || $vLng === null) {
                continue;
            }

            $dist = $this->haversineKm($incLat, $incLng, $vLat, $vLng);
            if ($dist > 20) {
                continue;
            }

            $vSkills = array_map('strtolower', $skillMap[$vid] ?? []);
            $match   = $required === [] || count(array_intersect($vSkills, array_map('strtolower', $required))) > 0;
            if (! $match) {
                continue;
            }

            $msg = "DRMS: {$ref} near you ({$dist} km) — {$title}. Check matching: " . base_url('admin/matching?incident_id=' . ($incident['id'] ?? ''));

            if (($v['email'] ?? '') !== '') {
                $this->notifications->queue('email', (string) $v['email'], '[DRMS] Nearby incident alert', $msg, ['event' => 'volunteer.nearby']);
            }

            if ($this->config->smsEnabled && ($v['phone'] ?? '') !== '') {
                $this->notifications->queue('sms', (string) $v['phone'], 'DRMS Alert', $msg, ['event' => 'volunteer.nearby']);
            }

            if (! empty($v['user_id'])) {
                $this->inApp->notify(
                    (int) $v['user_id'],
                    'Nearby incident: ' . $ref,
                    mb_substr($title, 0, 100),
                    base_url('admin/matching?incident_id=' . ($incident['id'] ?? '')),
                );
            }

            $count++;
        }

        return $count;
    }

    private function resolveLat(array $incident): ?float
    {
        if (! empty($incident['latitude'])) {
            return (float) $incident['latitude'];
        }
        if (! empty($incident['barangay_id'])) {
            $b = model(BarangayModel::class)->find((int) $incident['barangay_id']);

            return $b ? $this->floatOrNull($b['latitude'] ?? null) : null;
        }

        return null;
    }

    /**
     * @return array<int, array{lat: ?float, lng: ?float}>
     */
    private function loadBarangayCoords(): array
    {
        $rows = model(BarangayModel::class)->findAll();
        $map  = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $map[$id] = [
                'lat' => $this->floatOrNull($row['latitude'] ?? null),
                'lng' => $this->floatOrNull($row['longitude'] ?? null),
            ];
        }

        return $map;
    }

    private function volunteerLat(array $v, array $barangays): ?float
    {
        if (! empty($v['barangay_id'])) {
            $coords = $barangays[(int) $v['barangay_id']] ?? null;

            return $coords['lat'] ?? null;
        }

        return null;
    }

    private function volunteerLng(array $v, array $barangays): ?float
    {
        if (! empty($v['barangay_id'])) {
            $coords = $barangays[(int) $v['barangay_id']] ?? null;

            return $coords['lng'] ?? null;
        }

        return null;
    }

    private function resolveLng(array $incident): ?float
    {
        if (! empty($incident['longitude'])) {
            return (float) $incident['longitude'];
        }
        if (! empty($incident['barangay_id'])) {
            $b = model(BarangayModel::class)->find((int) $incident['barangay_id']);

            return $b ? $this->floatOrNull($b['longitude'] ?? null) : null;
        }

        return null;
    }

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a    = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return round($r * 2 * atan2(sqrt($a), sqrt(1 - $a)), 1);
    }

    private function floatOrNull(mixed $v): ?float
    {
        return ($v === null || $v === '') ? null : (float) $v;
    }
}
