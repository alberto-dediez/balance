<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PropertyCarbonFootprintCertificateRemoved extends DomainEvent
{
    protected static string $eventName = 'propertyCarbonFootprintCertificateRemoved';

    public function __construct(
        private PropertyCarbonFootprintCertificateId $propertyCarbonFootprintCertificateId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function propertyCarbonFootprintCertificateId(): PropertyCarbonFootprintCertificateId
    {
        return $this->propertyCarbonFootprintCertificateId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->propertyCarbonFootprintCertificateId;
    }

    public function payload(): array
    {
        return [
            'propertyCarbonFootprintCertificateId' => (string) $this->propertyCarbonFootprintCertificateId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            propertyCarbonFootprintCertificateId: new PropertyCarbonFootprintCertificateId($payload['propertyCarbonFootprintCertificateId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
