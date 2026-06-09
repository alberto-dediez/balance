<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\UI\Web\DTO;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImportFileRequest
{
    public ?UploadedFile $file = null;

    public int $offset = 0;
}
