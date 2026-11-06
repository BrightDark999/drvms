<?php

declare(strict_types=1);

use CodeIgniter\HTTP\ResponseInterface;

if (! function_exists('crud_json_ok')) {
    /**
     * @param array<string, mixed> $data
     */
    function crud_json_ok(string $message, array $data = []): ResponseInterface
    {
        $security = config('Security');

        return service('response')
            ->setJSON([
                'status'  => 'ok',
                'message' => $message,
                'data'    => $data,
            ])
            ->setHeader($security->headerName, csrf_hash());
    }
}

if (! function_exists('crud_json_error')) {
    /**
     * @param array<string, mixed> $errors
     */
    function crud_json_error(string $message, int $code = 400, array $errors = []): ResponseInterface
    {
        $payload = ['status' => 'error', 'message' => $message];
        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        $security = config('Security');

        return service('response')
            ->setStatusCode($code)
            ->setJSON($payload)
            ->setHeader($security->headerName, csrf_hash());
    }
}

if (! function_exists('drms_icon_button')) {
    /**
     * Icon-only action control with tooltip (title + aria-label).
     *
     * @param array<string, scalar|null> $dataAttrs  Keys become data-* attributes (e.g. id => 5 → data-id="5")
     */
    function drms_icon_button(
        string $variant,
        string $icon,
        string $title,
        string $extraClass = '',
        array $dataAttrs = [],
        bool $asLink = false,
        ?string $href = null,
        bool $newTab = false,
    ): string {
        $classes = trim('btn btn-xs drms-icon-action ' . $variant . ' ' . $extraClass);
        $attr    = ' class="' . esc($classes, 'attr') . '"'
            . ' title="' . esc($title, 'attr') . '"'
            . ' aria-label="' . esc($title, 'attr') . '"';

        foreach ($dataAttrs as $key => $value) {
            if ($value === null) {
                continue;
            }
            $attr .= ' data-' . esc((string) $key, 'attr') . '="' . esc((string) $value, 'attr') . '"';
        }

        $inner = '<i class="' . esc($icon, 'attr') . '" aria-hidden="true"></i>';

        if ($asLink && $href !== null) {
            $target = $newTab ? ' target="_blank" rel="noopener noreferrer"' : '';

            return '<a href="' . esc($href, 'attr') . '"' . $attr . $target . '>' . $inner . '</a>';
        }

        return '<button type="button"' . $attr . '>' . $inner . '</button>';
    }
}

if (! function_exists('drms_action_group')) {
    function drms_action_group(string ...$buttons): string
    {
        if ($buttons === []) {
            return '<span class="text-muted">—</span>';
        }

        return '<div class="btn-group btn-group-xs drms-table-actions" role="group">' . implode('', $buttons) . '</div>';
    }
}

if (! function_exists('drms_table_actions')) {
    /**
     * Edit / delete icon buttons for DataTables action columns.
     */
    function drms_table_actions(int $id, bool $canDelete = true, bool $canEdit = true): string
    {
        if (! $canEdit && ! $canDelete) {
            return '<span class="text-muted">—</span>';
        }

        $buttons = [];
        if ($canEdit) {
            $buttons[] = drms_icon_button('btn-info', 'fas fa-edit', 'Edit', 'btn-drms-edit', ['id' => $id]);
        }
        if ($canDelete) {
            $buttons[] = drms_icon_button('btn-danger', 'fas fa-trash', 'Delete', 'btn-drms-delete', ['id' => $id]);
        }

        return drms_action_group(...$buttons);
    }
}

if (! function_exists('drms_evac_table_actions')) {
    /**
     * View families / edit / delete for evacuation center DataTables rows.
     */
    function drms_evac_table_actions(int $id): string
    {
        return drms_action_group(
            drms_icon_button('btn-primary', 'fas fa-users', 'View families', 'btn-evac-families', ['id' => $id]),
            drms_icon_button('btn-info', 'fas fa-edit', 'Edit center', 'btn-drms-edit', ['id' => $id]),
            drms_icon_button('btn-danger', 'fas fa-trash', 'Delete center', 'btn-drms-delete', ['id' => $id]),
        );
    }
}

if (! function_exists('drms_btn_verify_incident')) {
    function drms_btn_verify_incident(int $id): string
    {
        return drms_icon_button('btn-success', 'fas fa-check-circle', 'Verify incident', 'btn-verify-incident', ['id' => $id]);
    }
}

