<?php

namespace App\Waste\Application\Container\Remove;

use App\Waste\Domain\Model\Container\ContainerRepository;
use InvalidArgumentException;

class RemoveContainerService
{
    public function __construct(private ContainerRepository $repository)
    {

    }

    public function __invoke(RemoveContainerRequest $request): RemoveContainerResponse
    {
        $container = $this->repository->find($request->containerId);

        if (null == $container) {
            throw new InvalidArgumentException("Container not found");
        }

        $container->remove();

        $this->repository->add($container);

        return new RemoveContainerResponse(
            containerId: $container->id()
        );
    }
}