<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityVerdict;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PropertyCarbonFootprintMaterialityOverridden extends DomainEvent
{
    protected static string $eventName = 'propertyCarbonFootprintMaterialityOverridden';

    public function __construct(
        private PropertyCarbonFootprintId $propertyCarbonFootprintId,
        private Source $source,
        private MaterialityVerdict $verdict,
        private UserId $userId,
        private string $justification,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function propertyCarbonFootprintId(): PropertyCarbonFootprintId
    {
        return $this->propertyCarbonFootprintId;
    }

    public function source(): Source
    {
        return $this->source;
    }

    public function verdict(): MaterialityVerdict
    {
        return $this->verdict;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function justification(): string
    {
        return $this->justification;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->propertyCarbonFootprintId;
    }

    public function payload(): array
    {
        return [
            'propertyCarbonFootprintId' => (string) $this->propertyCarbonFootprintId,
            'source' => $this->source->value,
            'verdict' => $this->verdict->value,
            'userId' => (string) $this->userId,
            'justification' => $this->justification,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            propertyCarbonFootprintId: new PropertyCarbonFootprintId($payload['propertyCarbonFootprintId']),
            source: Source::from($payload['source']),
            verdict: MaterialityVerdict::from($payload['verdict']),
            userId: new UserId($payload['userId']),
            justification: $payload['justification'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
