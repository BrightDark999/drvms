<?php

declare(strict_types=1);

namespace App\Services;

use Config\AuthGroups;

/**
 * Shared auth catalog — roles, permissions, and matrix for API consumers.
 */
class AuthCatalogService
{
    /**
     * @return array{
     *   roles: list<array{key:string, title:string, description:string, permissions:list<string>}>,
     *   permissions: array<string, string>,
     *   default_role: string
     * }
     */
    public function catalog(): array
    {
        $config = config(AuthGroups::class);

        $roles = [];
        foreach ($config->groups as $key => $meta) {
            $roles[] = [
                'key'         => $key,
                'title'       => (string) ($meta['title'] ?? $key),
                'description' => (string) ($meta['description'] ?? ''),
                'permissions' => $this->permissionsForGroup($key, $config),
            ];
        }

        return [
            'roles'       => $roles,
            'permissions' => $config->permissions,
            'default_role'=> $config->defaultGroup,
        ];
    }

    /**
     * @return list<string>
     */
    public function permissionsForGroup(string $group, ?AuthGroups $config = null): array
    {
        $config ??= config(AuthGroups::class);
        $matrix   = $config->matrix[$group] ?? [];

        return is_array($matrix) ? array_values($matrix) : [];
    }

    /**
     * @return array{authenticated:bool, user?:array<string,mixed>}
     */
    public function currentUserPayload(): array
    {
        if (! auth()->loggedIn()) {
            return ['authenticated' => false];
        }

        $user = auth()->user();
        if ($user === null) {
            return ['authenticated' => false];
        }

        $groups = method_exists($user, 'getGroups') ? $user->getGroups() : [];
        $perms  = [];
        foreach (array_keys(config(AuthGroups::class)->permissions) as $perm) {
            if ($user->can($perm)) {
                $perms[] = $perm;
            }
        }

        $profile = [];
        try {
            $profile = (new UserManagementService())->find((int) $user->id);
        } catch (\Throwable) {
            $profile = [];
        }

        return [
            'authenticated' => true,
            'user'          => [
                'id'               => (int) $user->id,
                'username'         => (string) ($user->username ?? ''),
                'email'            => (string) ($user->email ?? ''),
                'groups'           => $groups,
                'primary_role'     => (string) ($profile['group'] ?? ($groups[0] ?? '')),
                'account_status'   => (string) ($profile['account_status'] ?? ''),
                'permissions'      => $perms,
                'full_name'        => (string) ($profile['full_name'] ?? ''),
                'barangay_id'      => $profile['barangay_id'] ?? null,
            ],
        ];
    }
}
