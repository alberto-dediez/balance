<?php

declare(strict_types=1);

namespace App\EventOperations\Application\Event\Lock;

use App\EventOperations\Application\Event\EventAuthorizer;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class LockEventService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private EventRepository $eventRepository,
        private EventAuthorizer $authorizer,
    ) {}

    public function __invoke(LockEventRequest $request, User $user): void
    {
        $event = $this->eventRepository->find($request->eventId);

        if (null === $event) {
            throw new \InvalidArgumentException('Event not found');
        }

        $this->authorizer->denyUnlessCanDoThat($event, SecurityAction::Edit, $user);

        $event->lock();

        $this->eventRepository->add($event);
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof LockEventRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof LockEventRequest;
    }
}
