<?php

namespace App\Waste\Application\Measurement\Remove;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Waste\Application\Measurement\MeasurementAuthorizer;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;

class RemoveMeasurementService
{
    public function __construct(private MeasurementRepository $repository, private MeasurementAuthorizer $authorizer)
    {

    }

    public function __invoke(RemoveMeasurementRequest $request, User $user): RemoveMeasurementResponse
    {
        $measurement = $this->repository->find($request->measurementId);

        if (null == $measurement) {
            throw new InvalidArgumentException("Measurement not found");
        }

        $this->authorizer->denyUnlessCanDoThat($measurement, Action::Remove, $user);

        $measurement->remove();

        $this->repository->remove($measurement);

        return new RemoveMeasurementResponse(
            measurementId: $measurement->id()
        );
    }
}