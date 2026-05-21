<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Surplus;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;

class Surplus implements \Stringable
{
    use AggregateRoot;

    /** @param array<DomainEvent> $events */
    public function __construct(
        private SurplusId $id,
        private \DateTimeImmutable $date = new \DateTimeImmutable('NOW'),
        private ServiceCategoryId $serviceCategoryId = new ServiceCategoryId('00000000-0000-0000-0000-000000000000'),
        private CriticalPointId $criticalPointId = new CriticalPointId('00000000-0000-0000-0000-000000000000'),
        private SurplusCauseId $surplusCauseId = new SurplusCauseId('00000000-0000-0000-0000-000000000000'),
        private FoodType $foodType = FoodType::Processed,
        private float $quantity = 0.0,
        private DeliveryLogUnit $unit = DeliveryLogUnit::Kg,
        private AccountId $accountId = new AccountId('00000000-0000-0000-0000-000000000000'),
        private UserId $registeredBy = new UserId('00000000-0000-0000-0000-000000000000'),
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): SurplusId
    {
        return $this->id;
    }

    public function date(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function serviceCategoryId(): ServiceCategoryId
    {
        return $this->serviceCategoryId;
    }

    public function criticalPointId(): CriticalPointId
    {
        return $this->criticalPointId;
    }

    public function surplusCauseId(): SurplusCauseId
    {
        return $this->surplusCauseId;
    }

    public function foodType(): FoodType
    {
        return $this->foodType;
    }

    public function quantity(): float
    {
        return $this->quantity;
    }

    public function unit(): DeliveryLogUnit
    {
        return $this->unit;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function registeredBy(): UserId
    {
        return $this->registeredBy;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function register(
        \DateTimeImmutable $date,
        ServiceCategoryId $serviceCategoryId,
        CriticalPointId $criticalPointId,
        SurplusCauseId $surplusCauseId,
        FoodType $foodType,
        float $quantity,
        DeliveryLogUnit $unit,
        AccountId $accountId,
        UserId $registeredBy,
    ): void {
        $this->recordApplyAndPublish(
            new SurplusRegistered(
                surplusId: $this->id(),
                date: $date,
                serviceCategoryId: $serviceCategoryId,
                criticalPointId: $criticalPointId,
                surplusCauseId: $surplusCauseId,
                foodType: $foodType,
                quantity: $quantity,
                unit: $unit,
                accountId: $accountId,
                registeredBy: $registeredBy,
            ),
        );
    }

    public function update(
        \DateTimeImmutable $date,
        ServiceCategoryId $serviceCategoryId,
        CriticalPointId $criticalPointId,
        SurplusCauseId $surplusCauseId,
        FoodType $foodType,
        float $quantity,
        DeliveryLogUnit $unit,
    ): void {
        $this->recordApplyAndPublish(
            new SurplusUpdated(
                surplusId: $this->id(),
                date: $date,
                serviceCategoryId: $serviceCategoryId,
                criticalPointId: $criticalPointId,
                surplusCauseId: $surplusCauseId,
                foodType: $foodType,
                quantity: $quantity,
                unit: $unit,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new SurplusRemoved(
                surplusId: $this->id(),
            ),
        );
    }

    public function applySurplusRegistered(SurplusRegistered $event): void
    {
        $this->date = $event->date();
        $this->serviceCategoryId = $event->serviceCategoryId();
        $this->criticalPointId = $event->criticalPointId();
        $this->surplusCauseId = $event->surplusCauseId();
        $this->foodType = $event->foodType();
        $this->quantity = $event->quantity();
        $this->unit = $event->unit();
        $this->accountId = $event->accountId();
        $this->registeredBy = $event->registeredBy();
        $this->registeredAt = $event->occurredOn();
    }

    public function applySurplusUpdated(SurplusUpdated $event): void
    {
        $this->date = $event->date();
        $this->serviceCategoryId = $event->serviceCategoryId();
        $this->criticalPointId = $event->criticalPointId();
        $this->surplusCauseId = $event->surplusCauseId();
        $this->foodType = $event->foodType();
        $this->quantity = $event->quantity();
        $this->unit = $event->unit();
        $this->updatedAt = $event->occurredOn();
    }

    public function applySurplusRemoved(SurplusRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return sprintf('%s - %s', $this->date->format('Y-m-d'), $this->foodType->value);
    }
}
