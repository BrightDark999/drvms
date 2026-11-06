<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InventoryItemModel;
use Config\DrmsFeatures;
use RuntimeException;

/**
 * Shared inventory master data.
 *
 * DRMS admin shows medical / emergency categories only.
 * Relief system reads the same `inventory_items` table with scope=all (food, hygiene, etc.).
 */
class InventoryService
{
    public function __construct(
        private InventoryItemModel $items = new InventoryItemModel(),
        private AuditLogService $audit = new AuditLogService(),
    ) {
    }

    /**
     * @return list<string>
     */
    public function drmsCategories(): array
    {
        return config(DrmsFeatures::class)->inventoryDrmsCategories;
    }

    /**
     * @return array<string, string>
     */
    public function categoryLabels(): array
    {
        return config(DrmsFeatures::class)->inventoryCategories;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForScope(string $scope = 'drms', int $limit = 200): array
    {
        $limit = min(500, max(1, $limit));
        $builder = $this->items->builder()
            ->where('inventory_items.deleted_at', null)
            ->where('inventory_items.is_active', 1);

        if ($scope === 'drms') {
            $cats = $this->drmsCategories();
            if ($cats !== []) {
                $builder->whereIn('inventory_items.category', $cats);
            }
        }

        $rows = $builder
            ->orderBy('inventory_items.category', 'ASC')
            ->orderBy('inventory_items.name', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();

        return array_map(fn (array $row): array => $this->formatApiRow($row), $rows);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $row = $this->items->find($id);

        return is_array($row) ? $this->formatApiRow($row) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findActive(int $id): ?array
    {
        $row = $this->items
            ->where('id', $id)
            ->where('is_active', 1)
            ->first();

        return is_array($row) ? $this->formatApiRow($row) : null;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function formatApiRow(array $row): array
    {
        helper('drms');

        $qty   = (float) ($row['quantity'] ?? 0);
        $low   = isset($row['low_stock_threshold']) ? (float) $row['low_stock_threshold'] : null;
        $cat   = (string) ($row['category'] ?? '');
        $labels = $this->categoryLabels();

        return [
            'id'                  => (int) ($row['id'] ?? 0),
            'sku'                 => (string) ($row['sku'] ?? ''),
            'name'                => (string) ($row['name'] ?? ''),
            'category'            => $cat,
            'category_label'      => $labels[$cat] ?? ucfirst(str_replace('_', ' ', $cat)),
            'quantity'            => $qty,
            'unit'                => (string) ($row['unit'] ?? 'pc'),
            'low_stock_threshold' => $low,
            'warehouse_location'  => (string) ($row['warehouse_location'] ?? ''),
            'expires_at'          => (string) ($row['expires_at'] ?? ''),
            'notes'               => (string) ($row['notes'] ?? ''),
            'is_low_stock'        => $low !== null && $qty <= $low,
            'is_drms_scope'       => in_array($cat, $this->drmsCategories(), true),
            'updated_at'          => drms_format_datetime((string) ($row['updated_at'] ?? '')),
        ];
    }

    /**
     * @return array{total:int, low_stock:int, categories:array<string, int>}
     */
    public function summaryForScope(string $scope = 'drms'): array
    {
        $rows = $this->listForScope($scope, 500);
        $categories = [];
        $lowStock   = 0;

        foreach ($rows as $row) {
            $cat = (string) ($row['category'] ?? 'other');
            $categories[$cat] = ($categories[$cat] ?? 0) + 1;
            if (! empty($row['is_low_stock'])) {
                $lowStock++;
            }
        }

        return [
            'total'      => count($rows),
            'low_stock'  => $lowStock,
            'categories' => $categories,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data, ?int $userId = null): array
    {
        $category = $this->normalizeDrmsCategory((string) ($data['category'] ?? ''));

        $payload = [
            'sku'                 => strtoupper(trim((string) ($data['sku'] ?? ''))),
            'name'                => trim((string) ($data['name'] ?? '')),
            'category'            => $category,
            'quantity'            => max(0, (float) ($data['quantity'] ?? 0)),
            'unit'                => trim((string) ($data['unit'] ?? 'pc')) ?: 'pc',
            'low_stock_threshold' => $this->nullableFloat($data['low_stock_threshold'] ?? null),
            'warehouse_location'  => trim((string) ($data['warehouse_location'] ?? '')),
            'expires_at'          => $this->nullableDate($data['expires_at'] ?? null),
            'notes'               => trim((string) ($data['notes'] ?? '')),
            'is_active'           => 1,
        ];

        if ($payload['sku'] === '' || $payload['name'] === '') {
            throw new RuntimeException('SKU and item name are required.');
        }

        if (! $this->items->insert($payload)) {
            throw new RuntimeException(implode(' ', $this->items->errors()));
        }

        $id = (int) $this->items->getInsertID();
        $this->audit->log('inventory.create', 'inventory_item', $id, [
            'sku'      => $payload['sku'],
            'category' => $category,
            'user_id'  => $userId,
        ]);

        $row = $this->find($id);

        return $row ?? $payload;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function update(int $id, array $data, ?int $userId = null): array
    {
        $existing = $this->requireDrmsItem($id);

        $category = isset($data['category'])
            ? $this->normalizeDrmsCategory((string) $data['category'])
            : (string) ($existing['category'] ?? '');

        $payload = [
            'sku'                 => strtoupper(trim((string) ($data['sku'] ?? $existing['sku'] ?? ''))),
            'name'                => trim((string) ($data['name'] ?? $existing['name'] ?? '')),
            'category'            => $category,
            'unit'                => trim((string) ($data['unit'] ?? $existing['unit'] ?? 'pc')) ?: 'pc',
            'low_stock_threshold' => array_key_exists('low_stock_threshold', $data)
                ? $this->nullableFloat($data['low_stock_threshold'])
                : $existing['low_stock_threshold'],
            'warehouse_location'  => trim((string) ($data['warehouse_location'] ?? $existing['warehouse_location'] ?? '')),
            'expires_at'          => array_key_exists('expires_at', $data)
                ? $this->nullableDate($data['expires_at'])
                : ($existing['expires_at'] !== '' ? $existing['expires_at'] : null),
            'notes'               => trim((string) ($data['notes'] ?? $existing['notes'] ?? '')),
        ];

        if (array_key_exists('quantity', $data)) {
            $payload['quantity'] = max(0, (float) $data['quantity']);
        }

        if (! $this->items->update($id, $payload)) {
            throw new RuntimeException(implode(' ', $this->items->errors()));
        }

        $this->audit->log('inventory.update', 'inventory_item', $id, [
            'sku'     => $payload['sku'],
            'user_id' => $userId,
        ]);

        return $this->find($id) ?? $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function adjustStock(int $id, float $delta, string $direction, string $reason, ?int $userId = null): array
    {
        $existing = $this->requireDrmsItem($id);
        $raw      = $this->items->find($id);
        if (! is_array($raw)) {
            throw new RuntimeException('Item not found.');
        }

        $current = (float) ($raw['quantity'] ?? 0);
        $change  = abs($delta);
        $newQty  = $direction === 'remove' ? $current - $change : $current + $change;
        if ($newQty < 0) {
            throw new RuntimeException('Cannot remove more than the current quantity (' . $current . ').');
        }

        if (! $this->items->update($id, ['quantity' => $newQty])) {
            throw new RuntimeException('Could not update stock level.');
        }

        $this->audit->log('inventory.adjust', 'inventory_item', $id, [
            'direction' => $direction,
            'delta'     => $change,
            'from'      => $current,
            'to'        => $newQty,
            'reason'    => trim($reason),
            'user_id'   => $userId,
        ]);

        $row = $this->find($id);
        if ($row === null) {
            throw new RuntimeException('Item not found after adjustment.');
        }

        return $row;
    }

    public function deactivate(int $id, ?int $userId = null): void
    {
        $this->requireDrmsItem($id);

        if (! $this->items->delete($id)) {
            throw new RuntimeException('Could not deactivate item.');
        }

        $this->audit->log('inventory.deactivate', 'inventory_item', $id, ['user_id' => $userId]);
    }

    /**
     * @return array<string, mixed>
     */
    private function requireDrmsItem(int $id): array
    {
        $row = $this->find($id);
        if ($row === null) {
            throw new RuntimeException('Item not found.');
        }
        if (empty($row['is_drms_scope'])) {
            throw new RuntimeException('This item is managed by the relief inventory module.');
        }

        return $row;
    }

    private function normalizeDrmsCategory(string $category): string
    {
        $category = strtolower(trim($category));
        if (! in_array($category, $this->drmsCategories(), true)) {
            throw new RuntimeException('DRMS can only manage medical, emergency, and first-aid categories.');
        }

        return $category;
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    private function nullableDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
