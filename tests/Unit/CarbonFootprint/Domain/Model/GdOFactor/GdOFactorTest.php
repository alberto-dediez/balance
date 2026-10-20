<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactor;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorEmissionFactorUpdated;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class GdOFactorTest extends TestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testTranslateAddTranslation(): void
    {
        $gdOFactor = $this->gdOFactor();

        $this->assertCount(0, $gdOFactor->translations());
        $gdOFactor->translate('translated-name', Locale::English);
        $this->assertCount(1, $gdOFactor->translations());
    }

    public function testTranslateReturnOriginalNameIfNoLocaleIsSet(): void
    {
        $gdOFactor = $this->gdOFactor();
        $gdOFactor->translate('translated-name-en', Locale::English);
        $gdOFactor->translate('translated-name-it', Locale::Italian);
        $gdOFactor->translate('translated-name-ge', Locale::German);

        $this->assertEquals('gdo-factor-name', $gdOFactor->name());
    }

    public function testTranslateReturnTranslatedName(): void
    {
        $gdOFactor = $this->gdOFactor();
        $gdOFactor->translate('translated-name-en', Locale::English);
        $gdOFactor->translate('translated-name-it', Locale::Italian);
        $gdOFactor->translate('translated-name-ge', Locale::German);

        FakeLocaleSetter::set(Locale::Italian);

        $this->assertEquals('translated-name-it', $gdOFactor->name());
    }

    public function testSetEmissionFactorAddsIt(): void
    {
        $gdOFactor = $this->gdOFactor();

        $gdOFactor->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $this->assertCount(1, $gdOFactor->emissionFactors());
        $this->assertSame('1.5', $gdOFactor->emissionFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testSetEmissionFactorForSameYearReplacesIt(): void
    {
        $gdOFactor = $this->gdOFactor();

        $gdOFactor->setEmissionFactor($this->emissionFactor(2024, '1.5'));
        $gdOFactor->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertCount(1, $gdOFactor->emissionFactors());
        $this->assertSame('2.0', $gdOFactor->emissionFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testEmissionFactorForReturnsNullWhenMissing(): void
    {
        $gdOFactor = $this->gdOFactor();

        $this->assertNull($gdOFactor->emissionFactorFor(1999, $this->groupId()));
    }

    public function testSetEmissionFactorUsesKWhUnit(): void
    {
        $gdOFactor = $this->gdOFactor();

        $gdOFactor->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $factor = $gdOFactor->emissionFactorFor(2024, $this->groupId());

        $this->assertNotNull($factor);
        $this->assertSame(Unit::KWh, $factor->unit());
    }

    public function testSyncEmissionFactorsAddsUpdatesAndRemoves(): void
    {
        $gdOFactor = $this->gdOFactor();
        $gdOFactor->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $gdOFactor->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $gdOFactor->syncEmissionFactors([
            ['factor' => $this->emissionFactor(2024, '2.5'), 'wellToTank' => $this->wellToTankFactor(2024, '0.5')],
            ['factor' => $this->emissionFactor(2025, '3.0'), 'wellToTank' => $this->wellToTankFactor(2025, '0.5')],
        ]);

        $this->assertCount(2, $gdOFactor->emissionFactors());
        $this->assertNull($gdOFactor->emissionFactorFor(2023, $this->groupId()));
        $this->assertSame('2.5', $gdOFactor->emissionFactorFor(2024, $this->groupId())?->co2eq());
        $this->assertSame('3.0', $gdOFactor->emissionFactorFor(2025, $this->groupId())?->co2eq());
    }

    public function testSyncEmissionFactorsThrowsOnDuplicateYear(): void
    {
        $gdOFactor = $this->gdOFactor();

        $this->expectException(\InvalidArgumentException::class);

        $gdOFactor->syncEmissionFactors([
            ['factor' => $this->emissionFactor(2024, '1.0'), 'wellToTank' => $this->wellToTankFactor(2024, '0.5')],
            ['factor' => $this->emissionFactor(2024, '2.0'), 'wellToTank' => $this->wellToTankFactor(2024, '0.5')],
        ]);
    }

    public function testRemoveEmissionFactor(): void
    {
        $gdOFactor = $this->gdOFactor();
        $gdOFactor->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $gdOFactor->removeEmissionFactor(2024, $this->groupId());

        $this->assertCount(0, $gdOFactor->emissionFactors());
    }

    public function testSetEmissionFactorWithoutWellToTankStoresZeroWellToTank(): void
    {
        $gdOFactor = $this->gdOFactor();

        $gdOFactor->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $wellToTankFactor = $gdOFactor->wellToTankFactorFor(2024, $this->groupId());

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
        $gdOFactor = $this->gdOFactor();

        $gdOFactor->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2024, '0.61'));

        $wellToTankFactor = $gdOFactor->wellToTankFactorFor(2024, $this->groupId());

        $this->assertNotNull($wellToTankFactor);
        $this->assertSame('0.61', $wellToTankFactor->co2eq());
        $this->assertSame('DEFRA', $wellToTankFactor->source());
        $this->assertSame('1.5', $gdOFactor->emissionFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testWellToTankFactorForReturnsNullWhenMissing(): void
    {
        $gdOFactor = $this->gdOFactor();

        $this->assertNull($gdOFactor->wellToTankFactorFor(1999, $this->groupId()));
    }

    public function testSetEmissionFactorThrowsWhenWellToTankHasDifferentYear(): void
    {
        $gdOFactor = $this->gdOFactor();

        $this->expectException(\InvalidArgumentException::class);

        $gdOFactor->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2023, '0.61'));
    }

    public function testSetEmissionFactorThrowsWhenWellToTankHasDifferentGroup(): void
    {
        $gdOFactor = $this->gdOFactor();

        $this->expectException(\InvalidArgumentException::class);

        $gdOFactor->setEmissionFactor(
            $this->emissionFactor(2024, '1.5'),
            $this->wellToTankFactor(2024, '0.61')->withEmissionFactorGroup(EmissionFactorGroupMother::random()->id()),
        );
    }

    public function testChangingOnlyWellToTankPublishesEmissionFactorUpdated(): void
    {
        $gdOFactor = $this->gdOFactor();
        $gdOFactor->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2024, '0.61'));

        DomainEventPublisher::instance()->reset();
        $spySubscriber = new SpyDomainEventSubscriber();

        $gdOFactor->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2024, '0.7'));

        $this->assertInstanceOf(GdOFactorEmissionFactorUpdated::class, $spySubscriber->lastEvent());
        $this->assertSame('0.7', $gdOFactor->wellToTankFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testSettingSameFactorsPublishesNothing(): void
    {
        $gdOFactor = $this->gdOFactor();
        $gdOFactor->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2024, '0.61'));

        DomainEventPublisher::instance()->reset();
        $spySubscriber = new SpyDomainEventSubscriber();

        $gdOFactor->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2024, '0.61'));

        $this->assertCount(0, $spySubscriber->events());
    }

    public function testSyncEmissionFactorsStoresWellToTank(): void
    {
        $gdOFactor = $this->gdOFactor();

        $gdOFactor->syncEmissionFactors([
            ['factor' => $this->emissionFactor(2024, '2.5'), 'wellToTank' => $this->wellToTankFactor(2024, '0.61')],
        ]);

        $this->assertSame('0.61', $gdOFactor->wellToTankFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testSetEmissionFactorUsesKWhUnitForWellToTank(): void
    {
        $gdOFactor = $this->gdOFactor();

        $gdOFactor->setEmissionFactor(
            $this->emissionFactor(2024, '1.5')->withUnit(Unit::Kilogram),
            $this->wellToTankFactor(2024, '0.61', Unit::Kilogram),
        );

        $this->assertSame(Unit::KWh, $gdOFactor->wellToTankFactorFor(2024, $this->groupId())?->unit());
    }

    private function gdOFactor(): GdOFactor
    {
        return new GdOFactor(
            id: GdOFactorId::generate(),
            name: 'gdo-factor-name',
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
