<?php

namespace App\Waste\Application\Container\Update;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Waste\Application\Container\ContainerAuthorizer;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;

class UpdateContainerService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

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
            measurementMethod: $request->measurementMethod,
            volume: $request->volume,
            wasteManagerId: $request->wasteManagerId
        );

        $this->containerRepository->add($container);

        return new UpdateContainerResponse(
            containerId: $container->id(),
            wasteId: $container->wasteId(),
            purpose: $container->purpose(),
            measurementMethod: $container->measurementMethod(),
            volume: $container->volume(),
            wasteManagerId: $container->wasteManagerId()
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateContainerRequest;
    }
}