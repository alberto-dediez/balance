<?php

declare(strict_types=1);

namespace App\Library\Application\Media\ExtractBackup;

use App\Shared\Application\CommandBus\CommandResponse;

class ExtractMediaBackupResponse implements CommandResponse
{
    public function __construct(
        public string $path,
        public string $outputPath,
    ) {}
}
