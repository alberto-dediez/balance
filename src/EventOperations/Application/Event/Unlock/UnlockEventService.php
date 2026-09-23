<?php

declare(strict_types=1);

namespace App\EventOperations\Application\Event\Unlock;

use App\EventOperations\Application\Event\EventAuthorizer;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UnlockEventService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private EventRepository $eventRepository,
        private EventAuthorizer $authorizer,
    ) {}

    public function __invoke(UnlockEventRequest $request, User $user): void
    {
        $event = $this->eventRepository->find($request->eventId);

        if (null === $event) {
            throw new \InvalidArgumentException('Event not found');
        }

        if (!$this->authorizer->canDoThat($event, SecurityAction::Edit, $user)
            && !$this->authorizer->canDoThat($event, SecurityAction::Admin, $user)
        ) {
            throw new UnauthorizedActionException($event, SecurityAction::Edit);
        }

        $event->unlock();

        $this->eventRepository->add($event);
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof UnlockEventRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UnlockEventRequest;
    }
}
