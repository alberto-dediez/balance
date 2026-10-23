<?php

declare(strict_types=1);

namespace App\DataExchange\Application\Exportation\Purge;

use App\Shared\Application\CommandBus\TimedCommand;

class PurgeExpiredExportationsRequest implements TimedCommand
{
    public function __construct(
        public \DateTimeImmutable $now = new \DateTimeImmutable('NOW'),
    ) {}

    public function withNow(\DateTimeImmutable $now): self
    {
        return new self($now);
    }
}
