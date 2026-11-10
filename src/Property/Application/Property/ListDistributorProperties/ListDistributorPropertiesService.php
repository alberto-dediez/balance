<?php

declare(strict_types=1);

namespace App\Property\Application\Property\ListDistributorProperties;

use App\Property\Application\Property\List\DistributorPropertiesScope;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\DistributorAssignment\AccountIsNotDistributorException;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Account\DistributorPolicy;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

class ListDistributorPropertiesService
{
    public function __construct(
        private readonly PropertyRepository $repository,
        private readonly PropertyAuthorizer $authorizer,
        private readonly DistributorPolicy $distributorPolicy,
        private readonly DistributorPropertiesScope $distributorPropertiesScope,
    ) {}

    public function __invoke(ListDistributorPropertiesRequest $request, User $user): ListDistributorPropertiesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Property(
            id: PropertyId::generate(),
            accountId: $request->distributorAccountId,
        ), Action::List, $user);

        if (!$this->distributorPolicy->isActiveDistributor($request->distributorAccountId)) {
            throw new AccountIsNotDistributorException($request->distributorAccountId);
        }

        $filter = new PropertyFilter(accountId: $request->distributorAccountId, limit: 0);

        $this->distributorPropertiesScope->expand($filter);

        return new ListDistributorPropertiesResponse(
            $this->repository->findByFilter($filter),
            $this->repository->totalResultsByFilter($filter),
        );
    }
}
