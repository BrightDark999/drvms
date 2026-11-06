<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DonationModel;
use App\Models\DonorModel;
use RuntimeException;

class DonationService
{
    public function __construct(
        private DonationModel $donations = new DonationModel(),
        private DonorModel $donors = new DonorModel(),
        private AuditLogService $audit = new AuditLogService(),
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data, ?int $recordedByUserId = null): array
    {
        $donorId = $this->resolveDonorId($data);
        $row     = [
            'donor_id'            => $donorId,
            'donation_type'       => strtolower((string) ($data['donation_type'] ?? 'cash')),
            'description'         => trim((string) ($data['description'] ?? '')),
            'amount_peso'         => $this->nullableAmount($data['amount_peso'] ?? null),
            'received_at'         => $this->nullableDate($data['received_at'] ?? null) ?? date('Y-m-d H:i:s'),
            'recorded_by_user_id' => $recordedByUserId,
        ];

        if (! $this->donations->insert($row)) {
            throw new RuntimeException(implode(' ', $this->donations->errors()));
        }
        $id = (int) $this->donations->getInsertID();
        $this->audit->log('donation.create', 'donation', $id);

        return $this->donations->find($id) ?? $row;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        if (! $this->donations->find($id)) {
            return false;
        }

        $row = [
            'donor_id'      => $this->resolveDonorId($data),
            'donation_type' => strtolower((string) ($data['donation_type'] ?? 'cash')),
            'description'   => trim((string) ($data['description'] ?? '')),
            'amount_peso'   => $this->nullableAmount($data['amount_peso'] ?? null),
            'received_at'   => $this->nullableDate($data['received_at'] ?? null),
        ];

        $ok = $this->donations->update($id, $row);
        if ($ok) {
            $this->audit->log('donation.update', 'donation', $id);
        }

        return $ok;
    }

    public function delete(int $id): bool
    {
        if (! $this->donations->find($id)) {
            return false;
        }
        $ok = (bool) $this->donations->delete($id);
        if ($ok) {
            $this->audit->log('donation.delete', 'donation', $id);
        }

        return $ok;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveDonorId(array $data): ?int
    {
        if (! empty($data['donor_id'])) {
            return (int) $data['donor_id'];
        }

        $name = trim((string) ($data['donor_name'] ?? ''));
        if ($name === '') {
            return null;
        }

        $existing = $this->donors->where('name', $name)->first();
        if ($existing) {
            return (int) $existing['id'];
        }

        $this->donors->insert([
            'name'  => $name,
            'email' => trim((string) ($data['donor_email'] ?? '')) ?: null,
            'phone' => trim((string) ($data['donor_phone'] ?? '')) ?: null,
        ]);

        return (int) $this->donors->getInsertID();
    }

    private function nullableAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    private function nullableDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
