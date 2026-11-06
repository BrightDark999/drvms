<?php

declare(strict_types=1);

namespace App\Validation;

class UserRules
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function createApi(): array
    {
        $tables          = config('Auth')->tables;
        $usersTable      = $tables['users'];
        $identitiesTable = $tables['identities'];

        return [
            'username' => [
                'label'  => 'Username',
                'rules'  => "required|min_length[3]|max_length[30]|alpha_numeric_punct|is_unique[{$usersTable}.username]",
                'errors' => [
                    'is_unique' => 'This username is already in use.',
                ],
            ],
            'email' => [
                'label'  => 'Email',
                'rules'  => "required|valid_email|max_length[191]|is_unique[{$identitiesTable}.secret]",
                'errors' => [
                    'is_unique' => 'This email is already in use.',
                ],
            ],
            'password' => [
                'label' => 'Password',
                'rules' => 'required|min_length[8]|max_length[72]',
            ],
            'group'          => 'permit_empty|alpha_dash|max_length[32]',
            'full_name'      => 'permit_empty|max_length[160]',
            'phone'          => 'permit_empty|max_length[48]',
            'address'        => 'permit_empty|max_length[500]',
            'barangay_id'    => 'permit_empty|is_natural_no_zero',
            'account_status' => 'permit_empty|in_list[pending,approved,inactive,disabled,rejected]',
            'status_message' => 'permit_empty|max_length[500]',
        ];
    }

    /**
     * @return array<string, array<string, mixed>|string|list<string>>
     */
    public static function updateApi(int $userId = 0): array
    {
        $tables          = config('Auth')->tables;
        $usersTable      = $tables['users'];
        $identitiesTable = $tables['identities'];
        $userId          = max(0, $userId);

        $usernameRules = 'permit_empty|min_length[3]|max_length[30]|alpha_numeric_punct';
        $emailRules    = 'permit_empty|valid_email|max_length[191]';
        if ($userId > 0) {
            $usernameRules .= "|is_unique[{$usersTable}.username,id,{$userId}]";
            $emailRules .= "|is_unique[{$identitiesTable}.secret,user_id,{$userId}]";
        }

        return [
            'username' => [
                'label'  => 'Username',
                'rules'  => $usernameRules,
                'errors' => [
                    'is_unique' => 'This username is already in use.',
                ],
            ],
            'email' => [
                'label'  => 'Email',
                'rules'  => $emailRules,
                'errors' => [
                    'is_unique' => 'This email is already in use.',
                ],
            ],
            'password'       => 'permit_empty|min_length[8]|max_length[72]',
            'group'          => 'permit_empty|alpha_dash|max_length[32]',
            'full_name'      => 'permit_empty|max_length[160]',
            'phone'          => 'permit_empty|max_length[48]',
            'address'        => 'permit_empty|max_length[500]',
            'barangay_id'    => 'permit_empty|is_natural_no_zero',
            'account_status' => 'permit_empty|in_list[pending,approved,inactive,disabled,rejected]',
            'status_message' => 'permit_empty|max_length[500]',
        ];
    }
}
