<?php

namespace App\Waste\Application\Measurement\List;

use App\Waste\Domain\Model\Measurement\MeasurementRepository;

class ListMeasurementsService
{
    public function __construct(private MeasurementRepository $repository)
    {

    }

    public function __invoke(ListMeasurementsRequest $request): ListMeasurementsResponse
    {
        $measurements = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListMeasurementsResponse($measurements, $totalResults);
    }
}