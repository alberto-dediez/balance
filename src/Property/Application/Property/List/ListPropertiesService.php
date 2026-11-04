<?php

declare(strict_types=1);

namespace App\Property\Application\Property\List;

use App\Property\Application\DistributorAssignment\DistributedPropertiesPolicy;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Property\Domain\Model\Property\PropertyScope;
use App\Security\Application\Account\DistributorPolicy;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountFeature;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

class ListPropertiesService
{
    public function __construct(
        private readonly PropertyRepository $repository,
        private readonly PropertyAuthorizer $authorizer,
        private readonly ?DistributedPropertiesPolicy $distributedPropertiesPolicy = null,
        private readonly ?DistributorPolicy $distributorPolicy = null,
    ) {}

    public function __invoke(ListPropertiesRequest $request, User $user): ListPropertiesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Property(
            id: PropertyId::generate(),
            accountId: $request->accountId,
        ), Action::List, $user);

        $filter = clone $request;

        $this->expandScopeForDistributor($filter, $user);

        $properties = $this->repository->findByFilter($filter);

        $totalResults = $this->repository->totalResultsByFilter($filter);

        return new ListPropertiesResponse($properties, $totalResults);
    }

    private function expandScopeForDistributor(ListPropertiesRequest $filter, User $user): void
    {
        $accountId = $filter->accountId;

        if (!$accountId instanceof AccountId
            || !$this->distributedPropertiesPolicy instanceof DistributedPropertiesPolicy
            || !$this->distributorPolicy instanceof DistributorPolicy
            || !$this->authorizer->isOwnerOfCurrentAccount($user, $accountId)
            || !$this->distributorPolicy->isActiveDistributor($accountId)
        ) {
            return;
        }

        $filter->scope = PropertyScope::forAccount($accountId)
            ->merge($this->distributedPropertiesPolicy->scopeFor($accountId, AccountFeature::PropertyTripCalculators));
        $filter->accountId = null;
    }
}
