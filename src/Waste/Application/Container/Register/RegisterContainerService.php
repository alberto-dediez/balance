<?php

namespace App\Waste\Application\Container\Register;

use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\QrCodeGenerator;
use App\Waste\Domain\Model\Property\PropertyRepository;
use App\Waste\Domain\Model\Container\ContainerRepository;

class RegisterContainerService
{
    public function __construct(
        private ContainerRepository $containerRepository,
        private PropertyRepository $propertyRepository,
        private QrCodeGenerator $qrCodeGenerator
    ) {

    }

    public function __invoke(RegisterContainerRequest $request): RegisterContainerResponse
    {
        if (null == $request->propertyId || null == $request->wasteId) {
            throw new \InvalidArgumentException("Debes especificar una propiedad y tipologia de resiudo");
        }

        $property = $this->propertyRepository->find($request->propertyId);

        if (null == $property) {
            throw new \InvalidArgumentException("Debes especificar una propiedad válida");
        }

        $id = ContainerId::generate();

        $container = new Container($id);
        $container
            ->register(
                accountId: $property->accountId(),
                propertyId: $request->propertyId,
                wasteId: $request->wasteId,
                purpose: $request->purpose,
                measurementMethod: $request->measurementMethod,
                qrCode: $this->qrCodeGenerator->generate($id)
            )
        ;

        $this->containerRepository->add($container);

        return new RegisterContainerResponse(
            containerId: $container->id(),
            propertyId: $container->propertyId(),
            wasteId: $container->wasteId(),
            purpose: $container->purpose(),
            measurementMethod: $container->measurementMethod(),
            qrCode: $container->qrCode()
        );
    }
}