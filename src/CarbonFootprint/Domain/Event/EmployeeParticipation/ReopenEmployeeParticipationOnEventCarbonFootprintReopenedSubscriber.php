<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Event\EmployeeParticipation;

use App\CarbonFootprint\Application\EmployeeParticipation\Reopen\ReopenEmployeeParticipationRequest;
use App\CarbonFootprint\Application\EmployeeParticipation\Reopen\ReopenEmployeeParticipationService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintReopened;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class ReopenEmployeeParticipationOnEventCarbonFootprintReopenedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private ReopenEmployeeParticipationService $reopenEmployeeParticipation,
        private LoggedUser $loggedUser,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof EventCarbonFootprintReopened);

        ($this->reopenEmployeeParticipation)(
            new ReopenEmployeeParticipationRequest($event->eventCarbonFootprintId()),
            $this->loggedUser->__invoke(),
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof EventCarbonFootprintReopened;
    }
}
