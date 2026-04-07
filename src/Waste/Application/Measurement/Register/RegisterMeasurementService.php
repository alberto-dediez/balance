<?php

declare(strict_types=1);

namespace App\Waste\Application\Measurement\Register;

use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Waste\Application\Measurement\MeasurementAuthorizer;
use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;

class RegisterMeasurementService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MeasurementRepository $measurementRepository,
        private ContainerRepository $containerRepository,
        private PropertyRepository $propertyRepository,
        private UserRepository $userRepository,
        private MeasurementAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterMeasurementRequest $request, User $user): RegisterMeasurementResponse
    {
        $this->validateRequest($request);

        $container = $this->containerRepository->find($request->containerId);
        assert($container instanceof Container);
        assert($container->propertyId() instanceof PropertyId);

        $property = $this->propertyRepository->find($container->propertyId());
        assert($property instanceof Property);
        assert($property->accountId() instanceof AccountId);

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
            $user,
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
                day: $request->day instanceof \DateTime ? \DateTimeImmutable::createFromMutable($request->day) : $request->day,
            )
        ;

        $this->measurementRepository->add($measurement);

        $containerId = $measurement->containerId();
        assert($containerId instanceof ContainerId);
        $registeredBy = $measurement->registeredBy();
        assert($registeredBy instanceof UserId);

        return new RegisterMeasurementResponse(
            measurementId: $measurement->id(),
            containerId: $containerId,
            quantity: $measurement->quantity(),
            load: $measurement->load(),
            registeredBy: $registeredBy,
            justification: $measurement->justification(),
            reference: $measurement->reference(),
        );
    }

    private function validateRequest(RegisterMeasurementRequest $request): void
    {
        $container = $this->containerRepository->find($request->containerId);

        if (null == $container) {
            throw new \InvalidArgumentException('Debes especificar un contenedor válido');
        }

        $containerPropertyId = $container->propertyId();
        assert($containerPropertyId instanceof PropertyId);
        $property = $this->propertyRepository->find($containerPropertyId);

        if (null == $property) {
            throw new \InvalidArgumentException('Debes especificar una propiedad válida');
        }

        $user = $this->userRepository->find($request->registeredBy);

        $userAccountId = $user?->accountId();
        $propertyAccountId = $property->accountId();
        if (null == $user || !$userAccountId instanceof AccountId || !$propertyAccountId instanceof AccountId || false === $userAccountId->equals($propertyAccountId)) {
            throw new \InvalidArgumentException('El usuario que registra la medición no es válido');
        }
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterMeasurementRequest;
    }
}
