<?php

declare(strict_types=1);

namespace App\Validation;

class VolunteerRules
{
    /**
     * @return array<string, string|list<string>>
     */
    public static function save(): array
    {
        return [
            'full_name'           => 'required|min_length[2]|max_length[160]',
            'email'               => 'required|valid_email|max_length[191]',
            'phone'               => 'permit_empty|max_length[48]',
            'barangay_id'         => 'permit_empty|is_natural_no_zero',
            'address'             => 'permit_empty|max_length[2000]',
            'availability_status' => 'required|in_list[available,on_duty,unavailable]',
            'notes'               => 'permit_empty|max_length[5000]',
        ];
    }
}
