<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\CapitalGoodActivityRegistered;
use App\CarbonFootprint\Domain\Model\Activity\CapitalGoodActivityRemoved;
use App\CarbonFootprint\Domain\Model\Activity\CapitalGoodActivityUpdated;
use App\CarbonFootprint\Domain\Model\Activity\InvalidAcquisitionYearException;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class CapitalGoodActivityTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRegisterPublishesRegisteredEvent(): void
    {
        $activity = CapitalGoodActivityMother::registered(acquisitionYear: 2020);
        $event = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(CapitalGoodActivityRegistered::class, $event);
        $this->assertTrue($event->capitalGoodId()->equals($activity->capitalGoodId()));
        $this->assertSame(2020, $activity->acquisitionYear());
        $this->assertSame(Unit::Euro, $activity->emissionFactor()->unit());
        $this->assertSame('15.0', $activity->emissions()->co2eq());
        $this->assertSame(100.0, $activity->quantity()->value());
        $this->assertSame(Unit::Euro, $activity->quantity()->unit());
        $this->assertEquals($event->occurredOn(), $activity->registeredAt());
    }

    public function testRegisterWithoutAcquisitionYearLeavesItNull(): void
    {
        $activity = CapitalGoodActivityMother::registered();

        $this->assertNull($activity->acquisitionYear());
    }

    /**
     * @dataProvider acquisitionYearBoundaries
     */
    public function testRegisterAcceptsAcquisitionYearBoundaries(int $acquisitionYear): void
    {
        $activity = CapitalGoodActivityMother::registered(acquisitionYear: $acquisitionYear);

        $this->assertSame($acquisitionYear, $activity->acquisitionYear());
    }

    /**
     * @dataProvider outOfRangeAcquisitionYears
     */
    public function testRegisterRejectsOutOfRangeAcquisitionYear(int $acquisitionYear): void
    {
        $this->expectException(InvalidAcquisitionYearException::class);

        CapitalGoodActivityMother::registered(acquisitionYear: $acquisitionYear);
    }

    /**
     * @dataProvider outOfRangeAcquisitionYears
     */
    public function testUpdateRejectsOutOfRangeAcquisitionYearWithoutChangingTheActivity(int $acquisitionYear): void
    {
        $activity = CapitalGoodActivityMother::registered(acquisitionYear: 2020);
        $capitalGoodId = $activity->capitalGoodId();

        try {
            $activity->update(
                capitalGoodId: CapitalGoodId::generate(),
                emissionFactor: ActivityMother::emissionFactor()->withUnit(Unit::Euro),
                emissions: ActivityMother::emissions(),
                quantity: ActivityMother::quantity(Unit::Euro, 300.0),
                acquisitionYear: $acquisitionYear,
            );
            $this->fail('An out of range acquisition year must be rejected.');
        } catch (InvalidAcquisitionYearException) {
        }

        $this->assertInstanceOf(CapitalGoodActivityRegistered::class, $this->spySubscriber->lastEvent());
        $this->assertTrue($capitalGoodId->equals($activity->capitalGoodId()));
        $this->assertSame(2020, $activity->acquisitionYear());
    }

    /** @return array<string, array{int}> */
    public static function acquisitionYearBoundaries(): array
    {
        return [
            'minimum' => [1900],
            'maximum' => [2200],
        ];
    }

    /** @return array<string, array{int}> */
    public static function outOfRangeAcquisitionYears(): array
    {
        return [
            'below minimum' => [1899],
            'above maximum' => [2201],
            'negative' => [-5],
        ];
    }

    public function testUpdatePublishesUpdatedEvent(): void
    {
        $activity = CapitalGoodActivityMother::registered(acquisitionYear: 2020);
        $capitalGoodId = CapitalGoodId::generate();

        $activity->update(
            capitalGoodId: $capitalGoodId,
            emissionFactor: ActivityMother::emissionFactor()->withUnit(Unit::Euro),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::UsDollar, 300.0),
            acquisitionYear: 2022,
        );

        $this->assertInstanceOf(CapitalGoodActivityUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertTrue($capitalGoodId->equals($activity->capitalGoodId()));
        $this->assertSame(300.0, $activity->quantity()->value());
        $this->assertSame(Unit::UsDollar, $activity->quantity()->unit());
        $this->assertSame(2022, $activity->acquisitionYear());
        $this->assertNotNull($activity->updatedAt());
    }

    public function testRemovePublishesRemovedEvent(): void
    {
        $activity = CapitalGoodActivityMother::registered();

        $activity->remove();

        $this->assertInstanceOf(CapitalGoodActivityRemoved::class, $this->spySubscriber->lastEvent());
    }

    public function testRegisteredEventRebuildsFromPayload(): void
    {
        $activity = CapitalGoodActivityMother::registered(acquisitionYear: 2021);
        $event = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(CapitalGoodActivityRegistered::class, $event);

        $rebuilt = CapitalGoodActivityRegistered::rebuildFromPayload(
            DomainEventId::generate(),
            $event->occurredOn(),
            $event->payload(),
        );

        $this->assertTrue($rebuilt->capitalGoodId()->equals($activity->capitalGoodId()));
        $this->assertSame(Unit::Euro, $rebuilt->quantity()->unit());
        $this->assertSame(2021, $rebuilt->acquisitionYear());
    }

    public function testRegisteredEventRebuildsFromPayloadWithoutAcquisitionYear(): void
    {
        CapitalGoodActivityMother::registered();
        $event = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(CapitalGoodActivityRegistered::class, $event);

        $payload = $event->payload();
        unset($payload['acquisitionYear']);

        $rebuilt = CapitalGoodActivityRegistered::rebuildFromPayload(
            DomainEventId::generate(),
            $event->occurredOn(),
            $payload,
        );

        $this->assertNull($rebuilt->acquisitionYear());
    }
}
