<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\HTTP\Files\UploadedFile;
use RuntimeException;

class FileUploadService
{
    /**
     * @param list<string> $allowedMimes
     * @return array{path:string, original_name:string, mime:string}
     */
    public function store(UploadedFile $file, string $subdir, array $allowedMimes, int $maxKb = 5120): array
    {
        if (! $file->isValid()) {
            throw new RuntimeException($file->getErrorString() ?: 'Invalid upload.');
        }

        if ($file->getSizeByUnit('kb') > $maxKb) {
            throw new RuntimeException('File exceeds maximum size of ' . $maxKb . ' KB.');
        }

        $mime = (string) $file->getMimeType();
        $mime = $this->normalizeMime($mime, (string) $file->getClientName());
        if ($allowedMimes !== [] && ! in_array($mime, $allowedMimes, true)) {
            throw new RuntimeException('File type not allowed: ' . $mime);
        }

        $subdir = trim($subdir, '/');
        $dir    = FCPATH . 'uploads/' . $subdir;
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException('Could not create upload directory.');
        }

        $newName = $file->getRandomName();
        if (! $file->move($dir, $newName)) {
            throw new RuntimeException('Could not save uploaded file.');
        }

        return [
            'path'          => 'uploads/' . $subdir . '/' . $newName,
            'original_name' => (string) $file->getClientName(),
            'mime'          => $mime,
        ];
    }

    /**
     * WAMP/Windows often reports application/octet-stream — infer from extension.
     */
    private function normalizeMime(string $detected, string $clientName): string
    {
        if ($detected !== '' && $detected !== 'application/octet-stream') {
            return $detected;
        }

        return match (strtolower(pathinfo($clientName, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'webp'        => 'image/webp',
            'pdf'         => 'application/pdf',
            default       => $detected,
        };
    }
}
