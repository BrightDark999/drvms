<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User as UserEntity;
use CodeIgniter\Shield\Models\UserModel;

/**
 * Sample login accounts — one per DRMS role (Shield group).
 * Password for all test users: Drms@2026
 *
 * Run: php spark db:seed DrmsTestUsersSeeder
 */
class DrmsTestUsersSeeder extends Seeder
{
    /** Shared password for local/testing only — change in production. */
    public const TEST_PASSWORD = 'Drms@2026';

    /**
     * @var list<array{username:string, email:string, group:string, label:string}>
     */
    private array $accounts = [
        ['username' => 'mdrrmo', 'email' => 'mdrrmo@drms.local', 'group' => 'mdrrmo', 'label' => 'MDRRMO / LGU Staff'],
        ['username' => 'volunteer', 'email' => 'volunteer@drms.local', 'group' => 'volunteer', 'label' => 'Volunteer'],
        ['username' => 'donor', 'email' => 'donor@drms.local', 'group' => 'donor', 'label' => 'Donor'],
        ['username' => 'evac_manager', 'email' => 'evac@drms.local', 'group' => 'evac_manager', 'label' => 'Evacuation Center Manager'],
    ];

    public function run(): void
    {
        $userModel = model(UserModel::class);

        // Ensure default admin exists (created manually or via shield:user)
        $this->ensureAdmin($userModel);

        foreach ($this->accounts as $acc) {
            $this->createOrUpdateUser($userModel, $acc);
        }

        CLI::write('DRMS test users ready. Password for all: ' . self::TEST_PASSWORD, 'green');
        CLI::write('Login URL: ' . site_url('login'), 'yellow');
    }

    private function ensureAdmin(UserModel $userModel): void
    {
        $user = $userModel->findByCredentials(['email' => 'admin@drms.local'])
            ?? $userModel->findByCredentials(['username' => 'admin']);

        if ($user === null) {
            $user = new UserEntity([
                'username' => 'admin',
                'email'    => 'admin@drms.local',
                'password' => self::TEST_PASSWORD,
            ]);
            $userModel->save($user);
            $user = $userModel->findById($userModel->getInsertID());
            $user->addGroup('admin');
            CLI::write('Created admin: admin / admin@drms.local', 'green');
        } else {
            // Reset password for testing and ensure group/active
            $user->password = self::TEST_PASSWORD;
            $userModel->save($user);
            $user->syncGroups('admin');
            CLI::write('Updated admin password for testing: admin', 'cyan');
        }

        $userModel->activate($user);
    }

    /**
     * @param array{username:string, email:string, group:string, label:string} $acc
     */
    private function createOrUpdateUser(UserModel $userModel, array $acc): void
    {
        $user = $userModel->findByCredentials(['email' => $acc['email']])
            ?? $userModel->findByCredentials(['username' => $acc['username']]);

        if ($user === null) {
            $user = new UserEntity([
                'username' => $acc['username'],
                'email'    => $acc['email'],
                'password' => self::TEST_PASSWORD,
            ]);
            $userModel->save($user);
            $user = $userModel->findById($userModel->getInsertID());
            $user->addGroup($acc['group']);
            CLI::write('Created ' . $acc['label'] . ': ' . $acc['username'], 'green');
        } else {
            $user->password = self::TEST_PASSWORD;
            $userModel->save($user);
            // One group per test account for clear RBAC demos
            $user->syncGroups($acc['group']);
            CLI::write('Updated ' . $acc['label'] . ': ' . $acc['username'], 'cyan');
        }

        $userModel->activate($user);

        if ($acc['group'] === 'volunteer' && $user !== null) {
            (new \App\Services\UserRegistrationService())->ensureVolunteerProfile((int) $user->id);
        }

        if ($acc['group'] === 'evac_manager' && $user !== null) {
            $this->assignDefaultEvacCenter((int) $user->id);
        }
    }

    private function assignDefaultEvacCenter(int $userId): void
    {
        $profiles = new \App\Services\UserProfileService();
        if ($profiles->getAssignedEvacuationCenterId($userId) !== null) {
            return;
        }

        $first = model(\App\Models\EvacuationCenterModel::class)->orderBy('id', 'ASC')->first();
        if ($first) {
            $profiles->ensureProfile($userId, [
                'assigned_evacuation_center_id' => (int) $first['id'],
            ]);
        }
    }
}
