<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Models\AuditLogModel;
use App\Models\BarangayModel;
use App\Models\DonationModel;
use App\Models\DonorModel;
use App\Models\EvacuationCenterModel;
use App\Models\IncidentModel;
use App\Models\VolunteerModel;
use App\Services\VolunteerSkillService;
use CodeIgniter\Database\Seeder;

/**
 * Optional starter rows for local demos (safe to re-run: skips if barangays already exist).
 */
class DrmsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $bar = model(BarangayModel::class);
        if ($bar->countAllResults() > 0) {
            return;
        }

        $b1 = $bar->insert([
            'code'      => 'BRG-POB',
            'name'      => 'Poblacion',
            'latitude'  => 14.5171,
            'longitude' => 121.2672,
        ], true);
        $b2 = $bar->insert([
            'code'      => 'BRG-SJ',
            'name'      => 'San Jose',
            'latitude'  => 14.5220,
            'longitude' => 121.2600,
        ], true);

        model(IncidentModel::class)->skipValidation(true)->insert([
            'reference_code' => 'INC-' . date('Ymd') . '-DEMO',
            'title'          => 'Sample flood in Poblacion (demo)',
            'description'    => 'Use this row to test matching, priority tags, and GIS heatmap.',
            'incident_type'  => 'flood',
            'severity'       => 'high',
            'priority_tag'   => 'HIGH',
            'needs_rescue'   => 2,
            'needs_medical'  => 1,
            'needs_drivers'  => 1,
            'status'         => 'reported',
            'latitude'       => 14.5171,
            'longitude'      => 121.2672,
            'barangay_id'    => $b1,
        ]);

        $v1 = model(VolunteerModel::class)->skipValidation(true)->insert([
            'full_name'           => 'Ana Dela Cruz',
            'email'               => 'volunteer.demo@drms.local',
            'phone'               => '09171234567',
            'barangay_id'         => $b1,
            'availability_status' => 'available',
            'experience_years'    => 4,
        ], true);

        $v2 = model(VolunteerModel::class)->skipValidation(true)->insert([
            'full_name'           => 'Ben Ramos',
            'email'               => 'volunteer2.demo@drms.local',
            'phone'               => '09179876543',
            'barangay_id'         => $b2,
            'availability_status' => 'available',
            'experience_years'    => 2,
        ], true);

        $skills = new VolunteerSkillService();
        if ($v1) {
            $skills->syncSkills((int) $v1, ['Medical', 'Rescue', 'Communication']);
        }
        if ($v2) {
            $skills->syncSkills((int) $v2, ['Rescue', 'Driving', 'Logistics']);
        }

        model(EvacuationCenterModel::class)->skipValidation(true)->insert([
            'name'              => 'Municipal gym (demo)',
            'barangay_id'       => $b1,
            'capacity'          => 200,
            'current_occupancy' => 45,
            'families_registered' => 12,
            'medical_needs_count' => 3,
            'status'            => 'open',
            'contact_phone'     => '000-0000',
        ]);

        $donorId = model(DonorModel::class)->skipValidation(true)->insert([
            'name'     => 'Community donor (demo)',
            'org_name' => 'Citizens Network',
            'email'    => 'donor.demo@drms.local',
        ], true);

        // Donations model requires donor - skip if donor insert failed
        if ($donorId) {
            model(DonationModel::class)->skipValidation(true)->insert([
                'donor_id'      => $donorId,
                'donation_type' => 'goods',
                'description'   => 'Rice and canned goods',
                'amount_peso'   => null,
                'received_at'   => date('Y-m-d H:i:s'),
            ]);
        }

        model(AuditLogModel::class)->skipValidation(true)->insert([
            'user_id'     => null,
            'action'      => 'seed.demo',
            'entity_type' => 'system',
            'meta_json'   => json_encode(['note' => 'DrmsDemoSeeder']),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }
}
