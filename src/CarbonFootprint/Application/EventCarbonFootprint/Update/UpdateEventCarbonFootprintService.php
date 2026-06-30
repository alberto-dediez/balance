<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\Update;

use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\EventOperations\Domain\Model\Event\DateRange;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateEventCarbonFootprintService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventCarbonFootprintAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateEventCarbonFootprintRequest $request, User $user): UpdateEventCarbonFootprintResponse
    {
        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($request->eventCarbonFootprintId);

        if (null == $eventCarbonFootprint) {
            throw new \InvalidArgumentException('EventCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($eventCarbonFootprint, SecurityAction::Edit, $user);

        $calculationPeriod = new DateRange(
            startDate: $request->calculationPeriodStartDate,
            endDate: $request->calculationPeriodEndDate,
        );

        $eventCarbonFootprint->update(
            calculationPeriod: $calculationPeriod,
            totalEmissions: $request->totalEmissions,
            emissionsPerAttendee: $request->emissionsPerAttendee,
            status: $request->status,
        );

        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        return new UpdateEventCarbonFootprintResponse(
            eventCarbonFootprintId: $eventCarbonFootprint->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateEventCarbonFootprintRequest;
    }
}
