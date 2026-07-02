<?php

declare(strict_types=1);

namespace App\EventOperations\Application\Event\Register;

use App\EventOperations\Application\Event\EventAuthorizer;
use App\EventOperations\Domain\Model\Event\DateRange;
use App\EventOperations\Domain\Model\Event\Event;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterEventService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EventRepository $eventRepository,
        private EventAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterEventRequest $request, User $user): RegisterEventResponse
    {
        $id = EventId::generate();

        $this->authorizer->denyUnlessCanDoThat(new Event(
            id: $id,
            accountId: $request->accountId,
        ), SecurityAction::Register, $user);

        $assembly = new DateRange(
            startDate: $request->assemblyStartDate,
            endDate: $request->assemblyEndDate,
        );
        $celebration = new DateRange(
            startDate: $request->celebrationStartDate,
            endDate: $request->celebrationEndDate,
        );
        $breakdown = new DateRange(
            startDate: $request->breakdownStartDate,
            endDate: $request->breakdownEndDate,
        );

        $event = new Event($id);
        $event->register(
            name: $request->name,
            location: $request->location,
            size: $request->size,
            estimatedAttendees: $request->estimatedAttendees,
            sector: $request->sector,
            typology: $request->typology,
            assembly: $assembly,
            celebration: $celebration,
            breakdown: $breakdown,
            accountId: $request->accountId,
        );

        $this->eventRepository->add($event);

        return new RegisterEventResponse(
            eventId: $event->id(),
            name: $event->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterEventRequest;
    }
}
