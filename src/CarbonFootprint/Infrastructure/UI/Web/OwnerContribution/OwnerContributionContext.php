<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OwnerContribution;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Security\Domain\Model\Account\AccountId;

final readonly class OwnerContributionContext
{
    public function __construct(
        public AccountId $accountId,
        public ProjectContributionOrigin $origin,
        public string $originId,
        public string $originName,
        public ?float $suggestedCo2eqKg,
    ) {}
}
