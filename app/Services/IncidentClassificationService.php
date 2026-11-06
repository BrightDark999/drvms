<?php

declare(strict_types=1);

namespace App\Services;

use Config\DrmsFeatures;

/**
 * Auto-priority tagging (HIGH/MEDIUM/LOW) and staffing needs from incident type + severity.
 */
class IncidentClassificationService
{
    public function __construct(
        private DrmsFeatures $features = new DrmsFeatures(),
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function enrich(array $data): array
    {
        $type = strtolower(trim((string) ($data['incident_type'] ?? 'other')));
        if (! array_key_exists($type, $this->features->incidentTypes)) {
            $type = 'other';
        }

        $needs = $this->features->incidentTypeNeeds[$type] ?? $this->features->incidentTypeNeeds['other'];
        $severity = strtolower(trim((string) ($data['severity'] ?? 'moderate')));

        $data['incident_type'] = $type;
        $data['priority_tag']  = $this->computePriorityTag($type, $severity, $data);
        $data['needs_rescue']  = (int) ($data['needs_rescue'] ?? $needs['rescue']);
        $data['needs_medical']  = (int) ($data['needs_medical'] ?? $needs['medical']);
        $data['needs_drivers']   = (int) ($data['needs_drivers'] ?? $needs['drivers']);
        $data['required_skills_json'] = json_encode($needs['skills']);

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function computePriorityTag(string $type, string $severity, array $data): string
    {
        if ($severity === 'critical') {
            return 'HIGH';
        }
        if (in_array($type, ['fire', 'landslide', 'earthquake', 'rescue_request'], true) || $severity === 'high') {
            return 'HIGH';
        }
        if (in_array($type, ['flood', 'typhoon', 'injured_people'], true) || $severity === 'moderate') {
            return 'MEDIUM';
        }

        $desc = strtolower((string) ($data['description'] ?? ''));
        if (preg_match('/\b(trapped|casualt|death|critical|urgent)\b/', $desc)) {
            return 'HIGH';
        }

        return 'LOW';
    }

    /**
     * @return list<string>
     */
    public function requiredSkillsForIncident(array $incident): array
    {
        $raw = $incident['required_skills_json'] ?? null;
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? array_values($decoded) : [];
        }

        $type  = (string) ($incident['incident_type'] ?? 'other');
        $needs = $this->features->incidentTypeNeeds[$type] ?? $this->features->incidentTypeNeeds['other'];

        return $needs['skills'];
    }
}
