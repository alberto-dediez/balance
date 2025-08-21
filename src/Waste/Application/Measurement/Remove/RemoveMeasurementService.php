<?php

namespace App\Waste\Application\Measurement\Remove;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Waste\Application\Measurement\MeasurementAuthorizer;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;

class RemoveMeasurementService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

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

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveMeasurementRequest;
    }
}