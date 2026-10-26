<?php

declare(strict_types=1);

namespace App\Library\Application\Media\SyncStorage;

use App\Shared\Application\CommandBus\TimedCommand;

class SyncMediaStorageRequest implements TimedCommand
{
    public function __construct(
        public readonly \DateTimeImmutable $now,
        public readonly int $batchSize = 100,
    ) {}

    public function withNow(\DateTimeImmutable $now): self
    {
        return new self($now, $this->batchSize);
    }
}
