<?php

declare(strict_types=1);

namespace App\Shared\Application\Storage;

interface FileUploader
{
    public function __invoke(string $sourcePath, string $extension): string;
}
