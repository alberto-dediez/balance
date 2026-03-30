<?php

declare(strict_types=1);

namespace App\Waste\Application\Measurement\Update;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Waste\Application\Measurement\MeasurementAuthorizer;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;

class UpdateMeasurementService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MeasurementRepository $measurementRepository,
        private MeasurementAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateMeasurementRequest $request, User $user): UpdateMeasurementResponse
    {
        $measurement = $this->measurementRepository->find($request->measurementId);

        if (null == $measurement) {
            throw new \InvalidArgumentException('Measurement not found');
        }

        $this->authorizer->denyUnlessCanDoThat($measurement, Action::Edit, $user);

        $measurement->update(
            quantity: $request->quantity,
            load: $request->load,
            justification: $request->justification,
            reference: $request->reference,
        );

        $this->measurementRepository->add($measurement);

        return new UpdateMeasurementResponse(
            measurementId: $measurement->id(),
            quantity: $measurement->quantity(),
            load: $measurement->load(),
            justification: $measurement->justification(),
            reference: $measurement->reference(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateMeasurementRequest;
    }
}
