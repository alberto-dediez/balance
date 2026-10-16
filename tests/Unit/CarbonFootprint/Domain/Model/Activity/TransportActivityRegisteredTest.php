<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\TransportActivityRegistered;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use PHPUnit\Framework\TestCase;

class TransportActivityRegisteredTest extends TestCase
{
    public function testPayloadAndRebuildRoundTripWithWeightAndDistance(): void
    {
        $event = new TransportActivityRegistered(
            activityId: ActivityId::generate(),
            accountId: new AccountId('account-id'),
            transportId: TransportId::generate(),
            emissionFactor: ActivityMother::emissionFactor()->withUnit(Unit::TonneKilometre),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::TonneKilometre, 875.0),
            weight: new Quantity(2.5, Unit::Tonne),
            distanceKm: 350.0,
        );

        $rebuilt = TransportActivityRegistered::rebuildFromPayload(DomainEventId::generate(), new \DateTimeImmutable('now'), $event->payload());

        $rebuiltWeight = $rebuilt->weight();
        $this->assertNotNull($rebuiltWeight);
        $this->assertSame(2.5, $rebuiltWeight->value());
        $this->assertSame(Unit::Tonne, $rebuiltWeight->unit());
        $this->assertSame(350.0, $rebuilt->distanceKm());
        $this->assertTrue($event->quantity()->equals($rebuilt->quantity()));
    }

    public function testRebuildFromPayloadWithoutWeightAndDistanceKeys(): void
    {
        $event = new TransportActivityRegistered(
            activityId: ActivityId::generate(),
            accountId: new AccountId('account-id'),
            transportId: TransportId::generate(),
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Kilogram, 75.0),
        );

        $payload = $event->payload();
        unset($payload['weightValue'], $payload['weightUnit'], $payload['distanceKm']);

        $rebuilt = TransportActivityRegistered::rebuildFromPayload(DomainEventId::generate(), new \DateTimeImmutable('now'), $payload);

        $this->assertNull($rebuilt->weight());
        $this->assertNull($rebuilt->distanceKm());
        $this->assertTrue($event->quantity()->equals($rebuilt->quantity()));
    }

    public function testRebuildFromPayloadWithLegacyRoadTransportIdKey(): void
    {
        $transportId = TransportId::generate();
        $event = new TransportActivityRegistered(
            activityId: ActivityId::generate(),
            accountId: new AccountId('account-id'),
            transportId: $transportId,
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Kilogram, 75.0),
        );

        $payload = $event->payload();
        $payload['roadTransportId'] = $payload['transportId'];
        unset($payload['transportId']);

        $rebuilt = TransportActivityRegistered::rebuildFromPayload(DomainEventId::generate(), new \DateTimeImmutable('now'), $payload);

        $this->assertTrue($transportId->equals($rebuilt->transportId()));
    }
}
