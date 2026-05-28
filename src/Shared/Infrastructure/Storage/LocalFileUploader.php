<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Storage;

use App\Shared\Application\Storage\FileUploader;

class LocalFileUploader implements FileUploader
{
    private readonly string $uploadDir;

    public function __construct(
        string $rootDir,
    ) {
        $this->uploadDir = sprintf('%s/signature-requests', $rootDir);
    }

    public function __invoke(string $sourcePath, string $extension): string
    {
        $filename = uniqid('doc_', true) . '.' . $extension;
        $destPath = $this->uploadDir . '/' . $filename;

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }

        rename($sourcePath, $destPath);

        return $destPath;
    }
}
