<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLogModel;
use Throwable;

/**
 * Central place to insert audit rows (called after important mutations).
 */
class AuditLogService
{
    public function log(string $action, string $entityType, ?int $entityId = null, ?array $meta = null): void
    {
        try {
            $uid = auth()->id();
            model(AuditLogModel::class)->insert([
                'user_id'     => $uid ?: null,
                'action'      => $action,
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'meta_json'   => $meta ? json_encode($meta) : null,
                'ip_address'  => service('request')->getIPAddress(),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'AuditLogService: ' . $e->getMessage());
        }
    }
}
