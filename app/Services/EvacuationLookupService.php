<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BarangayModel;
use App\Models\EvacuationCenterModel;

class EvacuationLookupService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listOpenCenters(): array
    {
        return model(EvacuationCenterModel::class)
            ->whereIn('status', ['open'])
            ->orderBy('name', 'ASC')
            ->findAll(100);
    }

    /**
     * Add map coordinates and capacity summary for public map display.
     *
     * @param list<array<string, mixed>> $centers
     * @return list<array<string, mixed>>
     */
    public function enrichCenters(array $centers): array
    {
        $barangayIds = array_values(array_unique(array_filter(array_map(
            static fn (array $c): int => (int) ($c['barangay_id'] ?? 0),
            $centers
        ))));

        $barangays = [];
        if ($barangayIds !== []) {
            foreach (model(BarangayModel::class)->whereIn('id', $barangayIds)->findAll() as $b) {
                $barangays[(int) $b['id']] = $b;
            }
        }

        $enriched = [];
        foreach ($centers as $center) {
            $brgyId = (int) ($center['barangay_id'] ?? 0);
            $brgy   = $barangays[$brgyId] ?? null;
            $lat    = $brgy ? $this->floatOrNull($brgy['latitude'] ?? null) : null;
            $lng    = $brgy ? $this->floatOrNull($brgy['longitude'] ?? null) : null;

            if ($lat !== null && $lng !== null) {
                $id  = (int) ($center['id'] ?? 0);
                $lat += (($id % 5) - 2) * 0.0008;
                $lng += ((int) ($id / 5) % 5 - 2) * 0.0008;
            }

            $cap = (int) ($center['capacity'] ?? 0);
            $occ = (int) ($center['current_occupancy'] ?? 0);

            $enriched[] = array_merge($center, [
                'latitude'        => $lat,
                'longitude'       => $lng,
                'barangay_name'   => $brgy['name'] ?? '',
                'available_slots' => max(0, $cap - $occ),
                'is_full'         => $cap > 0 && $occ >= $cap,
            ]);
        }

        return $enriched;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function nearestCenter(float $lat, float $lng): ?array
    {
        $best    = null;
        $bestDist = PHP_FLOAT_MAX;

        foreach ($this->listOpenCenters() as $center) {
            $cLat = $this->centerLat($center);
            $cLng = $this->centerLng($center);
            if ($cLat === null || $cLng === null) {
                continue;
            }

            $cap = (int) ($center['capacity'] ?? 0);
            $occ = (int) ($center['current_occupancy'] ?? 0);
            if ($cap > 0 && $occ >= $cap) {
                continue;
            }

            $dist = $this->haversineKm($lat, $lng, $cLat, $cLng);
            if ($dist < $bestDist) {
                $bestDist = $dist;
                $center['distance_km'] = round($dist, 1);
                $center['available_slots'] = max(0, $cap - $occ);
                $best = $center;
            }
        }

        return $best;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function suggestAlternatives(float $lat, float $lng, int $limit = 3): array
    {
        $ranked = [];
        foreach ($this->listOpenCenters() as $center) {
            $cLat = $this->centerLat($center);
            $cLng = $this->centerLng($center);
            if ($cLat === null || $cLng === null) {
                continue;
            }
            $cap = (int) ($center['capacity'] ?? 0);
            $occ = (int) ($center['current_occupancy'] ?? 0);
            if ($cap > 0 && $occ >= $cap) {
                continue;
            }
            $center['distance_km']     = round($this->haversineKm($lat, $lng, $cLat, $cLng), 1);
            $center['available_slots'] = max(0, $cap - $occ);
            $ranked[]                  = $center;
        }

        usort($ranked, static fn ($a, $b) => ($a['distance_km'] ?? 99) <=> ($b['distance_km'] ?? 99));

        return array_slice($ranked, 0, $limit);
    }

    private function centerLat(array $center): ?float
    {
        if (! empty($center['barangay_id'])) {
            $b = model(BarangayModel::class)->find((int) $center['barangay_id']);

            return $b ? $this->floatOrNull($b['latitude'] ?? null) : null;
        }

        return null;
    }

    private function centerLng(array $center): ?float
    {
        if (! empty($center['barangay_id'])) {
            $b = model(BarangayModel::class)->find((int) $center['barangay_id']);

            return $b ? $this->floatOrNull($b['longitude'] ?? null) : null;
        }

        return null;
    }

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a    = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function floatOrNull(mixed $v): ?float
    {
        return ($v === null || $v === '') ? null : (float) $v;
    }
}
