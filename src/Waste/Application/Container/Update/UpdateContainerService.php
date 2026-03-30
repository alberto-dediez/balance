<?php

declare(strict_types=1);

namespace App\Waste\Application\Container\Update;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Waste\Application\Container\ContainerAuthorizer;
use App\Waste\Domain\Model\Container\ContainerRepository;

class UpdateContainerService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ContainerRepository $containerRepository,
        private ContainerAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateContainerRequest $request, User $user): UpdateContainerResponse
    {
        $container = $this->containerRepository->find($request->containerId);

        if (null == $container) {
            throw new \InvalidArgumentException('Container not found');
        }

        if (null == $request->wasteId) {
            throw new \InvalidArgumentException('Debe especificar una tipologia de residuo');
        }

        $this->authorizer->denyUnlessCanDoThat($container, Action::Edit, $user);

        $container->update(
            wasteId: $request->wasteId,
            purpose: $request->purpose,
            measurementMethod: $request->measurementMethod,
            wasteManagerId: $request->wasteManagerId,
            volume: $request->volume,
        );

        $this->containerRepository->add($container);

        assert(null !== $container->wasteId());
        assert(null !== $container->wasteManagerId());

        return new UpdateContainerResponse(
            containerId: $container->id(),
            wasteId: $container->wasteId(),
            purpose: $container->purpose(),
            measurementMethod: $container->measurementMethod(),
            volume: $container->volume(),
            wasteManagerId: $container->wasteManagerId(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateContainerRequest;
    }
}
