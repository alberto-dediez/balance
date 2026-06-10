<?php

declare(strict_types=1);

namespace App\DataExchange\Application\Exportation\Purge;

class PurgeExpiredExportationsResponse
{
    public function __construct(
        public int $purged,
    ) {}
}
