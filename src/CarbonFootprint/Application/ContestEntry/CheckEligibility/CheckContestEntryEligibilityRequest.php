<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ContestEntry\CheckEligibility;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostReference;
use App\CarbonFootprint\Domain\Model\Contest\ContestId;

class CheckContestEntryEligibilityRequest
{
    public function __construct(
        public ContestId $contestId,
        public ContestHostReference $host,
        public ProjectContributionOrigin $origin,
        public string $originId,
        public string $email,
        public string $country,
        public bool $consentAccepted,
        public bool $adultConfirmed,
        public \DateTimeImmutable $now = new \DateTimeImmutable('NOW'),
    ) {}
}
