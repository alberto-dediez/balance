<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood;

use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGood;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodCategory;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodEmissionFactorAdded;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodEmissionFactorUpdated;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRegistered;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRemoved;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodUpdated;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class CapitalGoodTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    private ?EmissionFactorGroupId $groupId = null;

    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testDefaultUnitIsEuro(): void
    {
        $capitalGood = new CapitalGood(id: CapitalGoodId::generate());

        $this->assertSame(Unit::Euro, $capitalGood->unit());
        $this->assertSame(CapitalGoodCategory::Others, $capitalGood->category());
    }

    public function testRegisterPublishesRegisteredEvent(): void
    {
        $capitalGood = CapitalGoodMother::fromName('Consultoría');
        $event = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(CapitalGoodRegistered::class, $event);
        $this->assertSame('Consultoría', $event->name());
        $this->assertSame(Unit::Euro, $event->unit());
        $this->assertSame(Unit::Euro, $capitalGood->unit());
        $this->assertSame(CapitalGoodCategory::Renovations, $capitalGood->category());
    }

    public function testUpdateChangesData(): void
    {
        $capitalGood = CapitalGoodMother::fromName('Consultoría');

        $capitalGood->update('Publicidad', CapitalGoodCategory::FurnitureAndEquipment, Unit::UsDollar);

        $this->assertInstanceOf(CapitalGoodUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertSame('Publicidad', $capitalGood->name());
        $this->assertSame(CapitalGoodCategory::FurnitureAndEquipment, $capitalGood->category());
        $this->assertSame(Unit::UsDollar, $capitalGood->unit());
        $this->assertNotNull($capitalGood->updatedAt());
    }

    public function testTranslateAddTranslation(): void
    {
        $capitalGood = $this->capitalGood();

        $this->assertCount(0, $capitalGood->translations());
        $capitalGood->translate('translated-name', Locale::English);
        $this->assertCount(1, $capitalGood->translations());
    }

    public function testTranslateReturnOriginalNameIfNoLocaleIsSet(): void
    {
        $capitalGood = $this->capitalGood();
        $capitalGood->translate('translated-name-en', Locale::English);
        $capitalGood->translate('translated-name-it', Locale::Italian);

        $this->assertEquals('capital-good-name', $capitalGood->name());
    }

    public function testTranslateReturnTranslatedName(): void
    {
        $capitalGood = $this->capitalGood();
        $capitalGood->translate('translated-name-en', Locale::English);
        $capitalGood->translate('translated-name-it', Locale::Italian);

        FakeLocaleSetter::set(Locale::Italian);

        $this->assertEquals('translated-name-it', $capitalGood->name());
    }

    public function testRemoveMarksAsRemoved(): void
    {
        $capitalGood = CapitalGoodMother::registeredCapitalGood();

        $capitalGood->remove();

        $this->assertInstanceOf(CapitalGoodRemoved::class, $this->spySubscriber->lastEvent());
        $this->assertTrue($capitalGood->isRemoved());
        $this->assertNotNull($capitalGood->removedAt());
    }

    public function testSetEmissionFactorAddsIt(): void
    {
        $capitalGood = $this->capitalGood();

        $capitalGood->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $this->assertInstanceOf(CapitalGoodEmissionFactorAdded::class, $this->spySubscriber->lastEvent());
        $this->assertCount(1, $capitalGood->emissionFactors());
        $this->assertSame('1.5', $capitalGood->emissionFactorFor(2024, $this->groupId())?->emissionFactor()->co2eq());
    }

    public function testSetSameEmissionFactorDoesNotPublish(): void
    {
        $capitalGood = $this->capitalGood();
        $capitalGood->setEmissionFactor($this->emissionFactor(2024, '1.5'));
        $eventsCount = count($this->spySubscriber->events());

        $capitalGood->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $this->assertCount($eventsCount, $this->spySubscriber->events());
    }

    public function testSetEmissionFactorForSameYearUpdatesIt(): void
    {
        $capitalGood = $this->capitalGood();

        $capitalGood->setEmissionFactor($this->emissionFactor(2024, '1.5'));
        $capitalGood->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertInstanceOf(CapitalGoodEmissionFactorUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertCount(1, $capitalGood->emissionFactors());
        $this->assertSame('2.0', $capitalGood->emissionFactorFor(2024, $this->groupId())?->emissionFactor()->co2eq());
    }

    public function testEmissionFactorForReturnsNullWhenMissing(): void
    {
        $capitalGood = $this->capitalGood();

        $this->assertNull($capitalGood->emissionFactorFor(1999, $this->groupId()));
    }

    public function testRemoveEmissionFactor(): void
    {
        $capitalGood = $this->capitalGood();
        $capitalGood->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $capitalGood->removeEmissionFactor(2024, $this->groupId());

        $this->assertCount(0, $capitalGood->emissionFactors());
    }

    public function testRemoveMissingEmissionFactorDoesNotPublish(): void
    {
        $capitalGood = $this->capitalGood();

        $capitalGood->removeEmissionFactor(2024, $this->groupId());

        $this->assertCount(0, $this->spySubscriber->events());
    }

    public function testSyncEmissionFactorsAddsUpdatesAndRemoves(): void
    {
        $capitalGood = $this->capitalGood();
        $capitalGood->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $capitalGood->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $capitalGood->syncEmissionFactors([
            $this->emissionFactor(2024, '2.5'),
            $this->emissionFactor(2025, '3.0'),
        ]);

        $this->assertCount(2, $capitalGood->emissionFactors());
        $this->assertNull($capitalGood->emissionFactorFor(2023, $this->groupId()));
        $this->assertSame('2.5', $capitalGood->emissionFactorFor(2024, $this->groupId())?->emissionFactor()->co2eq());
        $this->assertSame('3.0', $capitalGood->emissionFactorFor(2025, $this->groupId())?->emissionFactor()->co2eq());
    }

    public function testSyncEmissionFactorsThrowsOnDuplicateYear(): void
    {
        $capitalGood = $this->capitalGood();

        $this->expectException(\InvalidArgumentException::class);

        $capitalGood->syncEmissionFactors([
            $this->emissionFactor(2024, '1.0'),
            $this->emissionFactor(2024, '2.0'),
        ]);
    }

    public function testUpdatingUnitRestampsEmissionFactors(): void
    {
        $capitalGood = $this->capitalGood();
        $capitalGood->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $capitalGood->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $capitalGood->update('capital-good-name', CapitalGoodCategory::Others, Unit::UsDollar);

        $factor2023 = $capitalGood->emissionFactorFor(2023, $this->groupId());
        $factor2024 = $capitalGood->emissionFactorFor(2024, $this->groupId());

        $this->assertNotNull($factor2023);
        $this->assertNotNull($factor2024);
        $this->assertSame(Unit::UsDollar, $factor2023->emissionFactor()->unit());
        $this->assertSame(Unit::UsDollar, $factor2024->emissionFactor()->unit());
        $this->assertSame('1.0', $factor2023->emissionFactor()->co2eq());
    }

    public function testUpdatingUnitToSquareMeterRestampsEmissionFactors(): void
    {
        $capitalGood = $this->capitalGood();
        $capitalGood->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $capitalGood->update('capital-good-name', CapitalGoodCategory::Renovations, Unit::SquareMeter);

        $factor = $capitalGood->emissionFactorFor(2024, $this->groupId());

        $this->assertSame(Unit::SquareMeter, $capitalGood->unit());
        $this->assertNotNull($factor);
        $this->assertSame(Unit::SquareMeter, $factor->emissionFactor()->unit());
    }

    private function capitalGood(): CapitalGood
    {
        return new CapitalGood(
            id: CapitalGoodId::generate(),
            name: 'capital-good-name',
            category: CapitalGoodCategory::Others,
            unit: Unit::Euro,
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
            unit: Unit::Euro,
            source: 'test-source',
        );
    }

    private function groupId(): EmissionFactorGroupId
    {
        return $this->groupId ??= EmissionFactorGroupMother::defaultId();
    }
}
