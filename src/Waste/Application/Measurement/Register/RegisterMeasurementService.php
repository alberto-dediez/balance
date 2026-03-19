<?php

namespace App\Waste\Application\Measurement\Register;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Security\Domain\Model\User\UserRepository;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Waste\Application\Measurement\MeasurementAuthorizer;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;

class RegisterMeasurementService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

    public function __construct(
        private MeasurementRepository $measurementRepository,
        private ContainerRepository $containerRepository,
        private PropertyRepository $propertyRepository,
        private UserRepository $userRepository,
        private MeasurementAuthorizer $authorizer
    ) {

    }

    public function __invoke(RegisterMeasurementRequest $request, User $user): RegisterMeasurementResponse
    {
        $this->validateRequest($request);

        $container = $this->containerRepository->find($request->containerId);

        $property = $this->propertyRepository->find($container->propertyId());

        $id = MeasurementId::generate();

        $this->authorizer->denyUnlessCanDoThat(
            new Measurement(
                id: $id,
                containerId: $container->id(),
                propertyId: $property->id(),
                accountId: $property->accountId(),
                registeredBy: $user->id(),
            ),
            Action::Register,
            $user
        );

        $measurement = new Measurement($id);
        $measurement
            ->register(
                container: $container,
                quantity: $request->quantity,
                load: $request->load,
                registeredBy: $request->registeredBy,
                accountId: $property->accountId(),
                justification: $request->justification,
                reference: $request->reference,
                day: $request->day,
            )
        ;

        $this->measurementRepository->add($measurement);

        return new RegisterMeasurementResponse(
            measurementId: $measurement->id(),
            containerId: $measurement->containerId(),
            quantity: $measurement->quantity(),
            load: $measurement->load(),
            registeredBy: $measurement->registeredBy(),
            justification: $measurement->justification(),
            reference: $measurement->reference(),
        );
    }

    private function validateRequest(RegisterMeasurementRequest $request): void
    {
        $container = $this->containerRepository->find($request->containerId);

        if (null == $container) {
            throw new \InvalidArgumentException("Debes especificar un contenedor válido");
        }

        $property = $this->propertyRepository->find($container->propertyId());

        if (null == $property) {
            throw new \InvalidArgumentException("Debes especificar una propiedad válida");
        }

        $user = $this->userRepository->find($request->registeredBy);

        if (null == $user || null == $user->accountId() || false === $user->accountId()->equals($property->accountId())) {
            throw new \InvalidArgumentException("El usuario que registra la medición no es válido");
        }
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterMeasurementRequest;
    }
}
