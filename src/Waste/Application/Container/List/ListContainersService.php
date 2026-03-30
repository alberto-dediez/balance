<?php

declare(strict_types=1);

namespace App\Waste\Application\Container\List;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Waste\Application\Container\ContainerAuthorizer;
use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Waste\Domain\Model\Container\Purpose;

class ListContainersService
{
    public function __construct(private readonly ContainerRepository $repository, private readonly ContainerAuthorizer $authorizer) {}

    public function __invoke(ListContainersRequest $request, User $user): ListContainersResponse
    {
        $container = new Container(
            id: ContainerId::generate(),
            accountId: $request->accountId,
            propertyId: $request->propertyId,
            wasteId: $request->wasteId,
            purpose: $request->purpose ?? Purpose::Waste,
            measurementMethod: $request->measurementMethod ?? MeasurementMethod::Volume,
        );

        $this->authorizer->denyUnlessCanDoThat($container, Action::List, $user);

        $containers = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListContainersResponse($containers, $totalResults);
    }
}
