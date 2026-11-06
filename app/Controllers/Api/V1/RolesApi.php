<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Services\AuthCatalogService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

/**
 * Role & permission catalog — same Shield groups used by the admin UI.
 */
class RolesApi extends ResourceController
{
    protected $format = 'json';

    public function index(): ResponseInterface
    {
        $catalog = (new AuthCatalogService())->catalog();

        return $this->respond([
            'data' => $catalog['roles'],
            'meta' => [
                'count'        => count($catalog['roles']),
                'permissions'  => $catalog['permissions'],
                'default_role' => $catalog['default_role'],
            ],
        ]);
    }
}
