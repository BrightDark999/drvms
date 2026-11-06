<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\Admin\Traits\CrudResponses;
use App\Services\HomeCarouselService;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

class HomeCarousel extends BaseController
{
    use CrudResponses;

    public function index(): string
    {
        if (! drms_user_can('admin.*')) {
            return redirect()->to(base_url('admin/dashboard'))->with('error', 'Access denied.');
        }

        return view('admin/home_carousel/index', [
            'title'           => 'Homepage Carousel',
            'drms'            => drms_config(),
            'slides'          => (new HomeCarouselService())->listAll(),
            'backgroundStyles'=> [
                'gradient-1' => 'Crimson (default)',
                'gradient-2' => 'Deep red',
                'gradient-3' => 'Municipal maroon',
            ],
        ]);
    }

    public function list(): ResponseInterface
    {
        $guard = $this->guardAdmin();
        if ($guard instanceof ResponseInterface) {
            return $guard;
        }

        return $this->crudOk('OK', ['items' => (new HomeCarouselService())->listAll()]);
    }

    public function show(int $id): ResponseInterface
    {
        $guard = $this->guardAdmin();
        if ($guard instanceof ResponseInterface) {
            return $guard;
        }

        $row = (new HomeCarouselService())->find($id);
        if (! $row) {
            return $this->crudFail('Slide not found.', 404);
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
            $row = (new HomeCarouselService())->create(
                $this->mappedPost(),
                $this->request->getFile('image')
            );

            return $this->crudOk('Carousel slide created.', $row);
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

        if (! $this->validate($this->rules())) {
            return $this->crudValidationFail();
        }

        try {
            $row = (new HomeCarouselService())->update(
                $id,
                $this->mappedPost(),
                $this->request->getFile('image'),
                $this->request->getPost('remove_image') === '1'
            );

            return $this->crudOk('Carousel slide updated.', $row);
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
            (new HomeCarouselService())->delete($id);

            return $this->crudOk('Carousel slide deleted.');
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
    private function rules(): array
    {
        return [
            'title'       => 'required|max_length[255]',
            'badge_text'  => 'permit_empty|max_length[120]',
            'description' => 'permit_empty|max_length[2000]',
            'sort_order'  => 'permit_empty|integer',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mappedPost(): array
    {
        return [
            'badge_text'             => $this->request->getPost('badge_text'),
            'title'                  => $this->request->getPost('title'),
            'description'            => $this->request->getPost('description'),
            'background_style'       => $this->request->getPost('background_style'),
            'button_primary_label'   => $this->request->getPost('button_primary_label'),
            'button_primary_url'     => $this->request->getPost('button_primary_url'),
            'button_secondary_label' => $this->request->getPost('button_secondary_label'),
            'button_secondary_url'   => $this->request->getPost('button_secondary_url'),
            'sort_order'             => $this->request->getPost('sort_order'),
            'is_active'              => $this->request->getPost('is_active') ? 1 : 0,
        ];
    }
}
