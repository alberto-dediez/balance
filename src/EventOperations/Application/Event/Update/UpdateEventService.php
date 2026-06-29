<?php

declare(strict_types=1);

namespace App\EventOperations\Application\Event\Update;

use App\EventOperations\Application\Event\EventAuthorizer;
use App\EventOperations\Domain\Model\Event\DateRange;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateEventService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EventRepository $eventRepository,
        private EventAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateEventRequest $request, User $user): UpdateEventResponse
    {
        $event = $this->eventRepository->find($request->eventId);

        if (null == $event) {
            throw new \InvalidArgumentException('Event not found');
        }

        $this->authorizer->denyUnlessCanDoThat($event, SecurityAction::Edit, $user);

        $setup = new DateRange(
            startDate: $request->setupStartDate,
            endDate: $request->setupEndDate,
        );
        $celebration = new DateRange(
            startDate: $request->celebrationStartDate,
            endDate: $request->celebrationEndDate,
        );
        $dismantling = new DateRange(
            startDate: $request->dismantlingStartDate,
            endDate: $request->dismantlingEndDate,
        );

        $event->update(
            name: $request->name,
            location: $request->location,
            size: $request->size,
            estimatedAttendees: $request->estimatedAttendees,
            sector: $request->sector,
            typology: $request->typology,
            setup: $setup,
            celebration: $celebration,
            dismantling: $dismantling,
        );

        $this->eventRepository->add($event);

        return new UpdateEventResponse(
            eventId: $event->id(),
            name: $event->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateEventRequest;
    }
}
