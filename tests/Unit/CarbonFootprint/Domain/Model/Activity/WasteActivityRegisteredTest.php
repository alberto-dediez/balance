<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\WasteActivityRegistered;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use PHPUnit\Framework\TestCase;

class WasteActivityRegisteredTest extends TestCase
{
    public function testPayloadAndRebuildRoundTripWithoutContainer(): void
    {
        $event = new WasteActivityRegistered(
            activityId: ActivityId::generate(),
            accountId: new AccountId('account-id'),
            wasteId: WasteId::generate(),
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Kilogram, 75.0),
        );

        $rebuilt = WasteActivityRegistered::rebuildFromPayload(DomainEventId::generate(), new \DateTimeImmutable('now'), $event->payload());

        $this->assertNull($rebuilt->containerCapacity());
        $this->assertNull($rebuilt->fillPercentage());
        $this->assertTrue($event->quantity()->equals($rebuilt->quantity()));
    }

    public function testPayloadAndRebuildRoundTripWithContainer(): void
    {
        $event = new WasteActivityRegistered(
            activityId: ActivityId::generate(),
            accountId: new AccountId('account-id'),
            wasteId: WasteId::generate(),
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Kilogram, 250.0),
            containerCapacity: new Quantity(2, Unit::CubicMeter),
            fillPercentage: 50.0,
        );

        $rebuilt = WasteActivityRegistered::rebuildFromPayload(DomainEventId::generate(), new \DateTimeImmutable('now'), $event->payload());

        $rebuiltContainerCapacity = $rebuilt->containerCapacity();
        $eventContainerCapacity = $event->containerCapacity();
        $this->assertNotNull($rebuiltContainerCapacity);
        $this->assertNotNull($eventContainerCapacity);
        $this->assertTrue($eventContainerCapacity->equals($rebuiltContainerCapacity));
        $this->assertSame(50.0, $rebuilt->fillPercentage());
    }
}
