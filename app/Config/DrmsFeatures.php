<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Thesis feature catalog: incident types, volunteer skills, staffing needs.
 */
class DrmsFeatures extends BaseConfig
{
    /** @var list<string> */
    public array $volunteerSkills = [
        'Medical',
        'Rescue',
        'Driving',
        'Cooking',
        'Logistics',
        'Communication',
        'IT Support',
    ];

    /** @var array<string, string> */
    public array $incidentTypes = [
        'flood'              => 'Flood',
        'fire'               => 'Fire',
        'landslide'          => 'Landslide',
        'earthquake'         => 'Earthquake',
        'typhoon'            => 'Typhoon',
        'vehicular_incident' => 'Vehicular incident / road crash',
        'injured_people'     => 'Injured people / medical',
        'rescue_request'     => 'Rescue request',
        'other'              => 'Other hazard',
    ];

    /**
     * Default staffing needs & skills per incident type.
     *
     * @var array<string, array{rescue:int, medical:int, drivers:int, skills:list<string>}>
     */
    public array $incidentTypeNeeds = [
        'flood'              => ['rescue' => 2, 'medical' => 1, 'drivers' => 1, 'skills' => ['Rescue', 'Medical', 'Driving', 'Logistics']],
        'fire'               => ['rescue' => 3, 'medical' => 2, 'drivers' => 1, 'skills' => ['Rescue', 'Medical', 'Driving']],
        'landslide'          => ['rescue' => 3, 'medical' => 1, 'drivers' => 1, 'skills' => ['Rescue', 'Medical', 'Logistics']],
        'earthquake'         => ['rescue' => 4, 'medical' => 2, 'drivers' => 2, 'skills' => ['Rescue', 'Medical', 'Driving', 'Communication']],
        'typhoon'            => ['rescue' => 2, 'medical' => 1, 'drivers' => 1, 'skills' => ['Rescue', 'Logistics', 'Communication']],
        'vehicular_incident' => ['rescue' => 2, 'medical' => 2, 'drivers' => 1, 'skills' => ['Rescue', 'Medical', 'Driving']],
        'injured_people'     => ['rescue' => 1, 'medical' => 3, 'drivers' => 1, 'skills' => ['Medical', 'Driving']],
        'rescue_request'     => ['rescue' => 4, 'medical' => 1, 'drivers' => 2, 'skills' => ['Rescue', 'Driving', 'Communication']],
        'other'              => ['rescue' => 1, 'medical' => 1, 'drivers' => 0, 'skills' => ['Communication', 'Logistics']],
    ];

    /** @var list<string> */
    public array $priorityTags = ['HIGH', 'MEDIUM', 'LOW'];

    /** @var list<string> */
    public array $accountStatuses = [
        'pending',
        'approved',
        'inactive',
        'disabled',
        'rejected',
    ];

    /**
     * Shared inventory categories (single `inventory_items` table).
     * DRMS shows only {@see inventoryDrmsCategories}; relief system shows all.
     *
     * @var array<string, string>
     */
    public array $inventoryCategories = [
        'medical'    => 'Medical supplies',
        'emergency'  => 'Emergency equipment',
        'first_aid'  => 'First aid kits',
        'food'       => 'Food & rations',
        'hygiene'    => 'Hygiene kits',
        'water'      => 'Water & sanitation',
        'shelter'    => 'Shelter materials',
        'clothing'   => 'Clothing & bedding',
        'other'      => 'Other relief goods',
    ];

    /** Categories visible in DRMS admin (medical / emergency operations). */
    public array $inventoryDrmsCategories = [
        'medical',
        'emergency',
        'first_aid',
    ];
}
