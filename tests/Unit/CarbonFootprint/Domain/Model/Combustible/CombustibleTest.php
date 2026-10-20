<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible;

use App\CarbonFootprint\Domain\Model\Combustible\Combustible;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleEmissionFactorUpdated;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class CombustibleTest extends TestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testTranslateAddTranslation(): void
    {
        $combustible = new Combustible(
            id: CombustibleId::generate(),
            name: 'combustible-name',
            unit: Unit::Kilogram,
        );

        $this->assertCount(0, $combustible->translations());
        $combustible->translate('translated-name', Locale::English);
        $this->assertCount(1, $combustible->translations());
    }

    public function testTranslateReturnOriginalNameIfNoLocaleIsSet(): void
    {
        $combustible = new Combustible(
            id: CombustibleId::generate(),
            name: 'combustible-name',
            unit: Unit::Kilogram,
        );
        $combustible->translate('translated-name-en', Locale::English);
        $combustible->translate('translated-name-it', Locale::Italian);
        $combustible->translate('translated-name-ge', Locale::German);

        $this->assertEquals('combustible-name', $combustible->name());
    }

    public function testTranslateReturnTranslatedName(): void
    {
        $combustible = new Combustible(
            id: CombustibleId::generate(),
            name: 'combustible-name',
            unit: Unit::Kilogram,
        );
        $combustible->translate('translated-name-en', Locale::English);
        $combustible->translate('translated-name-it', Locale::Italian);
        $combustible->translate('translated-name-ge', Locale::German);

        FakeLocaleSetter::set(Locale::Italian);

        $this->assertEquals('translated-name-it', $combustible->name());
    }

    public function testSetEmissionFactorAddsIt(): void
    {
        $combustible = $this->combustible();

        $combustible->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $this->assertCount(1, $combustible->emissionFactors());
        $this->assertSame('1.5', $combustible->emissionFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testSetEmissionFactorForSameYearReplacesIt(): void
    {
        $combustible = $this->combustible();

        $combustible->setEmissionFactor($this->emissionFactor(2024, '1.5'));
        $combustible->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertCount(1, $combustible->emissionFactors());
        $this->assertSame('2.0', $combustible->emissionFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testEmissionFactorForReturnsNullWhenMissing(): void
    {
        $combustible = $this->combustible();

        $this->assertNull($combustible->emissionFactorFor(1999, $this->groupId()));
    }

    public function testSyncEmissionFactorsAddsUpdatesAndRemoves(): void
    {
        $combustible = $this->combustible();
        $combustible->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $combustible->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $combustible->syncEmissionFactors([
            ['factor' => $this->emissionFactor(2024, '2.5'), 'wellToTank' => $this->wellToTankFactor(2024, '0.5')],
            ['factor' => $this->emissionFactor(2025, '3.0'), 'wellToTank' => $this->wellToTankFactor(2025, '0.5')],
        ]);

        $this->assertCount(2, $combustible->emissionFactors());
        $this->assertNull($combustible->emissionFactorFor(2023, $this->groupId()));
        $this->assertSame('2.5', $combustible->emissionFactorFor(2024, $this->groupId())?->co2eq());
        $this->assertSame('3.0', $combustible->emissionFactorFor(2025, $this->groupId())?->co2eq());
    }

    public function testSyncEmissionFactorsThrowsOnDuplicateYear(): void
    {
        $combustible = $this->combustible();

        $this->expectException(\InvalidArgumentException::class);

        $combustible->syncEmissionFactors([
            ['factor' => $this->emissionFactor(2024, '1.0'), 'wellToTank' => $this->wellToTankFactor(2024, '0.5')],
            ['factor' => $this->emissionFactor(2024, '2.0'), 'wellToTank' => $this->wellToTankFactor(2024, '0.5')],
        ]);
    }

    public function testUpdatingCombustibleUnitRestampsEmissionFactors(): void
    {
        $combustible = $this->combustible();
        $combustible->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $combustible->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertSame(Unit::Kilogram, $combustible->emissionFactorFor(2023, $this->groupId())?->unit());

        $combustible->update('combustible-name', Unit::Piece);

        $factor2023 = $combustible->emissionFactorFor(2023, $this->groupId());
        $factor2024 = $combustible->emissionFactorFor(2024, $this->groupId());

        $this->assertNotNull($factor2023);
        $this->assertNotNull($factor2024);
        $this->assertSame(Unit::Piece, $factor2023->unit());
        $this->assertSame(Unit::Piece, $factor2024->unit());
        $this->assertSame('1.0', $factor2023->co2eq());
    }

    public function testRemoveEmissionFactor(): void
    {
        $combustible = $this->combustible();
        $combustible->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $combustible->removeEmissionFactor(2024, $this->groupId());

        $this->assertCount(0, $combustible->emissionFactors());
    }

    public function testSetEmissionFactorWithoutWellToTankStoresZeroWellToTank(): void
    {
        $combustible = $this->combustible();

        $combustible->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $wellToTankFactor = $combustible->wellToTankFactorFor(2024, $this->groupId());

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
        $combustible = $this->combustible();

        $combustible->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2024, '0.61'));

        $wellToTankFactor = $combustible->wellToTankFactorFor(2024, $this->groupId());

        $this->assertNotNull($wellToTankFactor);
        $this->assertSame('0.61', $wellToTankFactor->co2eq());
        $this->assertSame('DEFRA', $wellToTankFactor->source());
        $this->assertSame('1.5', $combustible->emissionFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testWellToTankFactorForReturnsNullWhenMissing(): void
    {
        $combustible = $this->combustible();

        $this->assertNull($combustible->wellToTankFactorFor(1999, $this->groupId()));
    }

    public function testSetEmissionFactorThrowsWhenWellToTankHasDifferentYear(): void
    {
        $combustible = $this->combustible();

        $this->expectException(\InvalidArgumentException::class);

        $combustible->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2023, '0.61'));
    }

    public function testSetEmissionFactorThrowsWhenWellToTankHasDifferentGroup(): void
    {
        $combustible = $this->combustible();

        $this->expectException(\InvalidArgumentException::class);

        $combustible->setEmissionFactor(
            $this->emissionFactor(2024, '1.5'),
            $this->wellToTankFactor(2024, '0.61')->withEmissionFactorGroup(EmissionFactorGroupMother::random()->id()),
        );
    }

    public function testChangingOnlyWellToTankPublishesEmissionFactorUpdated(): void
    {
        $combustible = $this->combustible();
        $combustible->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2024, '0.61'));

        DomainEventPublisher::instance()->reset();
        $spySubscriber = new SpyDomainEventSubscriber();

        $combustible->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2024, '0.7'));

        $this->assertInstanceOf(CombustibleEmissionFactorUpdated::class, $spySubscriber->lastEvent());
        $this->assertSame('0.7', $combustible->wellToTankFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testSettingSameFactorsPublishesNothing(): void
    {
        $combustible = $this->combustible();
        $combustible->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2024, '0.61'));

        DomainEventPublisher::instance()->reset();
        $spySubscriber = new SpyDomainEventSubscriber();

        $combustible->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->wellToTankFactor(2024, '0.61'));

        $this->assertCount(0, $spySubscriber->events());
    }

    public function testSyncEmissionFactorsStoresWellToTank(): void
    {
        $combustible = $this->combustible();

        $combustible->syncEmissionFactors([
            ['factor' => $this->emissionFactor(2024, '2.5'), 'wellToTank' => $this->wellToTankFactor(2024, '0.61')],
        ]);

        $this->assertSame('0.61', $combustible->wellToTankFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testUpdatingUnitRestampsWellToTankFactors(): void
    {
        $combustible = $this->combustible();
        $combustible->setEmissionFactor($this->emissionFactor(2024, '2.0'), $this->wellToTankFactor(2024, '0.61'));

        $combustible->update('combustible-name', Unit::Piece);

        $wellToTankFactor = $combustible->wellToTankFactorFor(2024, $this->groupId());

        $this->assertNotNull($wellToTankFactor);
        $this->assertSame(Unit::Piece, $wellToTankFactor->unit());
        $this->assertSame('0.61', $wellToTankFactor->co2eq());
    }

    private function combustible(): Combustible
    {
        return new Combustible(
            id: CombustibleId::generate(),
            name: 'combustible-name',
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
