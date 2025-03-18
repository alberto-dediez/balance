<?php

namespace App\Waste\Application\Measurement\Register;

use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;

class RegisterMeasurementService
{
    public function __construct(
        private MeasurementRepository $measurementRepository,
        private ContainerRepository $containerRepository
    ) {

    }

    public function __invoke(RegisterMeasurementRequest $request): RegisterMeasurementResponse
    {
        $container = $this->containerRepository->find($request->containerId);

        if (null == $container) {
            throw new \InvalidArgumentException("Debes especificar una contenedor válido");
        }

        $id = MeasurementId::generate();

        $measurement = new Measurement($id);
        $measurement
            ->register(
                container: $container,
                quantity: $request->quantity,
                load: $request->load,
                registeredBy: $request->registeredBy,
                justification: $request->justification
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
}