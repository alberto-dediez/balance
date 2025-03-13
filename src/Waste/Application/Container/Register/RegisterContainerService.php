<?php

namespace App\Waste\Application\Container\Register;

use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\ContainerRepository;

class RegisterContainerService
{
    public function __construct(
        private ContainerRepository $containerRepository
    ) {

    }

    public function __invoke(RegisterContainerRequest $request): RegisterContainerResponse
    {
        if (null == $request->propertyId || null == $request->wasteId) {
            throw new \InvalidArgumentException("Debes especificar una propiedad y tipologia de resiudo");
        }

        $id = ContainerId::generate();

        $container = new Container($id);
        $container
            ->register(
                propertyId: $request->propertyId,
                wasteId: $request->wasteId,
                purpose: $request->purpose,
                measurementMethod: $request->measurementMethod
            )
        ;

        $this->containerRepository->add($container);

        return new RegisterContainerResponse(
            containerId: $container->id(),
            propertyId: $container->propertyId(),
            wasteId: $container->wasteId(),
            purpose: $container->purpose(),
            measurementMethod: $container->measurementMethod()
        );
    }
}