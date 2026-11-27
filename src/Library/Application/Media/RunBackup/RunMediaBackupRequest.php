<?php

declare(strict_types=1);

namespace App\Library\Application\Media\RunBackup;

use App\Shared\Application\CommandBus\TimedCommand;

class RunMediaBackupRequest implements TimedCommand
{
    public function __construct(
        public readonly \DateTimeImmutable $now,
        public readonly int $batchSize = 200,
        public readonly int $maxFailures = 20,
    ) {}

    public function withNow(\DateTimeImmutable $now): self
    {
        return new self($now, $this->batchSize, $this->maxFailures);
    }
}
