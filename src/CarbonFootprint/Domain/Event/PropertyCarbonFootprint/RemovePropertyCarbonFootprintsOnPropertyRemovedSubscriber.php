<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Event\PropertyCarbonFootprint;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\Remove\RemovePropertyCarbonFootprintRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\Remove\RemovePropertyCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Property\PropertyRemoved;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class RemovePropertyCarbonFootprintsOnPropertyRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        private RemovePropertyCarbonFootprintService $removePropertyCarbonFootprint,
        private LoggedUser $loggedUser,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof PropertyRemoved);

        foreach ($this->propertyCarbonFootprintRepository->findByPropertyId($event->propertyId()) as $propertyCarbonFootprint) {
            $request = new RemovePropertyCarbonFootprintRequest($propertyCarbonFootprint->id());

            ($this->removePropertyCarbonFootprint)($request, $this->loggedUser->__invoke());
        }
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof PropertyRemoved;
    }
}
