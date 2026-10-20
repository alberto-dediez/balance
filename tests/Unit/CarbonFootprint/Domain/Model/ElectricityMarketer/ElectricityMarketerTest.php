<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer;

use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketer;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerEmissionFactorUpdated;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ElectricityMarketerTest extends TestCase
{
    public function testSetEmissionFactorAddsIt(): void
    {
        $electricityMarketer = $this->electricityMarketer();

        $electricityMarketer->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $this->assertCount(1, $electricityMarketer->emissionFactors());
        $this->assertSame('1.5', $electricityMarketer->emissionFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testSetEmissionFactorForSameYearReplacesIt(): void
    {
        $electricityMarketer = $this->electricityMarketer();

        $electricityMarketer->setEmissionFactor($this->emissionFactor(2024, '1.5'));
        $electricityMarketer->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertCount(1, $electricityMarketer->emissionFactors());
        $this->assertSame('2.0', $electricityMarketer->emissionFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testEmissionFactorForReturnsNullWhenMissing(): void
    {
        $electricityMarketer = $this->electricityMarketer();

        $this->assertNull($electricityMarketer->emissionFactorFor(1999, $this->groupId()));
    }

    public function testSetEmissionFactorUsesKWhUnit(): void
    {
        $electricityMarketer = $this->electricityMarketer();

        $electricityMarketer->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $factor = $electricityMarketer->emissionFactorFor(2024, $this->groupId());

        $this->assertNotNull($factor);
        $this->assertSame(Unit::KWh, $factor->unit());
    }

    public function testSyncEmissionFactorsAddsUpdatesAndRemoves(): void
    {
        $electricityMarketer = $this->electricityMarketer();
        $electricityMarketer->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $electricityMarketer->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $electricityMarketer->syncEmissionFactors([
            ['factor' => $this->emissionFactor(2024, '2.5'), 'wellToTank' => $this->wellToTankFactor(2024, '0.5')],
            ['factor' => $this->emissionFactor(2025, '3.0'), 'wellToTank' => $this->wellToTankFactor(2025, '0.5')],
        ]);

        $this->assertCount(2, $electricityMarketer->emissionFactors());
        $this->assertNull($electricityMarketer->emissionFactorFor(2023, $this->groupId()));
        $this->assertSame('2.5', $electricityMarketer->emissionFactorFor(2024, $this->groupId())?->co2eq());
        $this->assertSame('3.0', $electricityMarketer->emissionFactorFor(2025, $this->groupId())?->co2eq());
    }

    public function testSyncEmissionFactorsThrowsOnDuplicateYear(): void
    {
        $electricityMarketer = $this->electricityMarketer();

        $this->expectException(\InvalidArgumentException::class);

        $electricityMarketer->syncEmissionFactors([
            ['factor' => $this->emissionFactor(2024, '1.0'), 'wellToTank' => $this->wellToTankFactor(2024, '0.5')],
            ['factor' => $this->emissionFactor(2024, '2.0'), 'wellToTank' => $this->wellToTankFactor(2024, '0.5')],
        ]);
    }

    public function testRemoveEmissionFactor(): void
    {
        $electricityMarketer = $this->electricityMarketer();
        $electricityMarketer->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $electricityMarketer->removeEmissionFactor(2024, $this->groupId());

        $this->assertCount(0, $electricityMarketer->emissionFactors());
    }

    public function testSetEmissionFactorWithoutWellToTankStoresZeroWellToTank(): void
    {
        $electricityMarketer = $this->electricityMarketer();

        $electricityMarketer->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $wellToTankFactor = $electricityMarketer->wellToTankFactorFor(2024, $this->groupId());

        $this->assertNotNull($wellToTankFactor);
        $this->assertSame('0', $wellToTankFactor->co2eq());
        $this->assertSame('0', $wellToTankFactor->co2());
        $this->assertSame('0', $wellToTankFactor->ch4());
        $this->assertSame('0', $wellToTankFactor->n2o());
        $this->assertSame(2024, $wellToTankFactor->year());
        $this->assertNull($wellToTankFactor->source());
    }

    public function testSetEmissionFactorWithWellToTankStoresIt(): void
    {
        $electricityMarketer = $this->electricityMarketer();

        $electricityMarketer->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2024, '0.61'));

        $wellToTankFactor = $electricityMarketer->wellToTankFactorFor(2024, $this->groupId());

        $this->assertNotNull($wellToTankFactor);
        $this->assertSame('0.61', $wellToTankFactor->co2eq());
        $this->assertSame('DEFRA', $wellToTankFactor->source());
        $this->assertSame('1.5', $electricityMarketer->emissionFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testWellToTankFactorForReturnsNullWhenMissing(): void
    {
        $electricityMarketer = $this->electricityMarketer();

        $this->assertNull($electricityMarketer->wellToTankFactorFor(1999, $this->groupId()));
    }

    public function testSetEmissionFactorThrowsWhenWellToTankHasDifferentYear(): void
    {
        $electricityMarketer = $this->electricityMarketer();

        $this->expectException(\InvalidArgumentException::class);

        $electricityMarketer->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2023, '0.61'));
    }

    public function testSetEmissionFactorThrowsWhenWellToTankHasDifferentGroup(): void
    {
        $electricityMarketer = $this->electricityMarketer();

        $this->expectException(\InvalidArgumentException::class);

        $electricityMarketer->setEmissionFactor(
            $this->emissionFactor(2024, '1.5'),
            $this->wellToTankFactor(2024, '0.61')->withEmissionFactorGroup(EmissionFactorGroupMother::random()->id()),
        );
    }

    public function testChangingOnlyWellToTankPublishesEmissionFactorUpdated(): void
    {
        $electricityMarketer = $this->electricityMarketer();
        $electricityMarketer->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2024, '0.61'));

        DomainEventPublisher::instance()->reset();
        $spySubscriber = new SpyDomainEventSubscriber();

        $electricityMarketer->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2024, '0.7'));

        $this->assertInstanceOf(ElectricityMarketerEmissionFactorUpdated::class, $spySubscriber->lastEvent());
        $this->assertSame('0.7', $electricityMarketer->wellToTankFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testSettingSameFactorsPublishesNothing(): void
    {
        $electricityMarketer = $this->electricityMarketer();
        $electricityMarketer->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2024, '0.61'));

        DomainEventPublisher::instance()->reset();
        $spySubscriber = new SpyDomainEventSubscriber();

        $electricityMarketer->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2024, '0.61'));

        $this->assertCount(0, $spySubscriber->events());
    }

    public function testSyncEmissionFactorsStoresWellToTank(): void
    {
        $electricityMarketer = $this->electricityMarketer();

        $electricityMarketer->syncEmissionFactors([
            ['factor' => $this->emissionFactor(2024, '2.5'), 'wellToTank' => $this->wellToTankFactor(2024, '0.61')],
        ]);

        $this->assertSame('0.61', $electricityMarketer->wellToTankFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testSetEmissionFactorUsesKWhUnitForWellToTank(): void
    {
        $electricityMarketer = $this->electricityMarketer();

        $electricityMarketer->setEmissionFactor(
            $this->emissionFactor(2024, '1.5')->withUnit(Unit::Kilogram),
            $this->wellToTankFactor(2024, '0.61', Unit::Kilogram),
        );

        $this->assertSame(Unit::KWh, $electricityMarketer->wellToTankFactorFor(2024, $this->groupId())?->unit());
    }

    private function electricityMarketer(): ElectricityMarketer
    {
        return new ElectricityMarketer(
            id: ElectricityMarketerId::generate(),
            name: 'electricity-marketer-name',
        );
    }

    private function emissionFactor(int $year, string $co2eq): EmissionFactor
    {
        return new EmissionFactor(
            co2eq: $co2eq,
            co2: '0.1',
            ch4: '0.2',
            n2o: '0.3',
            year: $year,
            emissionFactorGroupId: $this->groupId(),
            unit: Unit::KWh,
            source: 'test-source',
        );
    }

    private function wellToTankFactor(int $year, string $co2eq, Unit $unit = Unit::KWh): EmissionFactor
    {
        return new EmissionFactor(
            co2eq: $co2eq,
            co2: '0.6',
            ch4: '0.01',
            n2o: '0.01',
            year: $year,
            emissionFactorGroupId: $this->groupId(),
            unit: $unit,
            source: 'DEFRA',
        );
    }

    private ?EmissionFactorGroupId $groupId = null;

    private function groupId(): EmissionFactorGroupId
    {
        return $this->groupId ??= EmissionFactorGroupMother::default()->id();
    }
}
