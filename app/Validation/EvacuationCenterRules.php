<?php

declare(strict_types=1);

namespace App\Validation;

class EvacuationCenterRules
{
    /**
     * @return array<string, string|list<string>>
     */
    public static function save(): array
    {
        return [
            'name'              => 'required|min_length[2]|max_length[255]',
            'barangay_id'       => 'permit_empty|is_natural_no_zero',
            'address'           => 'permit_empty|max_length[2000]',
            'capacity'          => 'required|is_natural',
            'current_occupancy' => 'required|is_natural',
            'status'            => 'required|in_list[open,full,closed]',
            'contact_phone'     => 'permit_empty|max_length[48]',
            'contact_person'    => 'permit_empty|max_length[160]',
            'intake_procedures' => 'permit_empty|max_length[8000]',
            'required_items'    => 'permit_empty|max_length[4000]',
        ];
    }
}
