<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Surplus;

use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\FoodLoss\Domain\Model\DeliveryLog\FoodType;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class SurplusRegistered extends DomainEvent
{
    protected static string $eventName = 'surplusRegistered';

    public function __construct(
        private readonly SurplusId $surplusId,
        private readonly \DateTimeImmutable $date,
        private readonly ServiceCategoryId $serviceCategoryId,
        private readonly CriticalPointId $criticalPointId,
        private readonly SurplusCauseId $surplusCauseId,
        private readonly FoodType $foodType,
        private readonly float $quantity,
        private readonly DeliveryLogUnit $unit,
        private readonly AccountId $accountId,
        private readonly UserId $registeredBy,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function surplusId(): SurplusId
    {
        return $this->surplusId;
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

    public function aggregateRootId(): AggregateRootId
    {
        return $this->surplusId;
    }

    public function payload(): array
    {
        return [
            'surplusId' => (string) $this->surplusId,
            'date' => $this->date->format(\DateTimeInterface::ATOM),
            'serviceCategoryId' => (string) $this->serviceCategoryId,
            'criticalPointId' => (string) $this->criticalPointId,
            'surplusCauseId' => (string) $this->surplusCauseId,
            'foodType' => $this->foodType->value,
            'quantity' => $this->quantity,
            'unit' => $this->unit->value,
            'accountId' => (string) $this->accountId,
            'registeredBy' => (string) $this->registeredBy,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new SurplusRegistered(
            surplusId: new SurplusId($payload['surplusId']),
            date: new \DateTimeImmutable($payload['date']),
            serviceCategoryId: new ServiceCategoryId($payload['serviceCategoryId']),
            criticalPointId: new CriticalPointId($payload['criticalPointId']),
            surplusCauseId: new SurplusCauseId($payload['surplusCauseId']),
            foodType: FoodType::from($payload['foodType']),
            quantity: (float) $payload['quantity'],
            unit: DeliveryLogUnit::from($payload['unit']),
            accountId: new AccountId($payload['accountId']),
            registeredBy: new UserId($payload['registeredBy']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
