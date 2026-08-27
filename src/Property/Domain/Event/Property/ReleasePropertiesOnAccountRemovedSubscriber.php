<?php

declare(strict_types=1);

namespace App\Property\Domain\Event\Property;

use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\Account\AccountRemoved;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class ReleasePropertiesOnAccountRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private PropertyRepository $propertyRepository,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof AccountRemoved);
        $this->handleAccountRemoved($event);
    }

    private function handleAccountRemoved(AccountRemoved $event): void
    {
        $properties = $this->propertyRepository->findByFilter(
            new PropertyFilter(accountId: $event->accountId(), limit: 0),
        );

        foreach ($properties as $property) {
            $property->releaseAccount();
            $this->propertyRepository->add($property);
        }
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof AccountRemoved;
    }
}
