<?php

namespace App\Waste\Application\Measurement\Register;

use App\Security\Domain\Model\User\UserRepository;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\Property\PropertyRepository;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;

class RegisterMeasurementService
{
    public function __construct(
        private MeasurementRepository $measurementRepository,
        private ContainerRepository $containerRepository,
        private PropertyRepository $propertyRepository,
        private UserRepository $userRepository
    ) {

    }

    public function __invoke(RegisterMeasurementRequest $request): RegisterMeasurementResponse
    {
        $this->validateRequest($request);

        $container = $this->containerRepository->find($request->containerId);

        $property = $this->propertyRepository->find($container->propertyId());

        $id = MeasurementId::generate();

        $measurement = new Measurement($id);
        $measurement
            ->register(
                container: $container,
                quantity: $request->quantity,
                load: $request->load,
                registeredBy: $request->registeredBy,
                justification: $request->justification,
                accountId: $property->accountId()
            )
        ;

        $this->measurementRepository->add($measurement);

        return new RegisterMeasurementResponse(
            measurementId: $measurement->id(),
            containerId: $measurement->containerId(),
            quantity: $measurement->quantity(),
            load: $measurement->load(),
            registeredBy: $measurement->registeredBy(),
            justification: $measurement->justification()
        );
    }

    private function validateRequest(RegisterMeasurementRequest $request)
    {
        $container = $this->containerRepository->find($request->containerId);

        if (null == $container) {
            throw new \InvalidArgumentException("Debes especificar una contenedor válido");
        }

        $property = $this->propertyRepository->find($container->propertyId());

        if (null == $property) {
            throw new \InvalidArgumentException("Debes especificar una contenedor válido");
        }

        $user = $this->userRepository->find($request->registeredBy);

        if (null == $user || null == $user->accountId() || false == $user->accountId()->equals($property->accountId())) {
            throw new \InvalidArgumentException("El usuario que registra la medición no es válido");
        }
    }
}