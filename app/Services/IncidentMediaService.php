<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\IncidentMediaModel;
use CodeIgniter\HTTP\Files\UploadedFile;

class IncidentMediaService
{
    /** @var list<string> */
    private array $allowedMimes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'video/mp4', 'video/webm', 'video/quicktime',
    ];

    public function __construct(
        private IncidentMediaModel $media = new IncidentMediaModel(),
        private FileUploadService $uploader = new FileUploadService(),
    ) {
    }

    /**
     * @param list<UploadedFile> $files
     * @return list<array<string, mixed>>
     */
    public function attachToIncident(int $incidentId, array $files): array
    {
        $saved = [];
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || $file->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $meta = $this->uploader->store($file, 'incidents/' . $incidentId, $this->allowedMimes, 10240);
            $this->media->insert([
                'incident_id'   => $incidentId,
                'path'          => $meta['path'],
                'original_name' => $meta['original_name'],
                'mime'          => $meta['mime'],
            ]);
            $saved[] = $this->media->find((int) $this->media->getInsertID()) ?? $meta;
        }

        return $saved;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForIncident(int $incidentId): array
    {
        return $this->media->where('incident_id', $incidentId)->orderBy('id', 'ASC')->findAll();
    }

    public function countForIncident(int $incidentId): int
    {
        return (int) $this->media->where('incident_id', $incidentId)->countAllResults();
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    public function formatForApi(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $path = ltrim((string) ($row['path'] ?? ''), '/');
            $mime = (string) ($row['mime'] ?? '');
            $out[] = array_merge($row, [
                'url'       => $path !== '' ? base_url($path) : '',
                'is_image'  => str_starts_with($mime, 'image/'),
                'is_video'  => str_starts_with($mime, 'video/'),
            ]);
        }

        return $out;
    }
}
