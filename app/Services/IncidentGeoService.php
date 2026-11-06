<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BarangayModel;

/**
 * Resolve map coordinates for incidents (GPS → barangay → municipal fallback).
 */
class IncidentGeoService
{
    /**
     * @param array<string, mixed> $incident
     * @return array{lat: float, lng: float, source: string}|null
     */
    public function resolve(array $incident): ?array
    {
        $lat = $this->toFloat($incident['latitude'] ?? null);
        $lng = $this->toFloat($incident['longitude'] ?? null);

        if ($lat !== null && $lng !== null) {
            return ['lat' => $lat, 'lng' => $lng, 'source' => 'incident'];
        }

        $barangayId = (int) ($incident['barangay_id'] ?? 0);
        if ($barangayId > 0) {
            $brgy = model(BarangayModel::class)->find($barangayId);
            if ($brgy) {
                $bLat = $this->toFloat($brgy['latitude'] ?? null);
                $bLng = $this->toFloat($brgy['longitude'] ?? null);
                if ($bLat !== null && $bLng !== null) {
                    return ['lat' => $bLat, 'lng' => $bLng, 'source' => 'barangay'];
                }
            }
        }

        $drms = drms_config();

        return [
            'lat'    => $drms->officeLatitude,
            'lng'    => $drms->officeLongitude,
            'source' => 'municipal',
        ];
    }

    /**
     * Persist resolved coordinates on new/updated incidents when GPS was not provided.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function enrichPayload(array $data): array
    {
        $lat = $this->toFloat($data['latitude'] ?? null);
        $lng = $this->toFloat($data['longitude'] ?? null);

        if ($lat !== null && $lng !== null) {
            $data['latitude']  = $lat;
            $data['longitude'] = $lng;

            return $data;
        }

        $resolved = $this->resolve($data);
        if ($resolved === null) {
            return $data;
        }

        $data['latitude']  = $resolved['lat'];
        $data['longitude'] = $resolved['lng'];

        return $data;
    }

    private function toFloat(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }

        return (float) $v;
    }
}
