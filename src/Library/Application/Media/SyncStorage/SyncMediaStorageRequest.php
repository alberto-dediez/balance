<?php

declare(strict_types=1);

namespace App\Library\Application\Media\SyncStorage;

use App\Shared\Application\CommandBus\Command;

class SyncMediaStorageRequest implements Command
{
    public function __construct(
        public readonly \DateTimeImmutable $now,
        public readonly int $batchSize = 100,
    ) {}
}
