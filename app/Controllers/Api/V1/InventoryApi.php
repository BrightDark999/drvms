<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Services\InventoryService;
use App\Services\Integrations\ExternalReliefApiClient;
use App\Validation\InventoryRules;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use RuntimeException;

/**
 * Shared inventory — DRMS scope (medical/emergency) vs full catalog (relief).
 */
class InventoryApi extends ResourceController
{
    protected $format = 'json';

    public function index(): ResponseInterface
    {
        if (! $this->canRead()) {
            return $this->failForbidden('Requires admin.access or inventory.* permission.');
        }

        $scope = strtolower(trim((string) ($this->request->getGet('scope') ?? 'drms')));
        if (! in_array($scope, ['drms', 'all', 'relief'], true)) {
            $scope = 'drms';
        }
        if ($scope === 'relief') {
            $scope = 'all';
        }

        if ($scope === 'all' && ! $this->canReadAll()) {
            return $this->failForbidden('Full inventory catalog requires inventory.* or admin.* permission.');
        }

        $service = new InventoryService();
        $data    = $this->resolveInventoryList($scope);
        $summary = $service->summaryForScope($scope);

        return $this->respond([
            'data' => $data,
            'meta' => [
                'count'           => count($data),
                'scope'           => $scope,
                'source'          => $this->inventorySource($scope),
                'summary'         => $summary,
                'drms_categories' => $service->drmsCategories(),
            ],
        ]);
    }

    public function show($id = null): ResponseInterface
    {
        $id = (int) $id;
        if ($id <= 0) {
            return $this->failNotFound('Item not found.');
        }
        if (! $this->canRead()) {
            return $this->failForbidden('Requires admin.access or inventory.* permission.');
        }

        $row = (new InventoryService())->find($id);
        if ($row === null) {
            return $this->failNotFound('Item not found.');
        }

        if (empty($row['is_drms_scope']) && ! $this->canReadAll()) {
            return $this->failForbidden('This item is outside the DRMS medical/emergency catalog.');
        }

        return $this->respond(['data' => $row]);
    }

    public function create(): ResponseInterface
    {
        if (! $this->canWrite()) {
            return $this->failForbidden('Requires inventory.* or admin.* permission.');
        }

        $input = $this->request->getJSON(true) ?? $this->request->getPost();

        if (! $this->validate(InventoryRules::save())) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        try {
            $row = (new InventoryService())->create($input, auth()->id());

            return $this->respondCreated([
                'data'    => $row,
                'message' => 'Supply item added.',
            ]);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        }
    }

    public function update($id = null): ResponseInterface
    {
        $id = (int) $id;
        if ($id <= 0) {
            return $this->failNotFound('Item not found.');
        }
        if (! $this->canWrite()) {
            return $this->failForbidden('Requires inventory.* or admin.* permission.');
        }

        $input = $this->request->getJSON(true) ?? $this->request->getRawInput();

        if (! $this->validate(InventoryRules::save($id))) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        try {
            $row = (new InventoryService())->update($id, $input, auth()->id());

            return $this->respond([
                'data'    => $row,
                'message' => 'Supply item updated.',
            ]);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        }
    }

    public function delete($id = null): ResponseInterface
    {
        $id = (int) $id;
        if ($id <= 0) {
            return $this->failNotFound('Item not found.');
        }
        if (! $this->canWrite()) {
            return $this->failForbidden('Requires inventory.* or admin.* permission.');
        }

        try {
            (new InventoryService())->deactivate($id, auth()->id());
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        }

        return $this->respondDeleted(['message' => 'Supply item deactivated.']);
    }

    public function adjust($id = null): ResponseInterface
    {
        $id = (int) $id;
        if ($id <= 0) {
            return $this->failNotFound('Item not found.');
        }
        if (! $this->canWrite()) {
            return $this->failForbidden('Requires inventory.* or admin.* permission.');
        }

        $input = $this->request->getJSON(true) ?? $this->request->getPost();

        if (! $this->validate(InventoryRules::adjustStock())) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        try {
            $row = (new InventoryService())->adjustStock(
                $id,
                (float) ($input['quantity_delta'] ?? 0),
                (string) ($input['direction'] ?? 'add'),
                (string) ($input['reason'] ?? ''),
                auth()->id(),
            );

            return $this->respond([
                'data'    => $row,
                'message' => 'Stock level updated.',
            ]);
        } catch (RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function resolveInventoryList(string $scope): array
    {
        $service = new InventoryService();
        $client  = new ExternalReliefApiClient();

        if ($scope === 'all' && $client->isEnabled()) {
            try {
                $remote = $client->listInventory();

                return $remote !== [] ? $remote : $service->listForScope('all');
            } catch (\Throwable) {
                return $service->listForScope('all');
            }
        }

        if ($scope === 'drms' && $client->isEnabled()) {
            try {
                $remote = $client->listInventory();
                if ($remote !== []) {
                    return $this->filterRemoteForDrms($remote, $service);
                }
            } catch (\Throwable) {
                // fall through to local DB
            }
        }

        return $service->listForScope($scope);
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function filterRemoteForDrms(array $rows, InventoryService $service): array
    {
        $cats = $service->drmsCategories();
        $out  = [];
        foreach ($rows as $row) {
            $cat = strtolower((string) ($row['category'] ?? ''));
            if (in_array($cat, $cats, true)) {
                $out[] = $row;
            }
        }

        return $out !== [] ? $out : $service->listForScope('drms');
    }

    private function inventorySource(string $scope): string
    {
        $client = new ExternalReliefApiClient();
        if ($client->isEnabled()) {
            return 'external_relief_api';
        }

        return 'shared_database';
    }

    private function canRead(): bool
    {
        return drms_user_can('admin.access', 'inventory.*', 'admin.*');
    }

    private function canReadAll(): bool
    {
        return drms_user_can('inventory.*', 'admin.*');
    }

    private function canWrite(): bool
    {
        return drms_user_can('inventory.*', 'admin.*', 'incidents.*');
    }
}
