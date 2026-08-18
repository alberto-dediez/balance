<?php

declare(strict_types=1);

namespace App\Balance\Application\LedgerEntry\Series;

final class ListLedgerSeriesRequest
{
    public function __construct(
        public readonly \DateTimeImmutable $from,
        public readonly \DateTimeImmutable $to,
        public readonly int $topBeneficiaries = 10,
    ) {}
}
