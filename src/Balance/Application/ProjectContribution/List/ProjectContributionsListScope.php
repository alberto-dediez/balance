<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\List;

use App\Balance\Domain\Model\ProjectContribution\DistributedContributionsResolver;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionScope;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;

final class ProjectContributionsListScope
{
    public function __construct(
        private readonly ?DistributedContributionsResolver $distributedContributionsResolver = null,
    ) {}

    public function apply(ListProjectContributionsRequest $filter, bool $isDistributor): void
    {
        $accountId = $filter->accountId;

        if (!$accountId instanceof AccountId || !$this->distributedContributionsResolver instanceof DistributedContributionsResolver) {
            return;
        }

        if ($filter->propertyId instanceof PropertyId) {
            $filter->scope = $this->distributedContributionsResolver->propertyScopeFor($accountId, $filter->propertyId, $isDistributor);

            if ($isDistributor) {
                $filter->accountId = null;
            }

            return;
        }

        if (!$isDistributor) {
            return;
        }

        $filter->scope = ProjectContributionScope::forAccount($accountId)
            ->merge($this->distributedContributionsResolver->scopeFor($accountId));
        $filter->accountId = null;
    }
}
