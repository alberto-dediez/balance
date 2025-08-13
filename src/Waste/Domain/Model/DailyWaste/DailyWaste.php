<?php

namespace App\Waste\Domain\Model\DailyWaste;

use App\Waste\Domain\Model\Waste\WasteId;
use App\Shared\Domain\Model\AggregateRoot;
use App\Waste\Domain\Model\Container\Purpose;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\DailyWaste\DailyWasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

class DailyWaste
{
    use AggregateRoot;

    private array $events = [];

    public function __construct(
        private DailyWasteId $id, 
        private PropertyId|null $propertyId = null,
        private WasteId|null $wasteId = null,
        private ?Purpose $purpose = null,
        private WasteManagerId|null $wasteManagerId = null,
        private float $load = 0,
        private AccountId|null $accountId = null,
        private ?\DateTimeImmutable $day = null,
        array $events = []
    ) {

    }

    public function id(): DailyWasteId
    {
        return $this->id;
    }

    public function propertyId() : ?PropertyId
    {
        return $this->propertyId;
    }

    public function wasteId() : ?WasteId
    {
        return $this->wasteId;
    }

    public function purpose() : ?Purpose
    {
        return $this->purpose;
    }

    public function wasteManagerId() : ?WasteManagerId
    {
        return $this->wasteManagerId;
    }

    public function load(): float
    {
        return $this->load;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function day(): \DateTimeImmutable
    {
        return $this->day;
    }

    public function register(
        PropertyId $propertyId,
        WasteId $wasteId,
        Purpose $purpose,
        WasteManagerId $wasteManagerId,
        \DateTimeImmutable $day,
        float $load,
        AccountId $accountId
    ) {
        $this->recordApplyAndPublish(
            new DailyWasteRegistered(
                dailyWasteId: $this->id(),
                propertyId: $propertyId,
                wasteId: $wasteId,
                purpose: $purpose,
                wasteManagerId: $wasteManagerId,
                load: $load,
                accountId: $accountId,
                day: $day
            )
        );
    }

    public function update(
        float $load
    ) {
        $this->recordApplyAndPublish(
            new DailyWasteUpdated(
                dailyWasteId: $this->id(),
                load: $load,
            )
        );
    }

    public function applyDailyWasteRegistered(DailyWasteRegistered $event)
    {
        $this->propertyId = $event->propertyId();
        $this->wasteId = $event->wasteId();
        $this->purpose = $event->purpose();
        $this->wasteManagerId = $event->wasteManagerId();
        $this->load = $event->load();
        $this->accountId = $event->accountId();
        $this->day = $event->day();
    }

    public function applyDailyWasteUpdated(DailyWasteUpdated $event)
    {
        $this->load = $event->load();
    }

    public function __toString(): string
    {
        return (string) $this->id();
    }
}