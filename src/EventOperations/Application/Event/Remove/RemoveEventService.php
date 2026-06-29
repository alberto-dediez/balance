<?php

declare(strict_types=1);

namespace App\EventOperations\Application\Event\Remove;

use App\EventOperations\Application\Event\EventAuthorizer;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveEventService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private EventRepository $repository, private EventAuthorizer $authorizer) {}

    public function __invoke(RemoveEventRequest $request, User $user): RemoveEventResponse
    {
        $event = $this->repository->find($request->eventId);

        if (null == $event) {
            throw new \InvalidArgumentException('Event not found');
        }

        $this->authorizer->denyUnlessCanDoThat($event, SecurityAction::Remove, $user);

        $event->remove();

        $this->repository->add($event);

        return new RemoveEventResponse(
            eventId: $event->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveEventRequest;
    }
}
