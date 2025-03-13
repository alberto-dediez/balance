<?php

namespace App\Waste\Application\Container\Update;

use InvalidArgumentException;
use App\Waste\Domain\Model\Container\ContainerRepository;

class UpdateContainerService
{
    public function __construct(
        private ContainerRepository $containerRepository
    ) {

    }

    public function __invoke(UpdateContainerRequest $request): UpdateContainerResponse
    {
        $container = $this->containerRepository->find($request->containerId);
        
        if (null == $container) {
            throw new InvalidArgumentException("Container not found");
        }

        if (null == $request->wasteId) {
            throw new InvalidArgumentException("Debe especificar una tipologia de residuo");
        }

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