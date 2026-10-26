<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\LegacyImport;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;

final class OrganizationDecision
{
    public function __construct(
        public string $slug,
        public OrganizationTarget $target,
        public ?AccountId $accountId = null,
        public bool $staff = false,
        public ?PropertyId $propertyId = null,
    ) {}

    public function isSkipped(): bool
    {
        return OrganizationTarget::Skip === $this->target;
    }
}
