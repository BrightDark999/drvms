<?php

declare(strict_types=1);

namespace App\Validation;

class DonationRules
{
    /**
     * @return array<string, string|list<string>>
     */
    public static function save(): array
    {
        return [
            'donor_name'     => 'required|min_length[2]|max_length[160]',
            'donation_type'  => 'required|in_list[cash,in_kind,service]',
            'description'    => 'permit_empty|max_length[5000]',
            'amount_peso'    => 'permit_empty|decimal|greater_than_equal_to[0]',
            'received_at'    => 'permit_empty|valid_date',
        ];
    }
}
