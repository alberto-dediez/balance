<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Geo\TravelMode;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRegistered;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityUpdated;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class MobilityTest extends TestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testTranslateAddTranslation(): void
    {
        $mobility = new Mobility(
            id: MobilityId::generate(),
            name: 'mobility-name',
            unit: Unit::Kilometer,
        );

        $this->assertCount(0, $mobility->translations());
        $mobility->translate('translated-name', Locale::English);
        $this->assertCount(1, $mobility->translations());
    }

    public function testTranslateReturnOriginalNameIfNoLocaleIsSet(): void
    {
        $mobility = new Mobility(
            id: MobilityId::generate(),
            name: 'mobility-name',
            unit: Unit::Kilometer,
        );
        $mobility->translate('translated-name-en', Locale::English);
        $mobility->translate('translated-name-it', Locale::Italian);
        $mobility->translate('translated-name-ge', Locale::German);

        $this->assertEquals('mobility-name', $mobility->name());
    }

    public function testTranslateReturnTranslatedName(): void
    {
        $mobility = new Mobility(
            id: MobilityId::generate(),
            name: 'mobility-name',
            unit: Unit::Kilometer,
        );
        $mobility->translate('translated-name-en', Locale::English);
        $mobility->translate('translated-name-it', Locale::Italian);
        $mobility->translate('translated-name-ge', Locale::German);

        FakeLocaleSetter::set(Locale::Italian);

        $this->assertEquals('translated-name-it', $mobility->name());
    }

    public function testSetEmissionFactorAddsIt(): void
    {
        $mobility = $this->mobility();

        $mobility->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $this->assertCount(1, $mobility->emissionFactors());
        $this->assertSame('1.5', $mobility->emissionFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testSetEmissionFactorForSameYearReplacesIt(): void
    {
        $mobility = $this->mobility();

        $mobility->setEmissionFactor($this->emissionFactor(2024, '1.5'));
        $mobility->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertCount(1, $mobility->emissionFactors());
        $this->assertSame('2.0', $mobility->emissionFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testEmissionFactorForReturnsNullWhenMissing(): void
    {
        $mobility = $this->mobility();

        $this->assertNull($mobility->emissionFactorFor(1999, $this->groupId()));
    }

    public function testSyncEmissionFactorsAddsUpdatesAndRemoves(): void
    {
        $mobility = $this->mobility();
        $mobility->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $mobility->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $mobility->syncEmissionFactors([
            $this->emissionFactor(2024, '2.5'),
            $this->emissionFactor(2025, '3.0'),
        ]);

        $this->assertCount(2, $mobility->emissionFactors());
        $this->assertNull($mobility->emissionFactorFor(2023, $this->groupId()));
        $this->assertSame('2.5', $mobility->emissionFactorFor(2024, $this->groupId())?->co2eq());
        $this->assertSame('3.0', $mobility->emissionFactorFor(2025, $this->groupId())?->co2eq());
    }

    public function testSyncEmissionFactorsThrowsOnDuplicateYear(): void
    {
        $mobility = $this->mobility();

        $this->expectException(\InvalidArgumentException::class);

        $mobility->syncEmissionFactors([
            $this->emissionFactor(2024, '1.0'),
            $this->emissionFactor(2024, '2.0'),
        ]);
    }

    public function testUpdatingMobilityUnitRestampsEmissionFactors(): void
    {
        $mobility = $this->mobility();
        $mobility->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $mobility->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertSame(Unit::Kilometer, $mobility->emissionFactorFor(2023, $this->groupId())?->unit());

        $mobility->update('mobility-name', Unit::Piece);

        $factor2023 = $mobility->emissionFactorFor(2023, $this->groupId());
        $factor2024 = $mobility->emissionFactorFor(2024, $this->groupId());

        $this->assertNotNull($factor2023);
        $this->assertNotNull($factor2024);
        $this->assertSame(Unit::Piece, $factor2023->unit());
        $this->assertSame(Unit::Piece, $factor2024->unit());
        $this->assertSame('1.0', $factor2023->co2eq());
    }

    public function testNearestYearForGroupReturnsTheSameYearWhenItHasAFactor(): void
    {
        $mobility = $this->mobility();
        $mobility->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $mobility->setEmissionFactor($this->emissionFactor(2025, '2.0'));

        $this->assertSame(2025, $mobility->emissionFactors()->nearestYearForGroup(2025, $this->groupId()));
    }

    public function testNearestYearForGroupReturnsThePreviousYearWhenItIsTheClosest(): void
    {
        $mobility = $this->mobility();
        $mobility->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $mobility->setEmissionFactor($this->emissionFactor(2028, '2.0'));

        $this->assertSame(2023, $mobility->emissionFactors()->nearestYearForGroup(2025, $this->groupId()));
    }

    public function testNearestYearForGroupReturnsTheNextYearWhenItIsTheClosest(): void
    {
        $mobility = $this->mobility();
        $mobility->setEmissionFactor($this->emissionFactor(2020, '1.0'));
        $mobility->setEmissionFactor($this->emissionFactor(2026, '2.0'));

        $this->assertSame(2026, $mobility->emissionFactors()->nearestYearForGroup(2025, $this->groupId()));
    }

    public function testNearestYearForGroupPrefersThePreviousYearOnATie(): void
    {
        $mobility = $this->mobility();
        $mobility->setEmissionFactor($this->emissionFactor(2026, '2.0'));
        $mobility->setEmissionFactor($this->emissionFactor(2024, '1.0'));

        $this->assertSame(2024, $mobility->emissionFactors()->nearestYearForGroup(2025, $this->groupId()));
    }

    public function testNearestYearForGroupIgnoresOtherGroups(): void
    {
        $mobility = $this->mobility();
        $mobility->setEmissionFactor($this->emissionFactor(2024, '1.0'));

        $this->assertNull($mobility->emissionFactors()->nearestYearForGroup(2024, EmissionFactorGroupMother::random()->id()));
    }

    public function testNearestYearForGroupIsNullWithoutFactors(): void
    {
        $this->assertNull($this->mobility()->emissionFactors()->nearestYearForGroup(2025, $this->groupId()));
    }

    public function testRemoveEmissionFactor(): void
    {
        $mobility = $this->mobility();
        $mobility->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $mobility->removeEmissionFactor(2024, $this->groupId());

        $this->assertCount(0, $mobility->emissionFactors());
    }

    public function testDefaultUnitIsKilometer(): void
    {
        $mobility = new Mobility(id: MobilityId::generate());

        $this->assertSame(Unit::Kilometer, $mobility->unit());
    }

    public function testTravelModeIsNullByDefault(): void
    {
        $mobility = new Mobility(id: MobilityId::generate());

        $this->assertNull($mobility->travelMode());
    }

    public function testRegisterAppliesTheTravelMode(): void
    {
        $mobility = new Mobility(id: MobilityId::generate());

        $mobility->register('mobility-name', Unit::Kilometer, TravelMode::Flight);

        $this->assertSame(TravelMode::Flight, $mobility->travelMode());

        $event = $this->firstEvent($mobility, MobilityRegistered::class);

        $this->assertSame(TravelMode::Flight, $event->travelMode());
        $this->assertSame(TravelMode::Flight->value, $event->payload()['travelMode']);
    }

    public function testRegisterWithoutTravelModeKeepsItNull(): void
    {
        $mobility = new Mobility(id: MobilityId::generate());

        $mobility->register('mobility-name', Unit::Kilometer);

        $this->assertNull($mobility->travelMode());
        $this->assertNull($this->firstEvent($mobility, MobilityRegistered::class)->payload()['travelMode']);
    }

    public function testUpdateAppliesTheTravelMode(): void
    {
        $mobility = new Mobility(id: MobilityId::generate());
        $mobility->register('mobility-name', Unit::Kilometer, TravelMode::Transit);

        $mobility->update('mobility-name', Unit::Kilometer, TravelMode::Driving);

        $this->assertSame(TravelMode::Driving, $mobility->travelMode());

        $event = $this->firstEvent($mobility, MobilityUpdated::class);

        $this->assertSame(TravelMode::Driving, $event->travelMode());
        $this->assertSame(TravelMode::Driving->value, $event->payload()['travelMode']);
    }

    public function testUpdateWithoutTravelModeClearsIt(): void
    {
        $mobility = new Mobility(id: MobilityId::generate());
        $mobility->register('mobility-name', Unit::Kilometer, TravelMode::Transit);

        $mobility->update('mobility-name', Unit::Kilometer);

        $this->assertNull($mobility->travelMode());
        $this->assertNull($this->firstEvent($mobility, MobilityUpdated::class)->payload()['travelMode']);
    }

    public function testRebuildFromRegisteredEventRestoresTheTravelMode(): void
    {
        $mobilityId = MobilityId::generate();
        $event = MobilityRegistered::rebuildFromPayload(
            id: DomainEventId::generate(),
            occurredOn: new \DateTimeImmutable('2024-01-01 10:00:00'),
            payload: [
                'mobilityId' => (string) $mobilityId,
                'name' => 'mobility-name',
                'unit' => Unit::Kilometer->value,
                'travelMode' => TravelMode::Cycling->value,
            ],
        );

        $mobility = new Mobility(id: $mobilityId, events: [(string) $event->id() => $event]);
        $mobility->rebuild();

        $this->assertSame('mobility-name', $mobility->name());
        $this->assertSame(Unit::Kilometer, $mobility->unit());
        $this->assertSame(TravelMode::Cycling, $mobility->travelMode());
    }

    public function testRebuildFromUpdatedEventRestoresTheTravelMode(): void
    {
        $mobilityId = MobilityId::generate();
        $event = MobilityUpdated::rebuildFromPayload(
            id: DomainEventId::generate(),
            occurredOn: new \DateTimeImmutable('2024-02-01 10:00:00'),
            payload: [
                'mobilityId' => (string) $mobilityId,
                'name' => 'updated-name',
                'unit' => Unit::Piece->value,
                'travelMode' => TravelMode::Walking->value,
            ],
        );

        $mobility = new Mobility(id: $mobilityId, travelMode: TravelMode::Flight, events: [(string) $event->id() => $event]);
        $mobility->rebuild();

        $this->assertSame('updated-name', $mobility->name());
        $this->assertSame(Unit::Piece, $mobility->unit());
        $this->assertSame(TravelMode::Walking, $mobility->travelMode());
    }

    public function testRebuildFromEventWithoutTravelModeLeavesItNull(): void
    {
        $mobilityId = MobilityId::generate();
        $event = MobilityRegistered::rebuildFromPayload(
            id: DomainEventId::generate(),
            occurredOn: new \DateTimeImmutable('2024-01-01 10:00:00'),
            payload: [
                'mobilityId' => (string) $mobilityId,
                'name' => 'mobility-name',
                'unit' => Unit::Kilometer->value,
            ],
        );

        $mobility = new Mobility(id: $mobilityId, events: [(string) $event->id() => $event]);
        $mobility->rebuild();

        $this->assertNull($mobility->travelMode());
    }

    /**
     * @template T of DomainEvent
     *
     * @param class-string<T> $eventClass
     *
     * @return T
     */
    private function firstEvent(Mobility $mobility, string $eventClass): object
    {
        $events = array_values(array_filter(
            $mobility->recordedEvents(),
            static fn($event) => $event instanceof $eventClass,
        ));

        $this->assertNotEmpty($events);

        return $events[0];
    }

    private function mobility(): Mobility
    {
        return new Mobility(
            id: MobilityId::generate(),
            name: 'mobility-name',
            unit: Unit::Kilometer,
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
            unit: Unit::Kilometer,
            source: 'test-source',
        );
    }

    private ?EmissionFactorGroupId $groupId = null;

    private function groupId(): EmissionFactorGroupId
    {
        return $this->groupId ??= EmissionFactorGroupMother::default()->id();
    }
}
