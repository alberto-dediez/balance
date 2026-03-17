<?php

namespace App\FoodLoss\Domain\Model\ServiceCategory;

use DateTimeImmutable;
use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ServiceCategoryRemoved extends DomainEvent
{
    protected static string $eventName = 'serviceCategoryRemoved';

    public function __construct(
        private ServiceCategoryId $serviceCategoryId, 
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function serviceCategoryId(): ServiceCategoryId
    {
        return $this->serviceCategoryId;
    }

    public function aggregateRootId(): ?AggregateRootId
    {
        return $this->serviceCategoryId();
    }

    public function payload(): ?array
    {
        return [
            'serviceCategoryId' => (string) $this->serviceCategoryId
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new ServiceCategoryRemoved(
            serviceCategoryId: new ServiceCategoryId($payload['serviceCategoryId']),
            id: $id,
            occurredOn: $occurredOn
        );
    }
}