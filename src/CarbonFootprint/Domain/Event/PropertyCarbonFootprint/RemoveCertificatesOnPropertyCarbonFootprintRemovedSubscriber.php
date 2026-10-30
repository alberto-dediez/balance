<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Event\PropertyCarbonFootprint;

use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Remove\RemovePropertyCarbonFootprintCertificatesRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Remove\RemovePropertyCarbonFootprintCertificatesService;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRemoved;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepository;
use App\Security\Application\User\SystemActor\SystemActorResolver;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class RemoveCertificatesOnPropertyCarbonFootprintRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private RemovePropertyCarbonFootprintCertificatesService $removeCertificates,
        private PropertyCarbonFootprintCertificateRepository $certificateRepository,
        private SystemActorResolver $systemActorResolver,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof PropertyCarbonFootprintRemoved);

        if ([] === $this->certificateRepository->findByPropertyCarbonFootprintId($event->propertyCarbonFootprintId())) {
            return;
        }

        ($this->removeCertificates)(
            new RemovePropertyCarbonFootprintCertificatesRequest($event->propertyCarbonFootprintId()),
            $this->systemActorResolver->__invoke(),
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof PropertyCarbonFootprintRemoved;
    }
}
