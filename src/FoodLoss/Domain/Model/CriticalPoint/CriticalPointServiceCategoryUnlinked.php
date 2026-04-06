<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CriticalPointServiceCategoryUnlinked extends DomainEvent
{
    protected static string $eventName = 'criticalPointServiceCategoryUnlinked';

    public function __construct(
        private readonly CriticalPointId $criticalPointId,
        private readonly ServiceCategoryId $serviceCategoryId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function criticalPointId(): CriticalPointId
    {
        return $this->criticalPointId;
    }

    public function serviceCategoryId(): ServiceCategoryId
    {
        return $this->serviceCategoryId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->criticalPointId;
    }

    public function payload(): array
    {
        return [
            'criticalPointId' => (string) $this->criticalPointId,
            'serviceCategoryId' => (string) $this->serviceCategoryId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new CriticalPointServiceCategoryUnlinked(
            criticalPointId: new CriticalPointId($payload['criticalPointId']),
            serviceCategoryId: new ServiceCategoryId($payload['serviceCategoryId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
