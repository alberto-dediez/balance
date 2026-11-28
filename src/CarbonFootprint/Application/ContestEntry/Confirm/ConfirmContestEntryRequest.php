<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ContestEntry\Confirm;

class ConfirmContestEntryRequest
{
    public function __construct(
        public string $projectContributionId,
        public \DateTimeImmutable $now = new \DateTimeImmutable('NOW'),
    ) {}
}
