<?php

declare(strict_types=1);

namespace App\Filters;

use App\Services\PresentationService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Demo / presentation mode: block POST writes before controllers run.
 * GET requests use the database (e.g. DrmsDemoSeeder sample rows) unless offline demo.
 */
class PresentationModeFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (is_cli()) {
            return null;
        }

        helper('drms');

        if (! function_exists('drms_presentation_active') || ! drms_presentation_active()) {
            return null;
        }

        $path         = trim($request->getUri()->getPath(), '/');
        $presentation = new PresentationService();
        $method       = strtolower($request->getMethod());

        if ($method === 'post') {
            return $presentation->blockWriteRequest($path, $request);
        }

        // Offline demo only — no MySQL: serve built-in fixture JSON for tables/charts.
        if ($method === 'get' && function_exists('drms_use_presentation_fixtures') && drms_use_presentation_fixtures()) {
            $fixture = $presentation->applyGetFixture($path, $request);
            if ($fixture !== null) {
                return service('response')->setJSON($fixture);
            }
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
