<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\VolunteerSkillModel;

class VolunteerSkillService
{
    public function __construct(
        private VolunteerSkillModel $skills = new VolunteerSkillModel(),
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForVolunteer(int $volunteerId): array
    {
        return $this->skills->where('volunteer_id', $volunteerId)->orderBy('skill_name', 'ASC')->findAll();
    }

    /**
     * @param list<string> $skillNames
     */
    public function syncSkills(int $volunteerId, array $skillNames): void
    {
        $this->skills->where('volunteer_id', $volunteerId)->delete();
        foreach (array_unique(array_filter($this->normalizeSkillNames($skillNames))) as $name) {
            if ($name === '') {
                continue;
            }
            $this->skills->insert([
                'volunteer_id' => $volunteerId,
                'skill_name'   => mb_substr($name, 0, 128),
            ]);
        }
    }

    /**
     * @return array<int, list<string>> volunteer_id => skill names
     */
    public function mapAllByVolunteer(): array
    {
        $map = [];
        foreach ($this->skills->findAll(2000) as $row) {
            $vid = (int) ($row['volunteer_id'] ?? 0);
            $map[$vid][] = (string) ($row['skill_name'] ?? '');
        }

        return $map;
    }

    /**
     * Accept checkbox strings or persisted skill rows from volunteer payloads.
     *
     * @param list<mixed> $skillNames
     * @return list<string>
     */
    public function normalizeSkillNames(array $skillNames): array
    {
        $names = [];
        foreach ($skillNames as $item) {
            if (is_string($item)) {
                $name = trim($item);
            } elseif (is_array($item)) {
                $name = trim((string) ($item['skill_name'] ?? $item['name'] ?? ''));
            } else {
                continue;
            }

            if ($name !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }
}
