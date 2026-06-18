<?php

declare(strict_types=1);

namespace App\DataExchange\Application\Exportation\Purge;

use App\Shared\Application\CommandBus\CommandResponse;

class PurgeExpiredExportationsResponse implements CommandResponse
{
    public function __construct(
        public int $purged,
    ) {}
}
