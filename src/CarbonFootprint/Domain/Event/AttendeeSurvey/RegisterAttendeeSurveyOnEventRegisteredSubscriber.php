<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Event\AttendeeSurvey;

use App\CarbonFootprint\Application\AttendeeSurvey\Register\RegisterAttendeeSurveyRequest;
use App\CarbonFootprint\Application\AttendeeSurvey\Register\RegisterAttendeeSurveyService;
use App\EventOperations\Domain\Model\Event\EventRegistered;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class RegisterAttendeeSurveyOnEventRegisteredSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private RegisterAttendeeSurveyService $registerAttendeeSurvey,
        private LoggedUser $loggedUser,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof EventRegistered);

        $user = $this->loggedUser->getLoggedUserOrNull();

        if (null === $user) {
            return;
        }

        ($this->registerAttendeeSurvey)(
            new RegisterAttendeeSurveyRequest($event->eventId()),
            $user,
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof EventRegistered;
    }
}
