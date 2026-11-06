<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HomeCarouselSlideModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use RuntimeException;

class HomeCarouselService
{
    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct(
        private HomeCarouselSlideModel $slides = new HomeCarouselSlideModel(),
        private FileUploadService $uploader = new FileUploadService(),
        private AuditLogService $audit = new AuditLogService(),
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listActive(): array
    {
        if (! $this->isReady()) {
            return $this->defaultSlides();
        }

        $rows = $this->slides
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll(20);

        return $rows !== [] ? $rows : $this->seedDefaultsIfEmpty();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listAll(): array
    {
        if (! $this->isReady()) {
            return [];
        }

        return $this->slides
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll(100);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        return $this->slides->find($id) ?: null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data, ?UploadedFile $image = null): array
    {
        $row = $this->mapFields($data);
        if ($image !== null && $image->isValid()) {
            $row['image_path'] = $this->storeImage($image);
        }

        if (! $this->slides->insert($row)) {
            throw new RuntimeException(implode(' ', $this->slides->errors()));
        }

        $id = (int) $this->slides->getInsertID();
        $this->audit->log('home_carousel.create', 'homepage_carousel_slide', $id);

        return $this->find($id) ?? $row;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data, ?UploadedFile $image = null, bool $removeImage = false): array
    {
        $existing = $this->find($id);
        if (! $existing) {
            throw new RuntimeException('Slide not found.');
        }

        $row = $this->mapFields($data, $existing);
        if ($removeImage) {
            $row['image_path'] = null;
        } elseif ($image !== null && $image->isValid()) {
            $row['image_path'] = $this->storeImage($image);
        }

        if (! $this->slides->update($id, $row)) {
            throw new RuntimeException('Could not update slide.');
        }

        $this->audit->log('home_carousel.update', 'homepage_carousel_slide', $id);

        return $this->find($id) ?? $row;
    }

    public function delete(int $id): void
    {
        if (! $this->slides->find($id)) {
            throw new RuntimeException('Slide not found.');
        }

        if (! $this->slides->delete($id)) {
            throw new RuntimeException('Could not delete slide.');
        }

        $this->audit->log('home_carousel.delete', 'homepage_carousel_slide', $id);
    }

    /**
     * @param array<string, mixed> $existing
     * @return array<string, mixed>
     */
    private function mapFields(array $data, array $existing = []): array
    {
        $style = strtolower(trim((string) ($data['background_style'] ?? $existing['background_style'] ?? 'gradient-1')));
        if (! in_array($style, ['gradient-1', 'gradient-2', 'gradient-3'], true)) {
            $style = 'gradient-1';
        }

        return [
            'badge_text'             => trim((string) ($data['badge_text'] ?? $existing['badge_text'] ?? '')),
            'title'                  => trim((string) ($data['title'] ?? $existing['title'] ?? '')),
            'description'            => trim((string) ($data['description'] ?? $existing['description'] ?? '')),
            'background_style'       => $style,
            'button_primary_label'   => trim((string) ($data['button_primary_label'] ?? $existing['button_primary_label'] ?? '')),
            'button_primary_url'     => trim((string) ($data['button_primary_url'] ?? $existing['button_primary_url'] ?? '')),
            'button_secondary_label' => trim((string) ($data['button_secondary_label'] ?? $existing['button_secondary_label'] ?? '')),
            'button_secondary_url'   => trim((string) ($data['button_secondary_url'] ?? $existing['button_secondary_url'] ?? '')),
            'sort_order'             => max(0, (int) ($data['sort_order'] ?? $existing['sort_order'] ?? 0)),
            'is_active'              => array_key_exists('is_active', $data)
                ? (! empty($data['is_active']) ? 1 : 0)
                : (int) ($existing['is_active'] ?? 1),
        ];
    }

    private function storeImage(UploadedFile $file): string
    {
        return $this->uploader->store($file, 'carousel', self::IMAGE_MIMES, 4096)['path'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function seedDefaultsIfEmpty(): array
    {
        if (! $this->isReady() || $this->slides->countAllResults() > 0) {
            return $this->defaultSlides();
        }

        $drms = drms_config();
        $defaults = [
            [
                'badge_text'             => (string) $drms->appName,
                'title'                  => $drms->appName . ' — ' . $drms->serviceMunicipality,
                'description'            => (string) $drms->appTagline,
                'background_style'       => 'gradient-1',
                'button_primary_label'   => 'Report an incident',
                'button_primary_url'     => base_url('report'),
                'button_secondary_label' => 'Public services',
                'button_secondary_url'   => base_url('/') . '#public-services',
                'sort_order'             => 1,
                'is_active'              => 1,
            ],
            [
                'badge_text'             => 'Citizens & Communities',
                'title'                  => 'Public tools anyone can use',
                'description'            => 'Report disasters, find shelters, and register as a volunteer — with admin approval keeping accounts legitimate.',
                'background_style'       => 'gradient-3',
                'button_primary_label'   => 'Public services',
                'button_primary_url'     => base_url('/') . '#public-services',
                'button_secondary_label' => 'Staff login',
                'button_secondary_url'     => url_to('login'),
                'sort_order'             => 2,
                'is_active'              => 1,
            ],
        ];

        foreach ($defaults as $row) {
            $this->slides->insert($row);
        }

        return $this->listActive();
    }

    /**
     * Fallback when DB table is missing.
     *
     * @return list<array<string, mixed>>
     */
    public function defaultSlides(): array
    {
        $drms = drms_config();

        return [
            [
                'badge_text'             => (string) $drms->appName,
                'title'                  => $drms->appName . ' — ' . $drms->serviceMunicipality,
                'description'            => (string) $drms->appTagline,
                'image_path'             => null,
                'background_style'       => 'gradient-1',
                'button_primary_label'   => 'Report an incident',
                'button_primary_url'     => base_url('report'),
                'button_secondary_label' => 'Public services',
                'button_secondary_url'   => base_url('/') . '#public-services',
            ],
        ];
    }

    public function isReady(): bool
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }

        try {
            $ready = db_connect()->tableExists('homepage_carousel_slides');
        } catch (\Throwable) {
            $ready = false;
        }

        return $ready;
    }
}
