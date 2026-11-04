<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ProjectContribution;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;

interface DistributedContributionsResolver
{
    public function scopeFor(AccountId $distributorAccountId): ProjectContributionScope;

    public function propertyScopeFor(AccountId $viewerAccountId, PropertyId $propertyId, bool $includeDistributed): ProjectContributionScope;

    public function distributes(AccountId $distributorAccountId, ProjectContribution $projectContribution): bool;
}
