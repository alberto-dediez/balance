<?php

namespace App\Waste\Application\Measurement\Remove;

use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use InvalidArgumentException;

class RemoveMeasurementService
{
    public function __construct(private MeasurementRepository $repository)
    {

    }

    public function __invoke(RemoveMeasurementRequest $request): RemoveMeasurementResponse
    {
        $measurement = $this->repository->find($request->measurementId);

        if (null == $measurement) {
            throw new InvalidArgumentException("Measurement not found");
        }

        $measurement->remove();

        $this->repository->remove($measurement);

        return new RemoveMeasurementResponse(
            measurementId: $measurement->id()
        );
    }
}