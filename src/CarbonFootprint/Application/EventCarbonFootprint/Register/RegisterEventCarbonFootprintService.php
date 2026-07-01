<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\Register;

use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintStatus;
use App\EventOperations\Domain\Model\Event\DateRange;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterEventCarbonFootprintService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventCarbonFootprintAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterEventCarbonFootprintRequest $request, User $user): RegisterEventCarbonFootprintResponse
    {
        $id = EventCarbonFootprintId::generate();

        $this->authorizer->denyUnlessCanDoThat(new EventCarbonFootprint(
            id: $id,
            accountId: $request->accountId,
        ), SecurityAction::Register, $user);

        $calculationPeriod = new DateRange(
            startDate: $request->calculationPeriodStartDate,
            endDate: $request->calculationPeriodEndDate,
        );

        $eventCarbonFootprint = new EventCarbonFootprint($id);
        $eventCarbonFootprint->register(
            eventId: $request->eventId,
            accountId: $request->accountId,
            emissionFactorGroupId: $request->emissionFactorGroupId,
            calculationPeriod: $calculationPeriod,
            totalEmissions: new Emissions(),
            emissionsPerAttendee: new Emissions(),
            status: EventCarbonFootprintStatus::Started,
        );

        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        return new RegisterEventCarbonFootprintResponse(
            eventCarbonFootprintId: $eventCarbonFootprint->id(),
            eventId: $request->eventId,
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterEventCarbonFootprintRequest;
    }
}
