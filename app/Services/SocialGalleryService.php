<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SocialGalleryPostModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use RuntimeException;

class SocialGalleryService
{
    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct(
        private SocialGalleryPostModel $posts = new SocialGalleryPostModel(),
        private FileUploadService $uploader = new FileUploadService(),
        private AuditLogService $audit = new AuditLogService(),
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listActive(int $limit = 27): array
    {
        if (! $this->isReady()) {
            return [];
        }

        return $this->posts
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('published_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll($limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listAll(): array
    {
        if (! $this->isReady()) {
            return [];
        }

        return $this->posts
            ->orderBy('sort_order', 'ASC')
            ->orderBy('published_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll(100);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        return $this->posts->find($id) ?: null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data, ?UploadedFile $image = null): array
    {
        $row = $this->mapFields($data);
        if ($image !== null && $image->isValid()) {
            $row['image_path'] = $this->storeImage($image);
        } elseif (empty($row['image_path'])) {
            throw new RuntimeException('A gallery image is required.');
        }

        if (! $this->posts->insert($row)) {
            throw new RuntimeException(implode(' ', $this->posts->errors()));
        }

        $id = (int) $this->posts->getInsertID();
        $this->audit->log('social_gallery.create', 'social_gallery_post', $id);

        return $this->find($id) ?? $row;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data, ?UploadedFile $image = null, bool $removeImage = false): array
    {
        $existing = $this->find($id);
        if (! $existing) {
            throw new RuntimeException('Gallery post not found.');
        }

        $row = $this->mapFields($data, $existing);
        if ($removeImage) {
            throw new RuntimeException('Gallery posts require an image. Upload a replacement instead.');
        }
        if ($image !== null && $image->isValid()) {
            $row['image_path'] = $this->storeImage($image);
        }

        if (! $this->posts->update($id, $row)) {
            throw new RuntimeException('Could not update gallery post.');
        }

        $this->audit->log('social_gallery.update', 'social_gallery_post', $id);

        return $this->find($id) ?? $row;
    }

    public function delete(int $id): void
    {
        if (! $this->posts->find($id)) {
            throw new RuntimeException('Gallery post not found.');
        }

        if (! $this->posts->delete($id)) {
            throw new RuntimeException('Could not delete gallery post.');
        }

        $this->audit->log('social_gallery.delete', 'social_gallery_post', $id);
    }

    /**
     * @param array<string, mixed> $existing
     * @return array<string, mixed>
     */
    private function mapFields(array $data, array $existing = []): array
    {
        $platform = strtolower(trim((string) ($data['platform'] ?? $existing['platform'] ?? 'facebook')));
        if (! in_array($platform, ['facebook', 'other'], true)) {
            $platform = 'facebook';
        }

        $publishedAt = trim((string) ($data['published_at'] ?? $existing['published_at'] ?? ''));
        if ($publishedAt === '') {
            $publishedAt = null;
        }

        return [
            'title'        => trim((string) ($data['title'] ?? $existing['title'] ?? '')),
            'caption'      => trim((string) ($data['caption'] ?? $existing['caption'] ?? '')),
            'image_path'   => trim((string) ($data['image_path'] ?? $existing['image_path'] ?? '')),
            'external_url' => trim((string) ($data['external_url'] ?? $existing['external_url'] ?? '')),
            'platform'     => $platform,
            'published_at' => $publishedAt,
            'sort_order'   => max(0, (int) ($data['sort_order'] ?? $existing['sort_order'] ?? 0)),
            'is_active'    => array_key_exists('is_active', $data)
                ? (! empty($data['is_active']) ? 1 : 0)
                : (int) ($existing['is_active'] ?? 1),
        ];
    }

    private function storeImage(UploadedFile $file): string
    {
        return $this->uploader->store($file, 'social-gallery', self::IMAGE_MIMES, 5120)['path'];
    }

    public function isReady(): bool
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }

        try {
            $ready = db_connect()->tableExists('social_gallery_posts');
        } catch (\Throwable) {
            $ready = false;
        }

        return $ready;
    }
}
