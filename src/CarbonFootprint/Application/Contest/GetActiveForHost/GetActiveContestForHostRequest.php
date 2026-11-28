<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest\GetActiveForHost;

use App\CarbonFootprint\Domain\Model\Contest\ContestHostReference;

class GetActiveContestForHostRequest
{
    public function __construct(
        public ContestHostReference $host,
        public \DateTimeImmutable $now = new \DateTimeImmutable('NOW'),
    ) {}
}
