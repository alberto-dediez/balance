<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Event\EventCarbonFootprint;

use App\CarbonFootprint\Application\EventCarbonFootprint\Complete\CompleteEventCarbonFootprintRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\Complete\CompleteEventCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintAttendeesAnswered;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class CompleteEventCarbonFootprintOnEventCarbonFootprintAttendeesAnsweredSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private CompleteEventCarbonFootprintService $completeEventCarbonFootprint, private LoggedUser $loggedUser)
    {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof EventCarbonFootprintAttendeesAnswered);

        $request = new CompleteEventCarbonFootprintRequest($event->eventCarbonFootprintId());

        ($this->completeEventCarbonFootprint)($request, $this->loggedUser->__invoke());
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof EventCarbonFootprintAttendeesAnswered;
    }
}
