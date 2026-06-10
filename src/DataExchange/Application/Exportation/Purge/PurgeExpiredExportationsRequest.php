<?php

declare(strict_types=1);

namespace App\DataExchange\Application\Exportation\Purge;

class PurgeExpiredExportationsRequest
{
    public function __construct(
        public \DateTimeImmutable $now = new \DateTimeImmutable('NOW'),
    ) {}
}
