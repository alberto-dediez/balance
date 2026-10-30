<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Event\PropertyCarbonFootprint;

use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Revoke\RevokePropertyCarbonFootprintCertificateRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Revoke\RevokePropertyCarbonFootprintCertificateService;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintReopened;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class RevokeCertificateOnPropertyCarbonFootprintReopenedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private RevokePropertyCarbonFootprintCertificateService $revokeCertificate,
        private PropertyCarbonFootprintCertificateRepository $certificateRepository,
        private LoggedUser $loggedUser,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof PropertyCarbonFootprintReopened);

        $certificate = $this->certificateRepository->findCurrentByPropertyCarbonFootprintId($event->propertyCarbonFootprintId());

        if (null === $certificate) {
            return;
        }

        ($this->revokeCertificate)(
            new RevokePropertyCarbonFootprintCertificateRequest($certificate->id()),
            $this->loggedUser->__invoke(),
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof PropertyCarbonFootprintReopened;
    }
}
