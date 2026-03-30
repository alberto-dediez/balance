<?php

declare(strict_types=1);

namespace App\Waste\Application\Container\Remove;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Waste\Application\Container\ContainerAuthorizer;
use App\Waste\Domain\Model\Container\ContainerRepository;

class RemoveContainerService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private ContainerRepository $repository, private ContainerAuthorizer $authorizer) {}

    public function __invoke(RemoveContainerRequest $request, User $user): RemoveContainerResponse
    {
        $container = $this->repository->find($request->containerId);

        if (null == $container) {
            throw new \InvalidArgumentException('Container not found');
        }

        $this->authorizer->denyUnlessCanDoThat($container, Action::Remove, $user);

        $container->remove();

        $this->repository->add($container);

        return new RemoveContainerResponse(
            containerId: $container->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveContainerRequest;
    }
}
