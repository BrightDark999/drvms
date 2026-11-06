<?php

declare(strict_types=1);

/**
 * DRMS role groups — aligns with operational roles (Shield / RBAC).
 */
namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

class AuthGroups extends ShieldAuthGroups
{
    /** Default group for self-registration (inactive & pending until admin approval). */
    public string $defaultGroup = 'volunteer';

    /**
     * @var array<string, array<string, string>>
     */
    public array $groups = [
        'admin' => [
            'title'       => 'Admin',
            'description' => 'Full system configuration and audit oversight.',
        ],
        'mdrrmo' => [
            'title'       => 'MDRRMO / LGU Staff',
            'description' => 'Disaster operations, incidents, volunteers, evacuation, and coordination.',
        ],
        'volunteer' => [
            'title'       => 'Volunteer',
            'description' => 'Volunteer portal — deployments and profile.',
        ],
        // 'donor' => [
        //     'title'       => 'Donor',
        //     'description' => 'Donations and assistance records.',
        // ],
        'evac_manager' => [
            'title'       => 'Camp Manager',
            'description' => 'Evacuation center capacity and occupancy.',
        ],
    ];

    /**
     * Fine-grained permissions (expand per module as features grow).
     *
     * @var array<string, string>
     */
    public array $permissions = [
        'admin.access'          => 'Access disaster operations dashboard (AdminLTE)',
        'admin.*'               => 'Wildcard for all admin.* permissions',
        'incidents.*'           => 'Manage disaster incidents',
        'volunteers.*'          => 'Manage volunteers and deployments',
        'evacuation.*'          => 'Evacuation centers',
        'donations.*'           => 'Donations and assistance',
        'notifications.*'       => 'Send notifications and announcements',
        'reports.*'             => 'Reports and exports',
        'audit.*'               => 'View audit trail',
        'users.*'               => 'Manage user accounts and registration approval',
        'inventory.*'           => 'View full shared inventory catalog (relief scope)',
        'matching.access'       => 'View dispatch queue and personal match (read-only for volunteers)',
        'messages.access'       => 'Internal messaging',
    ];

    /**
     * Group → permission wildcards.
     *
     * @var array<string, list<string>>
     */
    public array $matrix = [
        'admin' => [
            'admin.access',
            'admin.*',
            'incidents.*',
            'volunteers.*',
            'evacuation.*',
            'donations.*',
            'notifications.*',
            'reports.*',
            'audit.*',
            'users.*',
            'inventory.*',
            'messages.access',
            'matching.access',
        ],
        'mdrrmo' => [
            'admin.access',
            'incidents.*',
            'volunteers.*',
            'evacuation.*',
            'donations.*',
            'notifications.*',
            'reports.*',
            'audit.*',
            'users.*',
            'inventory.*',
            'messages.access',
        ],
        'evac_manager' => [
            'admin.access',
            'evacuation.*',
            'reports.*',
            'messages.access',
        ],
        'volunteer' => [
            'admin.access',
            'matching.access',
            'messages.access',
        ],
        'donor' => [
            'admin.access',
            'donations.*',
            'messages.access',
        ],
        'resident' => [
            // No admin.access — residents use profile + public pages until promoted by admin.
        ],
    ];
}
