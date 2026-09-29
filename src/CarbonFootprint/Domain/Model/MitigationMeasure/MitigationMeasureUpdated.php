<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationMeasure;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MitigationMeasureUpdated extends DomainEvent
{
    protected static string $eventName = 'mitigationMeasureUpdated';

    public function __construct(
        private readonly MitigationMeasureId $mitigationMeasureId,
        private readonly MitigationMeasureCategoryId $categoryId,
        private readonly string $name,
        private readonly string $description,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function mitigationMeasureId(): MitigationMeasureId
    {
        return $this->mitigationMeasureId;
    }

    public function categoryId(): MitigationMeasureCategoryId
    {
        return $this->categoryId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->mitigationMeasureId;
    }

    public function payload(): array
    {
        return [
            'mitigationMeasureId' => (string) $this->mitigationMeasureId,
            'categoryId' => (string) $this->categoryId,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            mitigationMeasureId: new MitigationMeasureId($payload['mitigationMeasureId']),
            categoryId: new MitigationMeasureCategoryId($payload['categoryId']),
            name: $payload['name'],
            description: $payload['description'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
