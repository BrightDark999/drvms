<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Validation rules for incident forms (used by controller + API).
 */
class IncidentRules
{
    /**
     * @return array<string, string|list<string>>
     */
    public static function create(): array
    {
        $types = implode(',', array_keys(config('DrmsFeatures')->incidentTypes));

        return [
            'title'         => 'required|min_length[3]|max_length[255]',
            'description'   => 'permit_empty|max_length[5000]',
            'severity'      => 'required|in_list[low,moderate,high,critical]',
            'incident_type' => "permit_empty|in_list[{$types}]",
            'barangay_id'   => 'permit_empty|is_natural_no_zero',
            'latitude'      => 'permit_empty|decimal',
            'longitude'     => 'permit_empty|decimal',
        ];
    }

    /**
     * @return array<string, string|list<string>>
     */
    public static function update(): array
    {
        $types = implode(',', array_keys(config('DrmsFeatures')->incidentTypes));

        return [
            'title'         => 'required|min_length[3]|max_length[255]',
            'description'   => 'permit_empty|max_length[5000]',
            'severity'      => 'required|in_list[low,moderate,high,critical]',
            'incident_type' => "permit_empty|in_list[{$types}]",
            'status'        => 'required|in_list[reported,verified,in_progress,resolved,closed]',
            'barangay_id'   => 'permit_empty|is_natural_no_zero',
            'latitude'      => 'permit_empty|decimal',
            'longitude'     => 'permit_empty|decimal',
        ];
    }
}
