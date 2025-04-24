<?php

namespace App\Waste\Application\Measurement\Update;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Waste\Application\Measurement\MeasurementAuthorizer;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;

class UpdateMeasurementService
{
    public function __construct(
        private MeasurementRepository $measurementRepository,
        private MeasurementAuthorizer $authorizer
    ) {

    }

    public function __invoke(UpdateMeasurementRequest $request, User $user): UpdateMeasurementResponse
    {
        $measurement = $this->measurementRepository->find($request->measurementId);
        
        if (null == $measurement) {
            throw new InvalidArgumentException("Measurement not found");
        }

        $this->authorizer->denyUnlessCanDoThat($measurement, Action::Edit, $user);

        $measurement->update(
            quantity: $request->quantity,
            load: $request->load,
            justification: $request->justification
        );

        $this->measurementRepository->add($measurement);

        return new UpdateMeasurementResponse(
            measurementId: $measurement->id(),
            quantity: $measurement->quantity(),
            load: $measurement->load(),
            justification: $measurement->justification()
        );
    }
}