<?php

declare(strict_types=1);

namespace App\Library\Application\Media\ExtractBackup;

use App\Shared\Application\CommandBus\Command;

class ExtractMediaBackupRequest implements Command
{
    public function __construct(
        public string $mediaId,
        public string $outputPath,
    ) {}
}
