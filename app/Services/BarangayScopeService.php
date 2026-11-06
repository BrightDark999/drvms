<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Restrict queries for barangay-scoped users (reserved for future role-based scoping).
 */
class BarangayScopeService
{
    public function shouldScopeIncidents(): bool
    {
        return false;
    }

    public function scopedBarangayId(): ?int
    {
        if (! $this->shouldScopeIncidents()) {
            return null;
        }
        $uid = auth()->id();

        return $uid ? (new UserProfileService())->getBarangayIdForUser((int) $uid) : null;
    }

    /**
     * @param \CodeIgniter\Database\BaseBuilder|\CodeIgniter\Model $builder
     */
    public function applyIncidentScope($builder, string $column = 'incidents.barangay_id'): void
    {
        $bid = $this->scopedBarangayId();
        if ($bid !== null) {
            $builder->where($column, $bid);
        }
    }
}
