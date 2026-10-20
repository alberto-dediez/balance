<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuel;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelEmissionFactorUpdated;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryType;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class MachineryFuelTest extends TestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testTranslateAddTranslation(): void
    {
        $machineryFuel = new MachineryFuel(
            id: MachineryFuelId::generate(),
            name: 'machinery-fuel-name',
            unit: Unit::Kilogram,
        );

        $this->assertCount(0, $machineryFuel->translations());
        $machineryFuel->translate('translated-name', Locale::English);
        $this->assertCount(1, $machineryFuel->translations());
    }

    public function testTranslateReturnOriginalNameIfNoLocaleIsSet(): void
    {
        $machineryFuel = new MachineryFuel(
            id: MachineryFuelId::generate(),
            name: 'machinery-fuel-name',
            unit: Unit::Kilogram,
        );
        $machineryFuel->translate('translated-name-en', Locale::English);
        $machineryFuel->translate('translated-name-it', Locale::Italian);
        $machineryFuel->translate('translated-name-ge', Locale::German);

        $this->assertEquals('machinery-fuel-name', $machineryFuel->name());
    }

    public function testTranslateReturnTranslatedName(): void
    {
        $machineryFuel = new MachineryFuel(
            id: MachineryFuelId::generate(),
            name: 'machinery-fuel-name',
            unit: Unit::Kilogram,
        );
        $machineryFuel->translate('translated-name-en', Locale::English);
        $machineryFuel->translate('translated-name-it', Locale::Italian);
        $machineryFuel->translate('translated-name-ge', Locale::German);

        FakeLocaleSetter::set(Locale::Italian);

        $this->assertEquals('translated-name-it', $machineryFuel->name());
    }

    public function testSetEmissionFactorAddsIt(): void
    {
        $machineryFuel = $this->machineryFuel();

        $machineryFuel->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $this->assertCount(1, $machineryFuel->emissionFactors());
        $this->assertSame('1.5', $machineryFuel->emissionFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testSetEmissionFactorForSameYearReplacesIt(): void
    {
        $machineryFuel = $this->machineryFuel();

        $machineryFuel->setEmissionFactor($this->emissionFactor(2024, '1.5'));
        $machineryFuel->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertCount(1, $machineryFuel->emissionFactors());
        $this->assertSame('2.0', $machineryFuel->emissionFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testEmissionFactorForReturnsNullWhenMissing(): void
    {
        $machineryFuel = $this->machineryFuel();

        $this->assertNull($machineryFuel->emissionFactorFor(1999, $this->groupId()));
    }

    public function testSyncEmissionFactorsAddsUpdatesAndRemoves(): void
    {
        $machineryFuel = $this->machineryFuel();
        $machineryFuel->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $machineryFuel->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $machineryFuel->syncEmissionFactors([
            ['factor' => $this->emissionFactor(2024, '2.5'), 'wellToTank' => $this->wellToTankFactor(2024, '0.5')],
            ['factor' => $this->emissionFactor(2025, '3.0'), 'wellToTank' => $this->wellToTankFactor(2025, '0.5')],
        ]);

        $this->assertCount(2, $machineryFuel->emissionFactors());
        $this->assertNull($machineryFuel->emissionFactorFor(2023, $this->groupId()));
        $this->assertSame('2.5', $machineryFuel->emissionFactorFor(2024, $this->groupId())?->co2eq());
        $this->assertSame('3.0', $machineryFuel->emissionFactorFor(2025, $this->groupId())?->co2eq());
    }

    public function testSyncEmissionFactorsThrowsOnDuplicateYear(): void
    {
        $machineryFuel = $this->machineryFuel();

        $this->expectException(\InvalidArgumentException::class);

        $machineryFuel->syncEmissionFactors([
            ['factor' => $this->emissionFactor(2024, '1.0'), 'wellToTank' => $this->wellToTankFactor(2024, '0.5')],
            ['factor' => $this->emissionFactor(2024, '2.0'), 'wellToTank' => $this->wellToTankFactor(2024, '0.5')],
        ]);
    }

    public function testUpdatingMachineryFuelUnitRestampsEmissionFactors(): void
    {
        $machineryFuel = $this->machineryFuel();
        $machineryFuel->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $machineryFuel->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertSame(Unit::Kilogram, $machineryFuel->emissionFactorFor(2023, $this->groupId())?->unit());

        $machineryFuel->update('machinery-fuel-name', Unit::Piece, MachineryType::Agricultural);

        $factor2023 = $machineryFuel->emissionFactorFor(2023, $this->groupId());
        $factor2024 = $machineryFuel->emissionFactorFor(2024, $this->groupId());

        $this->assertNotNull($factor2023);
        $this->assertNotNull($factor2024);
        $this->assertSame(Unit::Piece, $factor2023->unit());
        $this->assertSame(Unit::Piece, $factor2024->unit());
        $this->assertSame('1.0', $factor2023->co2eq());
    }

    public function testRemoveEmissionFactor(): void
    {
        $machineryFuel = $this->machineryFuel();
        $machineryFuel->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $machineryFuel->removeEmissionFactor(2024, $this->groupId());

        $this->assertCount(0, $machineryFuel->emissionFactors());
    }

    public function testSetEmissionFactorWithoutWellToTankStoresZeroWellToTank(): void
    {
        $machineryFuel = $this->machineryFuel();

        $machineryFuel->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $wellToTankFactor = $machineryFuel->wellToTankFactorFor(2024, $this->groupId());

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
        $machineryFuel = $this->machineryFuel();

        $machineryFuel->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2024, '0.61'));

        $wellToTankFactor = $machineryFuel->wellToTankFactorFor(2024, $this->groupId());

        $this->assertNotNull($wellToTankFactor);
        $this->assertSame('0.61', $wellToTankFactor->co2eq());
        $this->assertSame('DEFRA', $wellToTankFactor->source());
        $this->assertSame('1.5', $machineryFuel->emissionFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testWellToTankFactorForReturnsNullWhenMissing(): void
    {
        $machineryFuel = $this->machineryFuel();

        $this->assertNull($machineryFuel->wellToTankFactorFor(1999, $this->groupId()));
    }

    public function testSetEmissionFactorThrowsWhenWellToTankHasDifferentYear(): void
    {
        $machineryFuel = $this->machineryFuel();

        $this->expectException(\InvalidArgumentException::class);

        $machineryFuel->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2023, '0.61'));
    }

    public function testSetEmissionFactorThrowsWhenWellToTankHasDifferentGroup(): void
    {
        $machineryFuel = $this->machineryFuel();

        $this->expectException(\InvalidArgumentException::class);

        $machineryFuel->setEmissionFactor(
            $this->emissionFactor(2024, '1.5'),
            $this->wellToTankFactor(2024, '0.61')->withEmissionFactorGroup(EmissionFactorGroupMother::random()->id()),
        );
    }

    public function testChangingOnlyWellToTankPublishesEmissionFactorUpdated(): void
    {
        $machineryFuel = $this->machineryFuel();
        $machineryFuel->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2024, '0.61'));

        DomainEventPublisher::instance()->reset();
        $spySubscriber = new SpyDomainEventSubscriber();

        $machineryFuel->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2024, '0.7'));

        $this->assertInstanceOf(MachineryFuelEmissionFactorUpdated::class, $spySubscriber->lastEvent());
        $this->assertSame('0.7', $machineryFuel->wellToTankFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testSettingSameFactorsPublishesNothing(): void
    {
        $machineryFuel = $this->machineryFuel();
        $machineryFuel->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2024, '0.61'));

        DomainEventPublisher::instance()->reset();
        $spySubscriber = new SpyDomainEventSubscriber();

        $machineryFuel->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2024, '0.61'));

        $this->assertCount(0, $spySubscriber->events());
    }

    public function testSyncEmissionFactorsStoresWellToTank(): void
    {
        $machineryFuel = $this->machineryFuel();

        $machineryFuel->syncEmissionFactors([
            ['factor' => $this->emissionFactor(2024, '2.5'), 'wellToTank' => $this->wellToTankFactor(2024, '0.61')],
        ]);

        $this->assertSame('0.61', $machineryFuel->wellToTankFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testUpdatingUnitRestampsWellToTankFactors(): void
    {
        $machineryFuel = $this->machineryFuel();
        $machineryFuel->setEmissionFactor($this->emissionFactor(2024, '2.0'), $this->wellToTankFactor(2024, '0.61'));

        $machineryFuel->update('machinery-fuel-name', Unit::Piece, MachineryType::Agricultural);

        $wellToTankFactor = $machineryFuel->wellToTankFactorFor(2024, $this->groupId());

        $this->assertNotNull($wellToTankFactor);
        $this->assertSame(Unit::Piece, $wellToTankFactor->unit());
        $this->assertSame('0.61', $wellToTankFactor->co2eq());
    }

    private function machineryFuel(): MachineryFuel
    {
        return new MachineryFuel(
            id: MachineryFuelId::generate(),
            name: 'machinery-fuel-name',
            unit: Unit::Kilogram,
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
            unit: Unit::Kilogram,
            source: 'test-source',
        );
    }

    private function wellToTankFactor(int $year, string $co2eq, Unit $unit = Unit::Kilogram): EmissionFactor
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
