<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\CombustibleActivityRegistered;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use PHPUnit\Framework\TestCase;

class CombustibleActivityRegisteredTest extends TestCase
{
    public function testPayloadAndRebuildRoundTripWithWellToTank(): void
    {
        $event = $this->event();

        $rebuilt = CombustibleActivityRegistered::rebuildFromPayload(DomainEventId::generate(), new \DateTimeImmutable('now'), $event->payload());

        $this->assertTrue($event->wellToTankEmissionFactor()->equals($rebuilt->wellToTankEmissionFactor()));
        $this->assertSame('122.0', $rebuilt->wellToTankEmissions()->co2eq());
        $this->assertSame('122.0', $rebuilt->wellToTankEmissions()->co2());
    }

    public function testRebuildFromPayloadWithoutWellToTankKeysBuildsZeroWellToTank(): void
    {
        $payload = array_filter(
            $this->event()->payload(),
            static fn(string $key): bool => !str_starts_with($key, 'wellToTank'),
            ARRAY_FILTER_USE_KEY,
        );

        $rebuilt = CombustibleActivityRegistered::rebuildFromPayload(DomainEventId::generate(), new \DateTimeImmutable('now'), $payload);

        $wellToTankEmissionFactor = $rebuilt->wellToTankEmissionFactor();
        $this->assertSame('0', $wellToTankEmissionFactor->co2eq());
        $this->assertSame(2024, $wellToTankEmissionFactor->year());
        $this->assertSame(Unit::Kilogram, $wellToTankEmissionFactor->unit());
        $this->assertTrue($rebuilt->emissionFactor()->emissionFactorGroupId()->equals($wellToTankEmissionFactor->emissionFactorGroupId()));
        $this->assertNull($wellToTankEmissionFactor->source());
        $this->assertSame('0', $rebuilt->wellToTankEmissions()->co2eq());
        $this->assertSame('0', $rebuilt->wellToTankEmissions()->n2o());
    }

    private function event(): CombustibleActivityRegistered
    {
        return new CombustibleActivityRegistered(
            activityId: ActivityId::generate(),
            accountId: new AccountId('account-id'),
            combustibleId: CombustibleId::generate(),
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Liter, 200.0),
            source: null,
            wellToTankEmissionFactor: ActivityMother::wellToTankEmissionFactor(),
            wellToTankEmissions: ActivityMother::wellToTankEmissions(),
        );
    }
}
