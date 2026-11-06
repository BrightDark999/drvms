<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Sample rows for client demo / presentation mode (no database required).
 */
class PresentationFixtures
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function barangays(): array
    {
        return [
            ['id' => 1, 'name' => 'Poblacion', 'latitude' => 14.5171, 'longitude' => 121.2672],
            ['id' => 2, 'name' => 'Rizal', 'latitude' => 14.5050, 'longitude' => 121.2550],
            ['id' => 3, 'name' => 'San Juan', 'latitude' => 14.5280, 'longitude' => 121.2780],
            ['id' => 4, 'name' => 'San Jose', 'latitude' => 14.5120, 'longitude' => 121.2490],
        ];
    }

    /**
     * @return array<string, int|string>
     */
    public static function dashboardStats(): array
    {
        return [
            'active_incidents'      => 4,
            'volunteers_on_duty'    => 18,
            'evacuation_centers'    => 3,
            'evacuation_occupancy'  => '72%',
            'pending_verifications' => 2,
            'total_volunteers'      => 24,
        ];
    }

    /**
     * @return list<array{type:string, level:string, title:string, message:string, url?:string}>
     */
    public static function dashboardAlerts(): array
    {
        return [
            [
                'type'    => 'incident',
                'level'   => 'danger',
                'title'   => 'Critical flood — Poblacion',
                'message' => '2 incidents awaiting verification in the last 24 hours.',
                'url'     => base_url('admin/incidents'),
            ],
            [
                'type'    => 'evacuation',
                'level'   => 'warning',
                'title'   => 'Evacuation center nearing capacity',
                'message' => 'Municipal gym at 72% occupancy — consider opening auxiliary shelter.',
                'url'     => base_url('admin/evacuation-centers'),
            ],
        ];
    }

    public static function incidentsChart(): array
    {
        return [
            'status' => 'ok',
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'values' => [2, 4, 3, 5, 6, 4, 3],
            'source' => 'demo',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function incidentRows(): array
    {
        return [
            [
                'id'             => 1001,
                'reference_code' => 'INC-20260521-1001',
                'title'          => 'Flooding along main road — Poblacion',
                'incident_type'  => 'flood',
                'severity'       => 'high',
                'priority_tag'   => 'HIGH',
                'status'         => 'verified',
                'barangay_name'  => 'Poblacion',
                'created_at'     => date('Y-m-d H:i', strtotime('-2 hours')),
                'latitude'       => 14.5171,
                'longitude'      => 121.2672,
                'barangay_id'    => 1,
            ],
            [
                'id'             => 1002,
                'reference_code' => 'INC-20260521-1002',
                'title'          => 'Landslide risk — San Juan hillside',
                'incident_type'  => 'landslide',
                'severity'       => 'critical',
                'priority_tag'   => 'HIGH',
                'status'         => 'in_progress',
                'barangay_name'  => 'San Juan',
                'created_at'     => date('Y-m-d H:i', strtotime('-5 hours')),
                'latitude'       => 14.5280,
                'longitude'      => 121.2780,
                'barangay_id'    => 3,
            ],
            [
                'id'             => 1003,
                'reference_code' => 'INC-20260520-1003',
                'title'          => 'Power outage affecting evacuation center',
                'incident_type'  => 'other',
                'severity'       => 'moderate',
                'priority_tag'   => 'MEDIUM',
                'status'         => 'reported',
                'barangay_name'  => 'Rizal',
                'created_at'     => date('Y-m-d H:i', strtotime('-1 day')),
                'latitude'       => 14.5050,
                'longitude'      => 121.2550,
                'barangay_id'    => 2,
            ],
        ];
    }

    /**
     * @return array{type:string, features:list<array<string, mixed>>}
     */
    public static function incidentsMapGeoJson(): array
    {
        helper('drms');

        $features = [];
        foreach (self::incidentRows() as $inc) {
            $marker = drms_incident_map_marker(
                (string) ($inc['incident_type'] ?? 'other'),
                (string) ($inc['severity'] ?? 'moderate'),
            );

            $features[] = [
                'type'       => 'Feature',
                'geometry'   => [
                    'type'        => 'Point',
                    'coordinates' => [(float) $inc['longitude'], (float) $inc['latitude']],
                ],
                'properties' => array_merge([
                    'id'        => (int) $inc['id'],
                    'reference' => $inc['reference_code'],
                    'title'     => $inc['title'],
                    'severity'  => $inc['severity'],
                    'status'    => $inc['status'],
                    'url'       => base_url('admin/incidents'),
                ], $marker),
            ];
        }

        return ['type' => 'FeatureCollection', 'features' => $features];
    }

    /**
     * @return list<list<float>>
     */
    public static function incidentsHeatmapPoints(): array
    {
        return [
            [14.5171, 121.2672, 0.8],
            [14.5280, 121.2780, 1.0],
            [14.5050, 121.2550, 0.5],
        ];
    }

    /**
     * @return list<list<string>>
     */
    public static function volunteersTable(): array
    {
        return [
            [
                'Juan Dela Cruz',
                'juan.delacruz@example.com',
                '09171234567',
                '<span class="badge badge-success">Available</span>',
                '<span class="badge badge-success">Verified</span>',
                'Poblacion',
                drms_table_actions(2001),
            ],
            [
                'Maria Santos',
                'maria.santos@example.com',
                '09189876543',
                '<span class="badge badge-warning">Deployed</span>',
                '<span class="badge badge-warning">Pending</span>',
                'San Juan',
                drms_table_actions(2002),
            ],
            [
                'Rico Mendoza',
                'rico.mendoza@example.com',
                '09201112233',
                '<span class="badge badge-success">Available</span>',
                '<span class="badge badge-success">Verified</span>',
                'Rizal',
                drms_table_actions(2003),
            ],
        ];
    }

    /**
     * @return list<list<string>>
     */
    public static function evacuationCentersTable(): array
    {
        return [
            [
                'Municipal gym (demo)',
                'Poblacion',
                '180',
                '130',
                '72%',
                '<span class="badge badge-warning">Active</span>',
                drms_table_actions(5001),
            ],
            [
                'Barangay Rizal covered court',
                'Rizal',
                '90',
                '40',
                '44%',
                '<span class="badge badge-success">Open</span>',
                drms_table_actions(5002),
            ],
        ];
    }

    /**
     * @return list<list<string>>
     */
    public static function donationsTable(): array
    {
        return [
            [
                'Community donor (demo)',
                'Cash',
                '₱25,000',
                date('Y-m-d', strtotime('-2 days')),
                '<span class="badge badge-success">Received</span>',
                drms_table_actions(6001),
            ],
            [
                'NGO Relief Partners',
                'Goods',
                '120 hygiene kits',
                date('Y-m-d', strtotime('-5 days')),
                '<span class="badge badge-success">Received</span>',
                drms_table_actions(6002),
            ],
        ];
    }

    /**
     * @return list<list<string>>
     */
    public static function notificationsTable(): array
    {
        return [
            [
                date('Y-m-d H:i', strtotime('-1 hour')),
                'SMS',
                '09171234567',
                'Evacuation advisory — San Juan',
                '<span class="badge badge-success">Sent</span>',
                drms_table_actions(7001, false),
            ],
            [
                date('Y-m-d H:i', strtotime('-4 hours')),
                'Email',
                'mdrrmo@drms.local',
                'Daily operations digest',
                '<span class="badge badge-success">Sent</span>',
                drms_table_actions(7002, false),
            ],
        ];
    }

    /**
     * @return list<list<string>>
     */
    public static function usersTable(): array
    {
        helper('crud');

        return [
            [
                'pending_volunteer',
                'new.volunteer@example.com',
                'volunteer',
                '<span class="badge badge-warning">Pending</span>',
                date('Y-m-d H:i', strtotime('-2 days')),
                drms_user_table_actions(8001, 'pending', 'volunteer', 'pending', true),
            ],
            [
                'resident_pending',
                'resident.helper@example.com',
                'resident',
                '<span class="badge badge-warning">Pending</span>',
                date('Y-m-d H:i', strtotime('-1 day')),
                drms_user_table_actions(8002, 'pending', 'resident', 'none', false),
            ],
        ];
    }

    /**
     * @return list<list<string>>
     */
    public static function auditTrailTable(): array
    {
        $rows = [
            ['when' => '-30 minutes', 'user' => 'mdrrmo', 'action' => 'incident.verify', 'entity' => 'Incident · INC-DEMO-9001'],
            ['when' => '-2 hours', 'user' => 'mdrrmo', 'action' => 'volunteer.update', 'entity' => 'Volunteer #2'],
            ['when' => '-1 day', 'user' => 'volunteer', 'action' => 'volunteer.deploy', 'entity' => 'Deployment #1'],
        ];
        $data = [];
        foreach ($rows as $i => $r) {
            $at = date('Y-m-d H:i:s', strtotime($r['when']));
            $badge = match (true) {
                str_contains($r['action'], 'delete') => 'danger',
                str_contains($r['action'], 'deploy') => 'warning',
                str_contains($r['action'], 'verify')  => 'primary',
                default                             => 'info',
            };
            $label = ucwords(str_replace('.', ' ', $r['action']));
            $data[] = [
                '<div class="drms-audit-when"><strong>' . esc(date('M j, Y', strtotime($at))) . '</strong>'
                    . '<div class="small text-muted">' . esc(date('g:i A', strtotime($at))) . '</div></div>',
                esc($r['user']),
                '<span class="badge badge-' . $badge . ' drms-audit-badge">' . esc($label) . '</span>',
                esc($r['entity']),
                '<button type="button" class="btn btn-xs btn-outline-secondary drms-icon-action btn-audit-detail" data-id="' . ($i + 1) . '" title="View details"><i class="fas fa-search"></i></button>',
            ];
        }

        return $data;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function socialFeed(): array
    {
        return [
            [
                'id'          => 9001,
                'platform'    => 'facebook',
                'author'      => 'Barangay San Juan Page',
                'content'     => 'Road impassable due to mudslide near the elementary school. Families need assistance.',
                'flag_level'  => 'high',
                'status'      => 'flagged',
                'posted_at'   => date('Y-m-d H:i', strtotime('-45 minutes')),
            ],
            [
                'id'          => 9002,
                'platform'    => 'twitter',
                'author'      => '@baras_residents',
                'content'     => 'Heavy rain in Poblacion — waist-deep flood reported on M.H. del Pilar St.',
                'flag_level'  => 'medium',
                'status'      => 'flagged',
                'posted_at'   => date('Y-m-d H:i', strtotime('-2 hours')),
            ],
        ];
    }
}
