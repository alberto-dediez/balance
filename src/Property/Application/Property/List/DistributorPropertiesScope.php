<?php

declare(strict_types=1);

namespace App\Property\Application\Property\List;

use App\Property\Application\DistributorAssignment\DistributedPropertiesPolicy;
use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyScope;
use App\Security\Application\Account\DistributorPolicy;
use App\Security\Domain\Model\Account\AccountFeature;
use App\Security\Domain\Model\Account\AccountId;

final class DistributorPropertiesScope
{
    public function __construct(
        private readonly DistributedPropertiesPolicy $distributedPropertiesPolicy,
        private readonly DistributorPolicy $distributorPolicy,
    ) {}

    public function expand(PropertyFilter $filter): void
    {
        $accountId = $filter->accountId;

        if (!$accountId instanceof AccountId || !$this->distributorPolicy->isActiveDistributor($accountId)) {
            return;
        }

        $filter->scope = PropertyScope::forAccount($accountId)
            ->merge($this->distributedPropertiesPolicy->scopeFor($accountId, AccountFeature::PropertyTripCalculators));
        $filter->accountId = null;
    }
}
