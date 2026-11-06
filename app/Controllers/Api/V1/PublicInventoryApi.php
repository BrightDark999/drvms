<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Services\InventoryService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Read-only public inventory API for integrations.
 *
 * Only active inventory records are returned. Mutations remain available solely
 * through the authenticated /api/v1/inventory endpoints.
 */
class PublicInventoryApi extends BaseController
{
    public function docs(): string
    {
        return view('api/docs', [
            'listUrl' => site_url('api/v1/public/inventory'),
            'itemUrl' => site_url('api/v1/public/inventory/{id}'),
        ]);
    }

    public function index(): ResponseInterface
    {
        $limit = min(500, max(1, (int) ($this->request->getGet('limit') ?? 200)));
        $rows  = (new InventoryService())->listForScope('all', $limit);

        return $this->publicJson([
            'data' => array_map([$this, 'publicFields'], $rows),
            'meta' => [
                'count'     => count($rows),
                'limit'     => $limit,
                'read_only' => true,
            ],
        ]);
    }

    public function show($id = null): ResponseInterface
    {
        $id  = (int) $id;
        $row = $id > 0 ? (new InventoryService())->findActive($id) : null;

        if ($row === null) {
            return $this->publicJson([
                'status'  => 404,
                'error'   => 404,
                'message' => 'Active inventory item not found.',
            ], 404);
        }

        return $this->publicJson(['data' => $this->publicFields($row)]);
    }

    /**
     * Do not publish internal warehouse locations, thresholds, or staff notes.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function publicFields(array $row): array
    {
        return [
            'id'             => $row['id'],
            'sku'            => $row['sku'],
            'name'           => $row['name'],
            'category'       => $row['category'],
            'category_label' => $row['category_label'],
            'quantity'       => $row['quantity'],
            'unit'           => $row['unit'],
            'expires_at'     => $row['expires_at'],
            'is_low_stock'   => $row['is_low_stock'],
            'updated_at'     => $row['updated_at'],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function publicJson(array $payload, int $status = 200): ResponseInterface
    {
        return $this->response
            ->setStatusCode($status)
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Cache-Control', 'public, max-age=60')
            ->setJSON($payload);
    }
}
