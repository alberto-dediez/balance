<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector;

use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSector;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorEmissionFactorAdded;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorEmissionFactorUpdated;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorId;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRegistered;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRemoved;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorUpdated;
use App\CarbonFootprint\Domain\Model\EconomicSector\NonCurrencyUnitException;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class EconomicSectorTest extends TestCase
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
        $economicSector = new EconomicSector(id: EconomicSectorId::generate());

        $this->assertSame(Unit::Euro, $economicSector->unit());
    }

    public function testRegisterPublishesRegisteredEvent(): void
    {
        $economicSector = EconomicSectorMother::fromName('Fabricación de productos metálicos');
        $event = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(EconomicSectorRegistered::class, $event);
        $this->assertSame('Fabricación de productos metálicos', $event->name());
        $this->assertSame(Unit::Euro, $event->unit());
        $this->assertSame(Unit::Euro, $economicSector->unit());
    }

    public function testUpdateChangesData(): void
    {
        $economicSector = EconomicSectorMother::fromName('Fabricación de productos metálicos');

        $economicSector->update('Fabricación de maquinaria', Unit::UsDollar, '28');

        $this->assertInstanceOf(EconomicSectorUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertSame('Fabricación de maquinaria', $economicSector->name());
        $this->assertSame(Unit::UsDollar, $economicSector->unit());
        $this->assertSame('28', $economicSector->classificationCode());
        $this->assertNotNull($economicSector->updatedAt());
    }

    public function testRegisterWithClassificationCode(): void
    {
        $economicSector = EconomicSectorMother::withCode('25');
        $event = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(EconomicSectorRegistered::class, $event);
        $this->assertSame('25', $event->classificationCode());
        $this->assertSame('25', $economicSector->classificationCode());
    }

    public function testRegisterWithNonCurrencyUnitThrows(): void
    {
        $economicSector = new EconomicSector(id: EconomicSectorId::generate());

        try {
            $economicSector->register('Fabricación de productos metálicos', Unit::Kilogram, null);
            $this->fail('NonCurrencyUnitException expected');
        } catch (NonCurrencyUnitException) {
            $this->assertCount(0, $this->spySubscriber->events());
        }
    }

    public function testUpdateWithNonCurrencyUnitThrows(): void
    {
        $economicSector = EconomicSectorMother::fromName('Fabricación de productos metálicos');

        $this->expectException(NonCurrencyUnitException::class);

        $economicSector->update('Fabricación de productos metálicos', Unit::Kilogram, null);
    }

    public function testRegisteredEventSurvivesPayloadRoundTrip(): void
    {
        foreach (['25', null] as $code) {
            $event = new EconomicSectorRegistered(
                economicSectorId: EconomicSectorId::generate(),
                name: 'Fabricación de productos metálicos',
                unit: Unit::Euro,
                classificationCode: $code,
            );

            $rebuilt = EconomicSectorRegistered::rebuildFromPayload($event->id(), $event->occurredOn(), $event->payload());

            $this->assertEquals($event->economicSectorId(), $rebuilt->economicSectorId());
            $this->assertSame($event->name(), $rebuilt->name());
            $this->assertSame(Unit::Euro, $rebuilt->unit());
            $this->assertSame($code, $rebuilt->classificationCode());
        }
    }

    public function testTranslateAddTranslation(): void
    {
        $economicSector = $this->economicSector();

        $this->assertCount(0, $economicSector->translations());
        $economicSector->translate('translated-name', Locale::English);
        $this->assertCount(1, $economicSector->translations());
    }

    public function testTranslateReturnOriginalNameIfNoLocaleIsSet(): void
    {
        $economicSector = $this->economicSector();
        $economicSector->translate('translated-name-en', Locale::English);
        $economicSector->translate('translated-name-it', Locale::Italian);

        $this->assertEquals('economic-sector-name', $economicSector->name());
    }

    public function testTranslateReturnTranslatedName(): void
    {
        $economicSector = $this->economicSector();
        $economicSector->translate('translated-name-en', Locale::English);
        $economicSector->translate('translated-name-it', Locale::Italian);

        FakeLocaleSetter::set(Locale::Italian);

        $this->assertEquals('translated-name-it', $economicSector->name());
    }

    public function testRemoveMarksAsRemoved(): void
    {
        $economicSector = EconomicSectorMother::registeredEconomicSector();

        $economicSector->remove();

        $this->assertInstanceOf(EconomicSectorRemoved::class, $this->spySubscriber->lastEvent());
        $this->assertTrue($economicSector->isRemoved());
        $this->assertNotNull($economicSector->removedAt());
    }

    public function testSetEmissionFactorAddsIt(): void
    {
        $economicSector = $this->economicSector();

        $economicSector->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $this->assertInstanceOf(EconomicSectorEmissionFactorAdded::class, $this->spySubscriber->lastEvent());
        $this->assertCount(1, $economicSector->emissionFactors());
        $this->assertSame('1.5', $economicSector->emissionFactorFor(2024, $this->groupId())?->emissionFactor()->co2eq());
    }

    public function testSetSameEmissionFactorDoesNotPublish(): void
    {
        $economicSector = $this->economicSector();
        $economicSector->setEmissionFactor($this->emissionFactor(2024, '1.5'));
        $eventsCount = count($this->spySubscriber->events());

        $economicSector->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $this->assertCount($eventsCount, $this->spySubscriber->events());
    }

    public function testSetEmissionFactorForSameYearUpdatesIt(): void
    {
        $economicSector = $this->economicSector();

        $economicSector->setEmissionFactor($this->emissionFactor(2024, '1.5'));
        $economicSector->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertInstanceOf(EconomicSectorEmissionFactorUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertCount(1, $economicSector->emissionFactors());
        $this->assertSame('2.0', $economicSector->emissionFactorFor(2024, $this->groupId())?->emissionFactor()->co2eq());
    }

    public function testEmissionFactorForReturnsNullWhenMissing(): void
    {
        $economicSector = $this->economicSector();

        $this->assertNull($economicSector->emissionFactorFor(1999, $this->groupId()));
    }

    public function testRemoveEmissionFactor(): void
    {
        $economicSector = $this->economicSector();
        $economicSector->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $economicSector->removeEmissionFactor(2024, $this->groupId());

        $this->assertCount(0, $economicSector->emissionFactors());
    }

    public function testRemoveMissingEmissionFactorDoesNotPublish(): void
    {
        $economicSector = $this->economicSector();

        $economicSector->removeEmissionFactor(2024, $this->groupId());

        $this->assertCount(0, $this->spySubscriber->events());
    }

    public function testSyncEmissionFactorsAddsUpdatesAndRemoves(): void
    {
        $economicSector = $this->economicSector();
        $economicSector->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $economicSector->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $economicSector->syncEmissionFactors([
            $this->emissionFactor(2024, '2.5'),
            $this->emissionFactor(2025, '3.0'),
        ]);

        $this->assertCount(2, $economicSector->emissionFactors());
        $this->assertNull($economicSector->emissionFactorFor(2023, $this->groupId()));
        $this->assertSame('2.5', $economicSector->emissionFactorFor(2024, $this->groupId())?->emissionFactor()->co2eq());
        $this->assertSame('3.0', $economicSector->emissionFactorFor(2025, $this->groupId())?->emissionFactor()->co2eq());
    }

    public function testSyncEmissionFactorsThrowsOnDuplicateYear(): void
    {
        $economicSector = $this->economicSector();

        $this->expectException(\InvalidArgumentException::class);

        $economicSector->syncEmissionFactors([
            $this->emissionFactor(2024, '1.0'),
            $this->emissionFactor(2024, '2.0'),
        ]);
    }

    public function testUpdatingUnitRestampsEmissionFactors(): void
    {
        $economicSector = $this->economicSector();
        $economicSector->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $economicSector->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $economicSector->update('economic-sector-name', Unit::UsDollar, null);

        $factor2023 = $economicSector->emissionFactorFor(2023, $this->groupId());
        $factor2024 = $economicSector->emissionFactorFor(2024, $this->groupId());

        $this->assertNotNull($factor2023);
        $this->assertNotNull($factor2024);
        $this->assertSame(Unit::UsDollar, $factor2023->emissionFactor()->unit());
        $this->assertSame(Unit::UsDollar, $factor2024->emissionFactor()->unit());
        $this->assertSame('1.0', $factor2023->emissionFactor()->co2eq());
    }

    private function economicSector(): EconomicSector
    {
        return new EconomicSector(
            id: EconomicSectorId::generate(),
            name: 'economic-sector-name',
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
