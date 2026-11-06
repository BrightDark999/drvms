<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Shield\Models\UserModel;

/**
 * Generate a Shield access token for Postman / relief API testing.
 *
 * php spark drms:api-token mdrrmo
 * php spark drms:api-token mdrrmo "Relief integration"
 */
class ApiToken extends BaseCommand
{
    protected $group       = 'DRMS';
    protected $name        = 'drms:api-token';
    protected $description = 'Generate a Bearer access token for /api/v1 Postman testing.';
    protected $usage       = 'drms:api-token [username] [label]';

    public function run(array $params): void
    {
        $username = trim((string) ($params[0] ?? 'mdrrmo'));
        $label    = trim((string) ($params[1] ?? 'postman'));

        if ($username === '') {
            CLI::error('Username required. Example: php spark drms:api-token mdrrmo');

            return;
        }

        $user = model(UserModel::class)->where('username', $username)->first();
        if ($user === null) {
            CLI::error("User not found: {$username}");

            return;
        }

        if (! (bool) $user->active) {
            CLI::error("User {$username} is not active. Approve the account in admin first.");

            return;
        }

        $token = $user->generateAccessToken($label);

        CLI::write("Access token for {$username} (label: {$label})", 'green');
        CLI::newLine();
        CLI::write($token->raw_token, 'yellow');
        CLI::newLine();
        CLI::write('Postman → Authorization → Bearer Token → paste the token above.', 'white');
        CLI::write('Test: GET ' . base_url('api/v1/auth/me'), 'white');
        CLI::write('Token is shown once — store it securely.', 'light_gray');
    }
}
