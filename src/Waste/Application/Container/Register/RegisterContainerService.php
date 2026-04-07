<?php

declare(strict_types=1);

namespace App\Waste\Application\Container\Register;

use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Waste\Application\Container\ContainerAuthorizer;
use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Waste\Domain\Model\Container\QrCode;
use App\Waste\Domain\Model\Container\QrCodeGenerator;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

class RegisterContainerService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ContainerRepository $containerRepository,
        private PropertyRepository $propertyRepository,
        private QrCodeGenerator $qrCodeGenerator,
        private ContainerAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterContainerRequest $request, User $user): RegisterContainerResponse
    {
        if (null == $request->propertyId || null == $request->wasteId) {
            throw new \InvalidArgumentException('Debes especificar una propiedad y tipologia de resiudo');
        }

        $property = $this->propertyRepository->find($request->propertyId);

        if (null == $property) {
            throw new \InvalidArgumentException('Debes especificar una propiedad válida');
        }

        $id = ContainerId::generate();

        $this->authorizer->denyUnlessCanDoThat(
            new Container(
                id: $id,
                accountId: $property->accountId(),
                propertyId: $request->propertyId,
            ),
            Action::Register,
            $user,
        );

        assert($property->accountId() instanceof AccountId);
        $container = new Container($id);
        $container
            ->register(
                accountId: $property->accountId(),
                propertyId: $request->propertyId,
                wasteId: $request->wasteId,
                purpose: $request->purpose,
                measurementMethod: $request->measurementMethod,
                volume: $request->volume,
                wasteManagerId: $request->wasteManagerId,
                qrCode: $this->qrCodeGenerator->generate($id),
            )
        ;

        $this->containerRepository->add($container);

        assert($container->propertyId() instanceof PropertyId);
        assert($container->wasteId() instanceof WasteId);
        assert($container->wasteManagerId() instanceof WasteManagerId);
        assert($container->qrCode() instanceof QrCode);

        return new RegisterContainerResponse(
            containerId: $container->id(),
            propertyId: $container->propertyId(),
            wasteId: $container->wasteId(),
            purpose: $container->purpose(),
            measurementMethod: $container->measurementMethod(),
            volume: $container->volume(),
            wasteManagerId: $container->wasteManagerId(),
            qrCode: $container->qrCode(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterContainerRequest;
    }
}
