<?php

namespace App\Waste\Application\Container\Remove;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Waste\Application\Container\ContainerAuthorizer;
use App\Waste\Domain\Model\Container\ContainerRepository;

class RemoveContainerService
{
    public function __construct(private ContainerRepository $repository, private ContainerAuthorizer $authorizer)
    {

    }

    public function __invoke(RemoveContainerRequest $request, User $user): RemoveContainerResponse
    {
        $container = $this->repository->find($request->containerId);

        if (null == $container) {
            throw new InvalidArgumentException("Container not found");
        }

        $this->authorizer->denyUnlessCanDoThat($container, Action::Remove, $user);

        $container->remove();

        $this->repository->add($container);

        return new RemoveContainerResponse(
            containerId: $container->id()
        );
    }
}