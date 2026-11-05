<?php

declare(strict_types=1);

namespace App\Property\Application\Property\List;

use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

class ListPropertiesService
{
    public function __construct(
        private readonly PropertyRepository $repository,
        private readonly PropertyAuthorizer $authorizer,
        private readonly ?DistributorPropertiesScope $distributorPropertiesScope = null,
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
            || !$this->distributorPropertiesScope instanceof DistributorPropertiesScope
            || !$this->authorizer->isOwnerOfCurrentAccount($user, $accountId)
        ) {
            return;
        }

        $this->distributorPropertiesScope->expand($filter);
    }
}
