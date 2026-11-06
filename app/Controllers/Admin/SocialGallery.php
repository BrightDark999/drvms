<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\Admin\Traits\CrudResponses;
use App\Services\SocialGalleryService;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

class SocialGallery extends BaseController
{
    use CrudResponses;

    public function index(): string
    {
        if (! drms_user_can('admin.*')) {
            return redirect()->to(base_url('admin/dashboard'))->with('error', 'Access denied.');
        }

        $drms = drms_config();

        return view('admin/social_gallery/index', [
            'title'           => 'LGU Social Gallery',
            'drms'            => $drms,
            'posts'           => (new SocialGalleryService())->listAll(),
            'facebookPageUrl' => $drms->facebookPageUrl,
            'facebookPageName'=> $drms->facebookPageName,
        ]);
    }

    public function list(): ResponseInterface
    {
        $guard = $this->guardAdmin();
        if ($guard instanceof ResponseInterface) {
            return $guard;
        }

        return $this->crudOk('OK', ['items' => (new SocialGalleryService())->listAll()]);
    }

    public function show(int $id): ResponseInterface
    {
        $guard = $this->guardAdmin();
        if ($guard instanceof ResponseInterface) {
            return $guard;
        }

        $row = (new SocialGalleryService())->find($id);
        if (! $row) {
            return $this->crudFail('Gallery post not found.', 404);
        }

        return $this->crudOk('OK', $row);
    }

    public function store(): ResponseInterface
    {
        $guard = $this->guardAdmin();
        if ($guard instanceof ResponseInterface) {
            return $guard;
        }

        if (! $this->validate($this->rules())) {
            return $this->crudValidationFail();
        }

        try {
            $row = (new SocialGalleryService())->create(
                $this->mappedPost(),
                $this->request->getFile('image')
            );

            return $this->crudOk('Gallery post created.', $row);
        } catch (RuntimeException $e) {
            return $this->crudFail($e->getMessage(), 400);
        }
    }

    public function update(int $id): ResponseInterface
    {
        $guard = $this->guardAdmin();
        if ($guard instanceof ResponseInterface) {
            return $guard;
        }

        if (! $this->validate($this->rules($id))) {
            return $this->crudValidationFail();
        }

        try {
            $row = (new SocialGalleryService())->update(
                $id,
                $this->mappedPost(),
                $this->request->getFile('image'),
                $this->request->getPost('remove_image') === '1'
            );

            return $this->crudOk('Gallery post updated.', $row);
        } catch (RuntimeException $e) {
            return $this->crudFail($e->getMessage(), 400);
        }
    }

    public function delete(int $id): ResponseInterface
    {
        $guard = $this->guardAdmin();
        if ($guard instanceof ResponseInterface) {
            return $guard;
        }

        try {
            (new SocialGalleryService())->delete($id);

            return $this->crudOk('Gallery post deleted.');
        } catch (RuntimeException $e) {
            return $this->crudFail($e->getMessage(), 400);
        }
    }

    private function guardAdmin(): self|ResponseInterface
    {
        if (! drms_user_can('admin.*')) {
            return $this->crudFail('Unauthorized.', 403);
        }

        return $this;
    }

    /**
     * @return array<string, string>
     */
    private function rules(?int $id = null): array
    {
        $rules = [
            'title'        => 'permit_empty|max_length[255]',
            'caption'      => 'permit_empty|max_length[4000]',
            'external_url' => 'permit_empty|max_length[500]|valid_url_strict',
            'sort_order'   => 'permit_empty|integer',
            'published_at' => 'permit_empty|valid_date[Y-m-d\TH:i]',
        ];

        if ($id === null) {
            $rules['image'] = 'uploaded[image]|max_size[image,5120]|ext_in[image,jpg,jpeg,png,webp]';
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    private function mappedPost(): array
    {
        $publishedAt = $this->request->getPost('published_at');
        if (is_string($publishedAt) && $publishedAt !== '') {
            $publishedAt = str_replace('T', ' ', $publishedAt);
        }

        return [
            'title'        => $this->request->getPost('title'),
            'caption'      => $this->request->getPost('caption'),
            'external_url' => $this->request->getPost('external_url'),
            'platform'     => $this->request->getPost('platform') ?: 'facebook',
            'published_at' => $publishedAt,
            'sort_order'   => $this->request->getPost('sort_order'),
            'is_active'    => $this->request->getPost('is_active') ? 1 : 0,
        ];
    }
}
