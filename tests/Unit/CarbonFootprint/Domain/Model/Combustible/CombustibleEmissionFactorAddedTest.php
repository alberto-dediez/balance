<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible;

use App\CarbonFootprint\Domain\Model\Combustible\CombustibleEmissionFactorAdded;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleEmissionFactorId;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use PHPUnit\Framework\TestCase;

class CombustibleEmissionFactorAddedTest extends TestCase
{
    public function testPayloadAndRebuildRoundTripWithWellToTank(): void
    {
        $event = $this->event();

        $rebuilt = CombustibleEmissionFactorAdded::rebuildFromPayload(DomainEventId::generate(), new \DateTimeImmutable('now'), $event->payload());

        $this->assertTrue($event->emissionFactor()->equals($rebuilt->emissionFactor()));
        $this->assertTrue($event->wellToTankFactor()->equals($rebuilt->wellToTankFactor()));
        $this->assertSame('DEFRA', $rebuilt->wellToTankFactor()->source());
    }

    public function testRebuildFromPayloadWithoutWellToTankKeysBuildsZeroWellToTank(): void
    {
        $payload = array_filter(
            $this->event()->payload(),
            static fn(string $key): bool => !str_starts_with($key, 'wellToTank'),
            ARRAY_FILTER_USE_KEY,
        );

        $rebuilt = CombustibleEmissionFactorAdded::rebuildFromPayload(DomainEventId::generate(), new \DateTimeImmutable('now'), $payload);

        $wellToTankFactor = $rebuilt->wellToTankFactor();
        $this->assertSame('0', $wellToTankFactor->co2eq());
        $this->assertSame('0', $wellToTankFactor->co2());
        $this->assertSame('0', $wellToTankFactor->ch4());
        $this->assertSame('0', $wellToTankFactor->n2o());
        $this->assertSame(2024, $wellToTankFactor->year());
        $this->assertTrue($this->groupId()->equals($wellToTankFactor->emissionFactorGroupId()));
        $this->assertSame(Unit::Liter, $wellToTankFactor->unit());
        $this->assertNull($wellToTankFactor->source());
    }

    private function event(): CombustibleEmissionFactorAdded
    {
        return new CombustibleEmissionFactorAdded(
            combustibleId: CombustibleId::generate(),
            emissionFactorId: CombustibleEmissionFactorId::generate(),
            emissionFactor: new EmissionFactor('2.68', '2.6', '0.05', '0.03', 2024, $this->groupId(), Unit::Liter, 'MITECO'),
            wellToTankFactor: new EmissionFactor('0.61', '0.6', '0.01', '0.01', 2024, $this->groupId(), Unit::Liter, 'DEFRA'),
        );
    }

    private ?EmissionFactorGroupId $groupId = null;

    private function groupId(): EmissionFactorGroupId
    {
        return $this->groupId ??= EmissionFactorGroupMother::default()->id();
    }
}
