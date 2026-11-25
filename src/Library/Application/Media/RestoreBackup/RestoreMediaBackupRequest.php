<?php

declare(strict_types=1);

namespace App\Library\Application\Media\RestoreBackup;

use App\Library\Domain\Model\Media\MediaId;
use App\Shared\Application\CommandBus\Command;

class RestoreMediaBackupRequest implements Command
{
    public function __construct(
        public ?MediaId $mediaId = null,
        public bool $dryRun = false,
        public int $batchSize = 200,
    ) {}
}
