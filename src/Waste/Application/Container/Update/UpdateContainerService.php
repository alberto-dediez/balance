<?php

namespace App\Waste\Application\Container\Update;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Waste\Application\Container\ContainerAuthorizer;
use App\Security\Application\UnauthorizedActionException;
use App\Waste\Domain\Model\Container\ContainerRepository;

class UpdateContainerService
{
    public function __construct(
        private ContainerRepository $containerRepository,
        private ContainerAuthorizer $authorizer
    ) {

    }

    public function __invoke(UpdateContainerRequest $request, User $user): UpdateContainerResponse
    {
        $container = $this->containerRepository->find($request->containerId);
        
        if (null == $container) {
            throw new InvalidArgumentException("Container not found");
        }

        if (null == $request->wasteId) {
            throw new InvalidArgumentException("Debe especificar una tipologia de residuo");
        }

        $this->authorizer->denyUnlessCanDoThat($container, Action::Edit, $user);

        $container->update(
            wasteId: $request->wasteId,
            purpose: $request->purpose,
            measurementMethod: $request->measurementMethod
        );

        $this->containerRepository->add($container);

        return new UpdateContainerResponse(
            containerId: $container->id(),
            wasteId: $container->wasteId(),
            purpose: $container->purpose(),
            measurementMethod: $container->measurementMethod()
        );
    }
}