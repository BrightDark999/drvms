<?php

declare(strict_types=1);

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\ResponseInterface;

if (! function_exists('datatable_response')) {
    /**
     * Build a JSON response for jQuery DataTables (server-side).
     *
     * @param list<array<string, mixed>> $rows
     */
    function datatable_response(IncomingRequest $request, array $rows, int $recordsTotal, ?int $recordsFiltered = null): ResponseInterface
    {
        $draw   = $request->getVar('draw');
        $draw   = is_numeric($draw) ? (int) $draw : 0;
        $filter = $recordsFiltered ?? $recordsTotal;

        return service('response')->setJSON([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $filter,
            'data'            => $rows,
        ]);
    }
}
