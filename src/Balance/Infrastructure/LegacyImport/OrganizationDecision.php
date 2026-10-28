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
        public string $accountKey = '',
        public ?string $group = null,
        public bool $consultant = false,
        public bool $createProperty = false,
    ) {}

    public function isSkipped(): bool
    {
        return OrganizationTarget::Skip === $this->target;
    }

    public function hasProperty(): bool
    {
        return null !== $this->propertyId || $this->createProperty;
    }

    public function usesAccountCalculator(LegacyOrganization $organization): bool
    {
        return $organization->isOta() || $this->consultant;
    }
}
