<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Event\AttendeeSurvey;

use App\CarbonFootprint\Application\AttendeeSurvey\Reopen\ReopenAttendeeSurveyRequest;
use App\CarbonFootprint\Application\AttendeeSurvey\Reopen\ReopenAttendeeSurveyService;
use App\EventOperations\Domain\Model\Event\EventUnlocked;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class ReopenAttendeeSurveyOnEventUnlockedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private ReopenAttendeeSurveyService $reopenAttendeeSurvey,
        private LoggedUser $loggedUser,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof EventUnlocked);

        ($this->reopenAttendeeSurvey)(
            new ReopenAttendeeSurveyRequest($event->eventId()),
            $this->loggedUser->__invoke(),
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof EventUnlocked;
    }
}
