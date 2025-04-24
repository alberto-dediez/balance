<?php

namespace App\Waste\Application\Container\List;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Application\Container\ContainerAuthorizer;
use App\Waste\Domain\Model\Container\ContainerRepository;

class ListContainersService
{
    public function __construct(private ContainerRepository $repository, private ContainerAuthorizer $authorizer)
    {

    }

    public function __invoke(ListContainersRequest $request, User $user): ListContainersResponse
    {
        $container = new Container(
            id: ContainerId::generate(),
            accountId: $request->accountId,
            propertyId: $request->propertyId,
            wasteId: $request->wasteId,
            purpose: $request->purpose,
            measurementMethod: $request->measurementMethod
        );

        $this->authorizer->denyUnlessCanDoThat($container, Action::List, $user);

        $containers = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListContainersResponse($containers, $totalResults);
    }
}