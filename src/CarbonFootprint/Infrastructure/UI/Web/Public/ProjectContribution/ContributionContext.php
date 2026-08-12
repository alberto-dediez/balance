<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\ProjectContribution;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Security\Domain\Model\Account\AccountId;

/** Everything the checkout needs to know about the flow the contributor came from. */
final readonly class ContributionContext
{
    public function __construct(
        public AccountId $accountId,
        public float $co2eqKg,
        public ProjectContributionOrigin $origin,
        public string $originId,
        public string $originName,
        public string $token,
    ) {}
}
