<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CaptchaService;
use CodeIgniter\HTTP\ResponseInterface;

class CaptchaController extends BaseController
{
    public function image(): ResponseInterface
    {
        $svg = (new CaptchaService())->generateSvg();

        return $this->response
            ->setHeader('Content-Type', 'image/svg+xml')
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setBody($svg);
    }
}
