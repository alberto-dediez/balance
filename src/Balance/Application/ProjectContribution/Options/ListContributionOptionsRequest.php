<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Options;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;

final class ListContributionOptionsRequest
{
    public function __construct(
        public readonly AccountId $accountId,
        public readonly ?float $co2eqKg = null,
        public readonly bool $ownerMode = false,
        public readonly bool $allowSharedCredit = false,
        public readonly ?PropertyId $propertyId = null,
        public readonly ?\DateTimeImmutable $now = null,
    ) {}
}
