<?php

declare(strict_types=1);

namespace App\Library\Application\Media\MigrateStorage;

use App\Library\Domain\Model\Media\MediaStorage;
use App\Shared\Application\CommandBus\Command;

class MigrateMediaStorageRequest implements Command
{
    public function __construct(
        public MediaStorage $source,
        public MediaStorage $target,
        public bool $deleteSource = false,
        public bool $dryRun = false,
        public int $batchSize = 200,
    ) {}
}
