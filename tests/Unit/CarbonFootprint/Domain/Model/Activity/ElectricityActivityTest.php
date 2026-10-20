<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivity;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivityRegistered;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Event\DomainEventPublisher;
use PHPUnit\Framework\TestCase;

class ElectricityActivityTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testRegisterWithoutElectricityMixLeavesLocationBasedResultEmpty(): void
    {
        $activity = ElectricityActivityMother::registered();

        $this->assertNull($activity->electricityMixId());
        $this->assertNull($activity->locationBasedEmissionFactor());
        $this->assertNull($activity->locationBasedEmissions());
    }

    public function testRegisterWithElectricityMixStoresLocationBasedResult(): void
    {
        $activity = ElectricityActivityMother::registeredWithLocationBasedResult();

        $this->assertNotNull($activity->electricityMixId());

        $locationBasedEmissionFactor = $activity->locationBasedEmissionFactor();

        $this->assertNotNull($locationBasedEmissionFactor);
        $this->assertSame('0.18', $locationBasedEmissionFactor->co2eq());
        $this->assertSame(Unit::KWh, $locationBasedEmissionFactor->unit());
        $this->assertSame($activity->emissionFactor()->year(), $locationBasedEmissionFactor->year());
        $this->assertEquals($activity->emissionFactor()->emissionFactorGroupId(), $locationBasedEmissionFactor->emissionFactorGroupId());
        $this->assertSame('180.0', $activity->locationBasedEmissions()?->co2eq());
        $this->assertSame('15.0', $activity->emissions()->co2eq());
    }

    public function testRegisterWithPartialLocationBasedResultThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ElectricityActivity::register(
            id: ActivityId::generate(),
            accountId: new AccountId('account-id'),
            electricityMarketerId: ElectricityMarketerId::generate(),
            gdOFactorId: null,
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::KWh, 1000.0),
            electricityMixId: ElectricityMixId::generate(),
            locationBasedEmissionFactor: ElectricityActivityMother::locationBasedEmissionFactor(),
        );
    }

    public function testRegisterWithoutWellToTankStoresZeroWellToTank(): void
    {
        $activity = ElectricityActivityMother::registered();

        $this->assertSame('0', $activity->wellToTankEmissionFactor()->co2eq());
        $this->assertSame($activity->emissionFactor()->year(), $activity->wellToTankEmissionFactor()->year());
        $this->assertSame('0', $activity->wellToTankEmissions()->co2eq());
    }

    public function testRegisterWithWellToTankExposesIt(): void
    {
        $activity = ElectricityActivityMother::registeredWithWellToTank();

        $this->assertSame('0.61', $activity->wellToTankEmissionFactor()->co2eq());
        $this->assertSame('122.0', $activity->wellToTankEmissions()->co2eq());
        $this->assertSame('15.0', $activity->emissions()->co2eq());
    }

    public function testRegisterWithElectricityMixStoresLocationBasedWellToTank(): void
    {
        $activity = ElectricityActivityMother::registeredWithLocationBasedResult();

        $locationBasedWellToTankEmissionFactor = $activity->locationBasedWellToTankEmissionFactor();

        $this->assertNotNull($locationBasedWellToTankEmissionFactor);
        $this->assertSame('0.03', $locationBasedWellToTankEmissionFactor->co2eq());
        $this->assertSame(Unit::KWh, $locationBasedWellToTankEmissionFactor->unit());
        $this->assertSame('DEFRA', $locationBasedWellToTankEmissionFactor->source());
        $this->assertSame($activity->emissionFactor()->year(), $locationBasedWellToTankEmissionFactor->year());
        $this->assertSame('30.0', $activity->locationBasedWellToTankEmissions()?->co2eq());
    }

    public function testRegisterWithLocationBasedResultWithoutWellToTankThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ElectricityActivity::register(
            id: ActivityId::generate(),
            accountId: new AccountId('account-id'),
            electricityMarketerId: ElectricityMarketerId::generate(),
            gdOFactorId: null,
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::KWh, 1000.0),
            electricityMixId: ElectricityMixId::generate(),
            locationBasedEmissionFactor: ElectricityActivityMother::locationBasedEmissionFactor(),
            locationBasedEmissions: ElectricityActivityMother::locationBasedEmissions(),
        );
    }

    public function testUpdateAddsAndRemovesTheLocationBasedResult(): void
    {
        $activity = ElectricityActivityMother::registered();

        $activity->update(
            electricityMarketerId: ElectricityMarketerId::generate(),
            gdOFactorId: null,
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::KWh, 1000.0),
            electricityMixId: ElectricityMixId::generate(),
            locationBasedEmissionFactor: ElectricityActivityMother::locationBasedEmissionFactor(),
            locationBasedEmissions: ElectricityActivityMother::locationBasedEmissions(),
            locationBasedWellToTankEmissionFactor: ElectricityActivityMother::locationBasedWellToTankEmissionFactor(),
            locationBasedWellToTankEmissions: ElectricityActivityMother::locationBasedWellToTankEmissions(),
        );

        $this->assertNotNull($activity->locationBasedEmissions());
        $this->assertSame('30.0', $activity->locationBasedWellToTankEmissions()?->co2eq());

        $activity->update(
            electricityMarketerId: ElectricityMarketerId::generate(),
            gdOFactorId: null,
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::KWh, 1000.0),
        );

        $this->assertNull($activity->electricityMixId());
        $this->assertNull($activity->locationBasedEmissionFactor());
        $this->assertNull($activity->locationBasedEmissions());
        $this->assertNull($activity->locationBasedWellToTankEmissionFactor());
        $this->assertNull($activity->locationBasedWellToTankEmissions());
    }

    public function testRebuildFromPayloadWithoutLocationBasedKeys(): void
    {
        $payload = $this->registeredEvent()->payload();

        unset(
            $payload['electricityMixId'],
            $payload['locationBasedEmissionFactorCo2eq'],
            $payload['locationBasedEmissionFactorCo2'],
            $payload['locationBasedEmissionFactorCh4'],
            $payload['locationBasedEmissionFactorN2o'],
            $payload['locationBasedEmissionFactorSource'],
            $payload['locationBasedEmissionsCo2eq'],
            $payload['locationBasedEmissionsCo2'],
            $payload['locationBasedEmissionsCh4'],
            $payload['locationBasedEmissionsN2o'],
        );

        $event = ElectricityActivityRegistered::rebuildFromPayload(DomainEventId::generate(), new \DateTimeImmutable(), $payload);

        $this->assertNull($event->electricityMixId());
        $this->assertNull($event->locationBasedEmissionFactor());
        $this->assertNull($event->locationBasedEmissions());
    }

    public function testRebuildFromPayloadWithLocationBasedKeys(): void
    {
        $payload = $this->registeredEvent(withLocationBasedResult: true)->payload();

        $event = ElectricityActivityRegistered::rebuildFromPayload(DomainEventId::generate(), new \DateTimeImmutable(), $payload);

        $this->assertNotNull($event->electricityMixId());
        $locationBasedEmissionFactor = $event->locationBasedEmissionFactor();

        $this->assertNotNull($locationBasedEmissionFactor);
        $this->assertSame('0.18', $locationBasedEmissionFactor->co2eq());
        $this->assertSame(Unit::KWh, $locationBasedEmissionFactor->unit());
        $this->assertSame('REE', $locationBasedEmissionFactor->source());
        $this->assertSame('180.0', $event->locationBasedEmissions()?->co2eq());
    }

    private function registeredEvent(bool $withLocationBasedResult = false): ElectricityActivityRegistered
    {
        return new ElectricityActivityRegistered(
            activityId: ActivityId::generate(),
            accountId: new AccountId('account-id'),
            electricityMarketerId: ElectricityMarketerId::generate(),
            gdOFactorId: null,
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::KWh, 1000.0),
            source: null,
            wellToTankEmissionFactor: ActivityMother::wellToTankEmissionFactor(),
            wellToTankEmissions: ActivityMother::wellToTankEmissions(),
            electricityMixId: $withLocationBasedResult ? ElectricityMixId::generate() : null,
            locationBasedEmissionFactor: $withLocationBasedResult ? ElectricityActivityMother::locationBasedEmissionFactor() : null,
            locationBasedEmissions: $withLocationBasedResult ? ElectricityActivityMother::locationBasedEmissions() : null,
            locationBasedWellToTankEmissionFactor: $withLocationBasedResult ? ElectricityActivityMother::locationBasedWellToTankEmissionFactor() : null,
            locationBasedWellToTankEmissions: $withLocationBasedResult ? ElectricityActivityMother::locationBasedWellToTankEmissions() : null,
        );
    }
}
