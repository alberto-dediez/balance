<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ContestEntry\Register;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostReference;
use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\Shared\Domain\Model\Consent\ConsentProof;
use App\Shared\Domain\Model\Locale\Locale;

class RegisterContestEntryRequest
{
    public function __construct(
        public ContestId $contestId,
        public ContestHostReference $host,
        public ProjectContributionOrigin $origin,
        public string $originId,
        public ?string $projectContributionId,
        public string $name,
        public string $email,
        public ?string $phone,
        public string $country,
        public Locale $locale,
        public ConsentProof $consentProof,
        public bool $consentAccepted,
        public bool $adultConfirmed,
        public \DateTimeImmutable $now = new \DateTimeImmutable('NOW'),
    ) {}
}
