<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ExchangeRate;

final readonly class ExchangeRateFeed
{
    /** @param list<ExchangeRateFeedEntry> $entries */
    public function __construct(
        public \DateTimeImmutable $date,
        public array $entries,
    ) {}
}
