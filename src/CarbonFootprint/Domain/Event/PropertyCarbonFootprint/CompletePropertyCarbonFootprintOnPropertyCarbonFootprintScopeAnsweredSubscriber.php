<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Event\PropertyCarbonFootprint;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\Complete\CompletePropertyCarbonFootprintRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\Complete\CompletePropertyCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintScopeAnswered;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class CompletePropertyCarbonFootprintOnPropertyCarbonFootprintScopeAnsweredSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private CompletePropertyCarbonFootprintService $completePropertyCarbonFootprint, private LoggedUser $loggedUser)
    {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof PropertyCarbonFootprintScopeAnswered);

        if (Scope::Scope2 !== $event->scope()) {
            return;
        }

        ($this->completePropertyCarbonFootprint)(new CompletePropertyCarbonFootprintRequest($event->propertyCarbonFootprintId()), $this->loggedUser->__invoke());
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof PropertyCarbonFootprintScopeAnswered;
    }
}
