<?php

declare(strict_types=1);

namespace App\DataExchange\Application\Exportation\Purge;

use App\Shared\Application\CommandBus\Command;

class PurgeExpiredExportationsRequest implements Command
{
    public function __construct(
        public \DateTimeImmutable $now = new \DateTimeImmutable('NOW'),
    ) {}
}
