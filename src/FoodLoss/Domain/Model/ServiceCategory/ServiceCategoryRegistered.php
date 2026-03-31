<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\ServiceCategory;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ServiceCategoryRegistered extends DomainEvent
{
    protected static string $eventName = 'serviceCategoryRegistered';

    public function __construct(
        private ServiceCategoryId $serviceCategoryId,
        private string $name,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function serviceCategoryId(): ServiceCategoryId
    {
        return $this->serviceCategoryId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->serviceCategoryId();
    }

    public function payload(): array
    {
        return [
            'serviceCategoryId' => (string) $this->serviceCategoryId,
            'name' => $this->name,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ServiceCategoryRegistered(
            serviceCategoryId: new ServiceCategoryId($payload['serviceCategoryId']),
            name: $payload['name'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
