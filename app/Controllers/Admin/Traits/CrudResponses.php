<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Traits;

use CodeIgniter\HTTP\ResponseInterface;

trait CrudResponses
{
    /**
     * @param array<string, mixed> $data
     */
    protected function crudOk(string $message, array $data = []): ResponseInterface
    {
        return crud_json_ok($message, $data);
    }

    /**
     * @param array<string, mixed> $errors
     */
    protected function crudFail(string $message, int $code = 400, array $errors = []): ResponseInterface
    {
        return crud_json_error($message, $code, $errors);
    }

    protected function crudValidationFail(): ResponseInterface
    {
        return crud_json_error('Validation failed.', 422, $this->validator->getErrors());
    }
}
