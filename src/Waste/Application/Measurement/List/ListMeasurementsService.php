<?php

namespace App\Waste\Application\Measurement\List;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Application\Measurement\MeasurementAuthorizer;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;

class ListMeasurementsService
{
    public function __construct(private readonly MeasurementRepository $repository, private readonly MeasurementAuthorizer $authorizer)
    {

    }

    public function __invoke(ListMeasurementsRequest $request, User $user): ListMeasurementsResponse
    {
        $measurement = new Measurement(
            id: MeasurementId::generate(),
            containerId: $request->containerId,
            propertyId: $request->propertyId,
            wasteId: $request->wasteId,
            purpose: $request->purpose,
            measurementMethod: $request->measurementMethod,
            accountId: $request->accountId,
            registeredBy: $request->userId,
        );

        $this->authorizer->denyUnlessCanDoThat($measurement, Action::List, $user);

        $measurements = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListMeasurementsResponse($measurements, $totalResults);
    }
}