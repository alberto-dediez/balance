<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\DailyWaste;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

class DailyWaste implements \Stringable
{
    use AggregateRoot;

    public function __construct(
        private DailyWasteId $id,
        private ?PropertyId $propertyId = null,
        private ?WasteId $wasteId = null,
        private Purpose $purpose = Purpose::Waste,
        private ?WasteManagerId $wasteManagerId = null,
        private float|string $load = 0,
        private ?AccountId $accountId = null,
        private \DateTimeImmutable $day = new \DateTimeImmutable(),
    ) {}

    public function id(): DailyWasteId
    {
        return $this->id;
    }

    public function propertyId(): ?PropertyId
    {
        return $this->propertyId;
    }

    public function wasteId(): ?WasteId
    {
        return $this->wasteId;
    }

    public function purpose(): Purpose
    {
        return $this->purpose;
    }

    public function wasteManagerId(): ?WasteManagerId
    {
        return $this->wasteManagerId;
    }

    public function load(): float
    {
        return (float) $this->load;
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
        AccountId $accountId,
    ): void {
        $this->recordApplyAndPublish(
            new DailyWasteRegistered(
                dailyWasteId: $this->id(),
                propertyId: $propertyId,
                wasteId: $wasteId,
                purpose: $purpose,
                wasteManagerId: $wasteManagerId,
                accountId: $accountId,
                day: $day,
                load: $load,
            ),
        );
    }

    public function update(
        float $load,
    ): void {
        assert(null !== $this->accountId);

        $this->recordApplyAndPublish(
            new DailyWasteUpdated(
                dailyWasteId: $this->id(),
                accountId: $this->accountId,
                day: $this->day,
                load: $load,
            ),
        );
    }

    public function applyDailyWasteRegistered(DailyWasteRegistered $event): void
    {
        $this->propertyId = $event->propertyId();
        $this->wasteId = $event->wasteId();
        $this->purpose = $event->purpose();
        $this->wasteManagerId = $event->wasteManagerId();
        $this->load = $event->load();
        $this->accountId = $event->accountId();
        $this->day = $event->day();
    }

    public function applyDailyWasteUpdated(DailyWasteUpdated $event): void
    {
        $this->load = $event->load();
    }

    public function __toString(): string
    {
        return (string) $this->id();
    }
}
