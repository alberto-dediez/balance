<?php

declare(strict_types=1);

namespace App\Security\Domain\Event;

use App\Security\Application\RecordedEvent\Register\RegisterRecordedEventRequest;
use App\Security\Application\RecordedEvent\Register\RegisterRecordedEventService;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;

class RecordEventSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private LoggedUser $loggedUser, private RegisterRecordedEventService $registerRecordedEvent)
    {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        $loggedUser = $this->loggedUser->getLoggedUserOrNull();
        $impersonator = $this->loggedUser->getImpersonatorOrNull();
        $registerRecordedEvent = $this->registerRecordedEvent;

        $registerRecordedEvent(
            new RegisterRecordedEventRequest(
                userId: $loggedUser instanceof User ? $loggedUser->id() : UserMother::systemUser()->id(),
                event: $event,
                impersonatorId: $impersonator instanceof User ? $impersonator->id() : null,
            ),
            $loggedUser,
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return true;
    }
}
