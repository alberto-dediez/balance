<?php

namespace App\Waste\Application\Container\Register;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\QrCodeGenerator;
use App\Waste\Domain\Model\Property\PropertyRepository;
use App\Waste\Application\Container\ContainerAuthorizer;
use App\Waste\Domain\Model\Container\ContainerRepository;

class RegisterContainerService
{
    public function __construct(
        private ContainerRepository $containerRepository,
        private PropertyRepository $propertyRepository,
        private QrCodeGenerator $qrCodeGenerator,
        private ContainerAuthorizer $authorizer
    ) {

    }

    public function __invoke(RegisterContainerRequest $request, User $user): RegisterContainerResponse
    {
        if (null == $request->propertyId || null == $request->wasteId) {
            throw new \InvalidArgumentException("Debes especificar una propiedad y tipologia de resiudo");
        }

        $property = $this->propertyRepository->find($request->propertyId);

        if (null == $property) {
            throw new \InvalidArgumentException("Debes especificar una propiedad válida");
        }

        $id = ContainerId::generate();

        $this->authorizer->denyUnlessCanDoThat(
            new Container(
                id: $id, 
                accountId: $property->accountId(),
                propertyId: $request->propertyId
            ), 
            Action::Register, 
            $user
        );

        $container = new Container($id);
        $container
            ->register(
                accountId: $property->accountId(),
                propertyId: $request->propertyId,
                wasteId: $request->wasteId,
                purpose: $request->purpose,
                measurementMethod: $request->measurementMethod,
                wasteManagerId: $request->wasteManagerId,
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
            wasteManagerId: $container->wasteManagerId(),
            qrCode: $container->qrCode()
        );
    }
}