if (! function_exists('drms_btn_approve_user')) {
    function drms_btn_approve_user(int $id): string
    {
        return drms_icon_button('btn-success', 'fas fa-check', 'Approve user', 'btn-user-approve', ['id' => $id]);
    }
}

if (! function_exists('drms_btn_reject_user')) {
    function drms_btn_reject_user(int $id): string
    {
        return drms_icon_button('btn-warning', 'fas fa-times', 'Reject user', 'btn-user-reject', ['id' => $id]);
    }
}

if (! function_exists('drms_btn_verify_user_id')) {
    function drms_btn_verify_user_id(int $id): string
    {
        return drms_icon_button('btn-outline-success', 'fas fa-id-card', 'Verify ID document', 'btn-user-verify-id', ['id' => $id]);
    }
}

if (! function_exists('drms_btn_verify_volunteer_id')) {
    function drms_btn_verify_volunteer_id(int $id): string
    {
        return drms_icon_button('btn-success', 'fas fa-check', 'Approve ID', 'btn-verify-id', ['id' => $id]);
    }
}

if (! function_exists('drms_btn_reject_volunteer_id')) {
    function drms_btn_reject_volunteer_id(int $id): string
    {
        return drms_icon_button('btn-warning', 'fas fa-times', 'Reject ID', 'btn-reject-id', ['id' => $id]);
    }
}

if (! function_exists('drms_btn_view_id_document')) {
    function drms_btn_view_id_document(string $path): string
    {
        return drms_icon_button(
            'btn-outline-info',
            'fas fa-external-link-alt',
            'View ID document',
            'btn-view-id',
            [],
            true,
            base_url(ltrim($path, '/')),
            true,
        );
    }
}

if (! function_exists('drms_user_table_actions')) {
    function drms_user_table_actions(
        int $id,
        string $accountStatus,
        string $group,
        string $idStatus,
        bool $hasIdDocument,
    ): string {
        if ($group === 'admin') {
            return '<span class="text-muted small">Protected</span>';
        }

        $buttons = [
            drms_icon_button('btn-info', 'fas fa-edit', 'Edit user', 'btn-drms-edit', ['id' => $id]),
        ];

        if ($accountStatus === 'pending') {
            $buttons[] = drms_btn_approve_user($id);
            $buttons[] = drms_btn_reject_user($id);
        }

        if ($idStatus === 'pending' && $hasIdDocument) {
            $buttons[] = drms_btn_verify_user_id($id);
        }

        $buttons[] = drms_icon_button('btn-danger', 'fas fa-trash', 'Deactivate user', 'btn-user-delete', ['id' => $id]);

        return drms_action_group(...$buttons);
    }
}

if (! function_exists('drms_volunteer_table_actions')) {
    function drms_volunteer_table_actions(int $id, string $idStatus, bool $canVerify, ?string $idPath = null): string
    {
        $buttons = [];

        if ($canVerify && $idStatus === 'pending') {
            $buttons[] = drms_btn_verify_volunteer_id($id);
            $buttons[] = drms_btn_reject_volunteer_id($id);
        } elseif ($canVerify && $idPath !== null && $idPath !== '') {
            $buttons[] = drms_btn_view_id_document($idPath);
        }

        $buttons[] = drms_icon_button('btn-info', 'fas fa-edit', 'Edit volunteer', 'btn-drms-edit', ['id' => $id]);
        $buttons[] = drms_icon_button('btn-danger', 'fas fa-trash', 'Delete volunteer', 'btn-drms-delete', ['id' => $id]);

        return drms_action_group(...$buttons);
    }
}

if (! function_exists('drms_incident_table_actions')) {
    function drms_incident_table_actions(int $id, string $status): string
    {
        $buttons = [];
        if ($status === 'reported') {
            $buttons[] = drms_btn_verify_incident($id);
        }
        $buttons[] = drms_icon_button('btn-info', 'fas fa-edit', 'Edit incident', 'btn-drms-edit', ['id' => $id]);
        $buttons[] = drms_icon_button('btn-danger', 'fas fa-trash', 'Delete incident', 'btn-drms-delete', ['id' => $id]);

        return drms_action_group(...$buttons);
    }
}
