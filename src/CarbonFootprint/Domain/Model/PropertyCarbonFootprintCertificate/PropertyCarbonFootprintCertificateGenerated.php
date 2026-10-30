<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PropertyCarbonFootprintCertificateGenerated extends DomainEvent
{
    protected static string $eventName = 'propertyCarbonFootprintCertificateGenerated';

    /** @param array<string, string> $files */
    public function __construct(
        private PropertyCarbonFootprintCertificateId $propertyCarbonFootprintCertificateId,
        private array $files,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function propertyCarbonFootprintCertificateId(): PropertyCarbonFootprintCertificateId
    {
        return $this->propertyCarbonFootprintCertificateId;
    }

    /** @return array<string, string> */
    public function files(): array
    {
        return $this->files;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->propertyCarbonFootprintCertificateId;
    }

    public function payload(): array
    {
        return [
            'propertyCarbonFootprintCertificateId' => (string) $this->propertyCarbonFootprintCertificateId,
            'files' => $this->files,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        /** @var array<string, string> $files */
        $files = $payload['files'] ?? [];

        return new self(
            propertyCarbonFootprintCertificateId: new PropertyCarbonFootprintCertificateId($payload['propertyCarbonFootprintCertificateId']),
            files: $files,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
