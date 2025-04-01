<?php

namespace App\Security\Domain\Event;

use App\Security\Application\RecordedEvent\Register\RegisterRecordedEventRequest;
use App\Security\Application\RecordedEvent\Register\RegisterRecordedEventService;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\RegisterTrait;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;

class RecordEventSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private LoggedUser $loggedUser, private RegisterRecordedEventService $registerRecordedEvent)
    {
        $this->register();
    }

    public function handle(DomainEvent $event)
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException("Incorrect event!");
        }

        $loggedUser = $this->loggedUser->__invoke();
        $registerRecordedEvent = $this->registerRecordedEvent;

        $registerRecordedEvent(
            new RegisterRecordedEventRequest(
                userId: null !== $loggedUser ? $loggedUser->id() : null,
                event: $event
            ),
            $loggedUser
        );
    }

    public function isSubscribedTo(DomainEvent $event)
    {
        return true;
    }
}
