<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivityRegistered;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use PHPUnit\Framework\TestCase;

class ElectricityActivityRegisteredTest extends TestCase
{
    public function testPayloadAndRebuildRoundTripWithWellToTank(): void
    {
        $event = $this->event();

        $rebuilt = ElectricityActivityRegistered::rebuildFromPayload(DomainEventId::generate(), new \DateTimeImmutable('now'), $event->payload());

        $this->assertTrue($event->wellToTankEmissionFactor()->equals($rebuilt->wellToTankEmissionFactor()));
        $this->assertSame('122.0', $rebuilt->wellToTankEmissions()->co2eq());

        $locationBasedWellToTankEmissionFactor = $rebuilt->locationBasedWellToTankEmissionFactor();
        $this->assertNotNull($locationBasedWellToTankEmissionFactor);
        $this->assertSame('0.03', $locationBasedWellToTankEmissionFactor->co2eq());
        $this->assertSame(Unit::KWh, $locationBasedWellToTankEmissionFactor->unit());
        $this->assertSame('DEFRA', $locationBasedWellToTankEmissionFactor->source());
        $this->assertSame('30.0', $rebuilt->locationBasedWellToTankEmissions()?->co2eq());
    }

    public function testRebuildFromPayloadWithoutWellToTankKeysBuildsZeroWellToTank(): void
    {
        $payload = array_filter(
            $this->event()->payload(),
            static fn(string $key): bool => !str_starts_with($key, 'wellToTank') && !str_starts_with($key, 'locationBasedWellToTank'),
            ARRAY_FILTER_USE_KEY,
        );

        $rebuilt = ElectricityActivityRegistered::rebuildFromPayload(DomainEventId::generate(), new \DateTimeImmutable('now'), $payload);

        $this->assertSame('0', $rebuilt->wellToTankEmissionFactor()->co2eq());
        $this->assertSame(2024, $rebuilt->wellToTankEmissionFactor()->year());
        $this->assertSame('0', $rebuilt->wellToTankEmissions()->co2eq());
        $this->assertSame('0', $rebuilt->locationBasedWellToTankEmissionFactor()?->co2eq());
        $this->assertSame('0', $rebuilt->locationBasedWellToTankEmissions()?->co2eq());
    }

    public function testRebuildFromPayloadWithoutLocationBasedResultLeavesLocationBasedWellToTankEmpty(): void
    {
        $payload = $this->event(withLocationBasedResult: false)->payload();

        $rebuilt = ElectricityActivityRegistered::rebuildFromPayload(DomainEventId::generate(), new \DateTimeImmutable('now'), $payload);

        $this->assertNull($rebuilt->locationBasedWellToTankEmissionFactor());
        $this->assertNull($rebuilt->locationBasedWellToTankEmissions());
    }

    private function event(bool $withLocationBasedResult = true): ElectricityActivityRegistered
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
