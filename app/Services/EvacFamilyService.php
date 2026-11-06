<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EvacFamilyModel;
use RuntimeException;

class EvacFamilyService
{
    public function __construct(
        private EvacFamilyModel $families = new EvacFamilyModel(),
        private EvacuationCenterService $centers = new EvacuationCenterService(),
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function register(array $data): array
    {
        $centerId = (int) ($data['evacuation_center_id'] ?? 0);
        $name     = trim((string) ($data['family_head_name'] ?? ''));
        if ($centerId < 1 || $name === '') {
            throw new RuntimeException('Center and family head name are required.');
        }

        $members = max(1, (int) ($data['members_count'] ?? 1));
        $token   = 'FAM-' . strtoupper(bin2hex(random_bytes(6)));

        $this->families->insert([
            'evacuation_center_id' => $centerId,
            'family_head_name'     => $name,
            'members_count'        => $members,
            'medical_needs'        => trim((string) ($data['medical_needs'] ?? '')) ?: null,
            'contact_phone'        => trim((string) ($data['contact_phone'] ?? '')) ?: null,
            'family_qr_token'      => $token,
            'registered_at'        => date('Y-m-d H:i:s'),
        ]);

        $id = (int) $this->families->getInsertID();
        $center = model(\App\Models\EvacuationCenterModel::class)->find($centerId);
        if ($center) {
            model(\App\Models\EvacuationCenterModel::class)->update($centerId, [
                'families_registered'   => (int) ($center['families_registered'] ?? 0) + 1,
                'current_occupancy'     => (int) ($center['current_occupancy'] ?? 0) + $members,
                'medical_needs_count'   => (int) ($center['medical_needs_count'] ?? 0) + (($data['medical_needs'] ?? '') !== '' ? 1 : 0),
            ]);
        }

        return $this->families->find($id) ?? [];
    }

    public function checkIn(string $familyQrToken): array
    {
        $token = trim($familyQrToken);
        if ($token === '') {
            throw new RuntimeException('Family QR token is required.');
        }

        $family = $this->families->where('family_qr_token', $token)->first();
        if (! $family) {
            throw new RuntimeException('Family registration not found for this token.');
        }

        if (! empty($family['checked_in_at'])) {
            return [
                'status'  => 'already',
                'message' => 'This family was already checked in.',
                'data'    => $this->enrichFamily($family),
            ];
        }

        $this->families->update($family['id'], ['checked_in_at' => date('Y-m-d H:i:s')]);
        $updated = $this->families->find($family['id']) ?? $family;

        return [
            'status'  => 'ok',
            'message' => 'Family checked in successfully.',
            'data'    => $this->enrichFamily($updated),
        ];
    }

    /**
     * Mark a family as checked out and adjust center occupancy counts.
     *
     * @return array{status: string, message: string, data: array<string, mixed>}
     */
    public function checkOut(int $familyId, int $centerId): array
    {
        $family = $this->families->find($familyId);
        if (! $family || (int) ($family['evacuation_center_id'] ?? 0) !== $centerId) {
            throw new RuntimeException('Family not found at this evacuation center.');
        }

        if (trim((string) ($family['checked_out_at'] ?? '')) !== '') {
            return [
                'status'  => 'already',
                'message' => 'This family was already checked out.',
                'data'    => $this->formatForAdmin($family),
            ];
        }

        $this->families->update($familyId, ['checked_out_at' => date('Y-m-d H:i:s')]);

        $center = model(\App\Models\EvacuationCenterModel::class)->find($centerId);
        if ($center) {
            $members    = max(1, (int) ($family['members_count'] ?? 1));
            $hasMedical = trim((string) ($family['medical_needs'] ?? '')) !== '';

            model(\App\Models\EvacuationCenterModel::class)->update($centerId, [
                'families_registered' => max(0, (int) ($center['families_registered'] ?? 0) - 1),
                'current_occupancy'   => max(0, (int) ($center['current_occupancy'] ?? 0) - $members),
                'medical_needs_count' => max(0, (int) ($center['medical_needs_count'] ?? 0) - ($hasMedical ? 1 : 0)),
            ]);
        }

        $updated = $this->families->find($familyId) ?? $family;

        return [
            'status'  => 'ok',
            'message' => 'Family checked out. Center occupancy updated.',
            'data'    => $this->formatForAdmin($updated),
        ];
    }

    /**
     * @param array<string, mixed> $family
     * @return array<string, mixed>
     */
    private function enrichFamily(array $family): array
    {
        $centerId = (int) ($family['evacuation_center_id'] ?? 0);
        $center   = $centerId > 0 ? model(\App\Models\EvacuationCenterModel::class)->find($centerId) : null;
        $barangay = '';

        if ($center && ! empty($center['barangay_id'])) {
            $b = model(\App\Models\BarangayModel::class)->find((int) $center['barangay_id']);
            $barangay = (string) ($b['name'] ?? '');
        }

        return [
            'id'                  => (int) ($family['id'] ?? 0),
            'family_head_name'    => (string) ($family['family_head_name'] ?? ''),
            'members_count'       => (int) ($family['members_count'] ?? 0),
            'medical_needs'       => (string) ($family['medical_needs'] ?? ''),
            'contact_phone'       => (string) ($family['contact_phone'] ?? ''),
            'family_qr_token'     => (string) ($family['family_qr_token'] ?? ''),
            'registered_at'       => (string) ($family['registered_at'] ?? ''),
            'checked_in_at'       => (string) ($family['checked_in_at'] ?? ''),
            'checked_out_at'      => (string) ($family['checked_out_at'] ?? ''),
            'center_name'         => (string) ($center['name'] ?? ''),
            'center_address'      => (string) ($center['address'] ?? ''),
            'center_barangay'     => $barangay,
            'center_contact'      => (string) ($center['contact_phone'] ?? ''),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForCenter(int $centerId): array
    {
        return $this->families->where('evacuation_center_id', $centerId)->orderBy('id', 'DESC')->findAll(200);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listEnrichedForCenter(int $centerId): array
    {
        $rows = $this->listForCenter($centerId);

        return array_map(fn (array $row): array => $this->formatForAdmin($row), $rows);
    }

    /**
     * @param array<string, mixed> $family
     * @return array<string, mixed>
     */
    public function formatForAdmin(array $family): array
    {
        $enriched  = $this->enrichFamily($family);
        $checkedOut = trim((string) ($family['checked_out_at'] ?? '')) !== '';
        $checkedIn  = trim((string) ($family['checked_in_at'] ?? '')) !== '';

        if ($checkedOut) {
            $enriched['check_in_status'] = 'checked_out';
            $enriched['check_in_label']  = 'Checked out';
        } elseif ($checkedIn) {
            $enriched['check_in_status'] = 'checked_in';
            $enriched['check_in_label']  = 'Checked in';
        } else {
            $enriched['check_in_status'] = 'pending';
            $enriched['check_in_label']  = 'Not checked in';
        }

        $enriched['has_medical']            = trim((string) ($family['medical_needs'] ?? '')) !== '';
        $enriched['registered_at_display']  = $this->formatDateTime((string) ($family['registered_at'] ?? ''));
        $enriched['checked_in_at_display']  = $this->formatDateTime((string) ($family['checked_in_at'] ?? ''));
        $enriched['checked_out_at_display'] = $this->formatDateTime((string) ($family['checked_out_at'] ?? ''));

        return $enriched;
    }

    private function formatDateTime(string $value): string
    {
        if ($value === '') {
            return '—';
        }

        $ts = strtotime($value);

        return $ts !== false ? date('M j, Y g:i A', $ts) : $value;
    }
}
