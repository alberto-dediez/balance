<?php

declare(strict_types=1);

namespace App\Property\Domain\Event\Company;

use App\Property\Domain\Model\Company\CompanyRemoved;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

final readonly class UnlinkCompanyOnCompanyRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private PropertyRepository $propertyRepository)
    {
        $this->register();
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof CompanyRemoved;
    }

    public function handle(DomainEvent $event): void
    {
        \assert($event instanceof CompanyRemoved);

        $properties = $this->propertyRepository->findByCompanyId($event->companyId());

        foreach ($properties as $property) {
            $property->update(
                name: $property->name(),
                accountId: $property->accountId(),
                info: $property->info(),
                externalId: $property->externalId(),
                companyId: null,
                numberOfEmployees: $property->numberOfEmployees(),
                dailyClients: $property->dailyClients(),
                operatingRegime: $property->operatingRegime(),
            );

            $this->propertyRepository->add($property);
        }
    }
}
