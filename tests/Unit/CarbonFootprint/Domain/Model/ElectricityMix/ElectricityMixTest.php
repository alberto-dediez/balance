<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix;

use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMix;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixEmissionFactorUpdated;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Property\Domain\Model\Country\CountryId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ElectricityMixTest extends TestCase
{
    public function testRegisterStoresCountry(): void
    {
        $electricityMix = ElectricityMixMother::fromCountryAndId('C-ES', (string) ElectricityMixId::generate());

        $this->assertSame('C-ES', (string) $electricityMix->countryId());
    }

    public function testUpdateChangesCountry(): void
    {
        $electricityMix = ElectricityMixMother::fromCountryAndId('C-ES', (string) ElectricityMixId::generate());

        $electricityMix->update(new CountryId('C-FR'));

        $this->assertSame('C-FR', (string) $electricityMix->countryId());
    }

    public function testCountryIdThrowsWhenNotRegistered(): void
    {
        $electricityMix = new ElectricityMix(id: ElectricityMixId::generate());

        $this->expectException(\LogicException::class);

        $electricityMix->countryId();
    }

    public function testSetEmissionFactorAddsIt(): void
    {
        $electricityMix = $this->electricityMix();

        $electricityMix->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $this->assertCount(1, $electricityMix->emissionFactors());
        $this->assertSame('1.5', $electricityMix->emissionFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testSetEmissionFactorForSameYearReplacesIt(): void
    {
        $electricityMix = $this->electricityMix();

        $electricityMix->setEmissionFactor($this->emissionFactor(2024, '1.5'));
        $electricityMix->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertCount(1, $electricityMix->emissionFactors());
        $this->assertSame('2.0', $electricityMix->emissionFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testEmissionFactorForReturnsNullWhenMissing(): void
    {
        $electricityMix = $this->electricityMix();

        $this->assertNull($electricityMix->emissionFactorFor(1999, $this->groupId()));
    }

    public function testSetEmissionFactorUsesKWhUnit(): void
    {
        $electricityMix = $this->electricityMix();

        $electricityMix->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $factor = $electricityMix->emissionFactorFor(2024, $this->groupId());

        $this->assertNotNull($factor);
        $this->assertSame(Unit::KWh, $factor->unit());
    }

    public function testSyncEmissionFactorsAddsUpdatesAndRemoves(): void
    {
        $electricityMix = $this->electricityMix();
        $electricityMix->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $electricityMix->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $electricityMix->syncEmissionFactors([
            ['factor' => $this->emissionFactor(2024, '2.5'), 'wellToTank' => $this->wellToTankFactor(2024, '0.5')],
            ['factor' => $this->emissionFactor(2025, '3.0'), 'wellToTank' => $this->wellToTankFactor(2025, '0.5')],
        ]);

        $this->assertCount(2, $electricityMix->emissionFactors());
        $this->assertNull($electricityMix->emissionFactorFor(2023, $this->groupId()));
        $this->assertSame('2.5', $electricityMix->emissionFactorFor(2024, $this->groupId())?->co2eq());
        $this->assertSame('3.0', $electricityMix->emissionFactorFor(2025, $this->groupId())?->co2eq());
    }

    public function testSyncEmissionFactorsThrowsOnDuplicateYear(): void
    {
        $electricityMix = $this->electricityMix();

        $this->expectException(\InvalidArgumentException::class);

        $electricityMix->syncEmissionFactors([
            ['factor' => $this->emissionFactor(2024, '1.0'), 'wellToTank' => $this->wellToTankFactor(2024, '0.5')],
            ['factor' => $this->emissionFactor(2024, '2.0'), 'wellToTank' => $this->wellToTankFactor(2024, '0.5')],
        ]);
    }

    public function testRemoveEmissionFactor(): void
    {
        $electricityMix = $this->electricityMix();
        $electricityMix->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $electricityMix->removeEmissionFactor(2024, $this->groupId());

        $this->assertCount(0, $electricityMix->emissionFactors());
    }

    public function testSetEmissionFactorWithoutWellToTankStoresZeroWellToTank(): void
    {
        $electricityMix = $this->electricityMix();

        $electricityMix->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $wellToTankFactor = $electricityMix->wellToTankFactorFor(2024, $this->groupId());

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
        $electricityMix = $this->electricityMix();

        $electricityMix->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2024, '0.61'));

        $wellToTankFactor = $electricityMix->wellToTankFactorFor(2024, $this->groupId());

        $this->assertNotNull($wellToTankFactor);
        $this->assertSame('0.61', $wellToTankFactor->co2eq());
        $this->assertSame('DEFRA', $wellToTankFactor->source());
        $this->assertSame('1.5', $electricityMix->emissionFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testWellToTankFactorForReturnsNullWhenMissing(): void
    {
        $electricityMix = $this->electricityMix();

        $this->assertNull($electricityMix->wellToTankFactorFor(1999, $this->groupId()));
    }

    public function testSetEmissionFactorThrowsWhenWellToTankHasDifferentYear(): void
    {
        $electricityMix = $this->electricityMix();

        $this->expectException(\InvalidArgumentException::class);

        $electricityMix->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2023, '0.61'));
    }

    public function testSetEmissionFactorThrowsWhenWellToTankHasDifferentGroup(): void
    {
        $electricityMix = $this->electricityMix();

        $this->expectException(\InvalidArgumentException::class);

        $electricityMix->setEmissionFactor(
            $this->emissionFactor(2024, '1.5'),
            $this->wellToTankFactor(2024, '0.61')->withEmissionFactorGroup(EmissionFactorGroupMother::random()->id()),
        );
    }

    public function testChangingOnlyWellToTankPublishesEmissionFactorUpdated(): void
    {
        $electricityMix = $this->electricityMix();
        $electricityMix->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2024, '0.61'));

        DomainEventPublisher::instance()->reset();
        $spySubscriber = new SpyDomainEventSubscriber();

        $electricityMix->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2024, '0.7'));

        $this->assertInstanceOf(ElectricityMixEmissionFactorUpdated::class, $spySubscriber->lastEvent());
        $this->assertSame('0.7', $electricityMix->wellToTankFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testSettingSameFactorsPublishesNothing(): void
    {
        $electricityMix = $this->electricityMix();
        $electricityMix->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2024, '0.61'));

        DomainEventPublisher::instance()->reset();
        $spySubscriber = new SpyDomainEventSubscriber();

        $electricityMix->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2024, '0.61'));

        $this->assertCount(0, $spySubscriber->events());
    }

    public function testSyncEmissionFactorsStoresWellToTank(): void
    {
        $electricityMix = $this->electricityMix();

        $electricityMix->syncEmissionFactors([
            ['factor' => $this->emissionFactor(2024, '2.5'), 'wellToTank' => $this->wellToTankFactor(2024, '0.61')],
        ]);

        $this->assertSame('0.61', $electricityMix->wellToTankFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testSetEmissionFactorUsesKWhUnitForWellToTank(): void
    {
        $electricityMix = $this->electricityMix();

        $electricityMix->setEmissionFactor(
            $this->emissionFactor(2024, '1.5')->withUnit(Unit::Kilogram),
            $this->wellToTankFactor(2024, '0.61', Unit::Kilogram),
        );

        $this->assertSame(Unit::KWh, $electricityMix->wellToTankFactorFor(2024, $this->groupId())?->unit());
    }

    private function electricityMix(): ElectricityMix
    {
        return new ElectricityMix(
            id: ElectricityMixId::generate(),
            countryId: new CountryId('C-ES'),
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
