<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EvacuationCenterModel;
use RuntimeException;

class EvacuationCenterService
{
    public function __construct(
        private EvacuationCenterModel $centers = new EvacuationCenterModel(),
        private AuditLogService $audit = new AuditLogService(),
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $row = $this->mapFields($data);
        if (! $this->centers->insert($row)) {
            throw new RuntimeException(implode(' ', $this->centers->errors()));
        }
        $id = (int) $this->centers->getInsertID();
        $this->audit->log('evacuation.create', 'evacuation_center', $id);

        return $this->centers->find($id) ?? $row;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        if (! $this->centers->find($id)) {
            return false;
        }
        $ok = $this->centers->update($id, $this->mapFields($data));
        if ($ok) {
            $this->audit->log('evacuation.update', 'evacuation_center', $id);
        }

        return $ok;
    }

    public function delete(int $id): bool
    {
        if (! $this->centers->find($id)) {
            return false;
        }
        $ok = (bool) $this->centers->delete($id);
        if ($ok) {
            $this->audit->log('evacuation.delete', 'evacuation_center', $id);
        }

        return $ok;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function mapFields(array $data): array
    {
        return [
            'name'              => trim((string) ($data['name'] ?? '')),
            'barangay_id'       => $this->nullableInt($data['barangay_id'] ?? null),
            'address'           => trim((string) ($data['address'] ?? '')),
            'capacity'          => (int) ($data['capacity'] ?? 0),
            'current_occupancy' => (int) ($data['current_occupancy'] ?? 0),
            'families_registered' => (int) ($data['families_registered'] ?? 0),
            'medical_needs_count' => (int) ($data['medical_needs_count'] ?? 0),
            'status'              => strtolower((string) ($data['status'] ?? 'open')),
            'contact_phone'       => trim((string) ($data['contact_phone'] ?? '')),
            'contact_person'      => trim((string) ($data['contact_person'] ?? '')),
            'intake_procedures'   => trim((string) ($data['intake_procedures'] ?? '')),
            'required_items'      => trim((string) ($data['required_items'] ?? '')),
        ];
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
