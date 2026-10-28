<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ReportedEmissionsActivity;
use App\CarbonFootprint\Domain\Model\Activity\ReportedEmissionsActivityRegistered;
use App\CarbonFootprint\Domain\Model\Activity\ReportedEmissionsActivityRemoved;
use App\CarbonFootprint\Domain\Model\Activity\ReportedEmissionsActivityUpdated;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\IncompatibleUnitsException;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ReportedEmissionsActivityTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRegisterPublishesRegisteredEvent(): void
    {
        $activity = ReportedEmissionsActivityMother::registered(description: '  Reparto 2025  ');
        $event = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(ReportedEmissionsActivityRegistered::class, $event);
        $this->assertSame('Reparto 2025', $activity->description());
        $this->assertSame('3200', $activity->emissions()->co2eq());
        $this->assertSame('reported', $activity->emissionFactor()->source());
        $this->assertEquals($event->occurredOn(), $activity->registeredAt());
    }

    public function testRegisterKeepsTonnesQuantity(): void
    {
        $activity = ReportedEmissionsActivity::register(
            id: ActivityId::generate(),
            accountId: new AccountId('account-id'),
            description: 'Última milla',
            emissionFactor: ReportedEmissionsActivityMother::identityEmissionFactor(),
            emissions: new Emissions('1200', '1200', '0', '0'),
            quantity: new Quantity(1.2, Unit::Tonne),
        );

        $this->assertSame(1.2, $activity->quantity()->value());
        $this->assertSame(Unit::Tonne, $activity->quantity()->unit());
        $this->assertSame('1200', $activity->emissions()->co2eq());
    }

    public function testRegisterThrowsWithEmptyDescription(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ReportedEmissionsActivityMother::registered(description: '   ');
    }

    public function testRegisterThrowsWithTooLongDescription(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ReportedEmissionsActivityMother::registered(description: str_repeat('a', 256));
    }

    public function testRegisterThrowsWithNonMassUnit(): void
    {
        $this->expectException(IncompatibleUnitsException::class);

        ReportedEmissionsActivity::register(
            id: ActivityId::generate(),
            accountId: new AccountId('account-id'),
            description: 'Reparto',
            emissionFactor: ReportedEmissionsActivityMother::identityEmissionFactor(),
            emissions: new Emissions('10', '10', '0', '0'),
            quantity: new Quantity(10.0, Unit::Gram),
        );
    }

    public function testUpdatePublishesUpdatedEvent(): void
    {
        $activity = ReportedEmissionsActivityMother::registered();

        $activity->update(
            description: 'Última milla',
            emissionFactor: ReportedEmissionsActivityMother::identityEmissionFactor(),
            emissions: new Emissions('800', '800', '0', '0'),
            quantity: new Quantity(0.8, Unit::Tonne),
        );

        $this->assertInstanceOf(ReportedEmissionsActivityUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertSame('Última milla', $activity->description());
        $this->assertSame(0.8, $activity->quantity()->value());
        $this->assertNotNull($activity->updatedAt());
    }

    public function testUpdateThrowsWithEmptyDescription(): void
    {
        $activity = ReportedEmissionsActivityMother::registered();

        $this->expectException(\InvalidArgumentException::class);

        $activity->update(
            description: '',
            emissionFactor: ReportedEmissionsActivityMother::identityEmissionFactor(),
            emissions: new Emissions('800', '800', '0', '0'),
            quantity: new Quantity(800.0, Unit::Kilogram),
        );
    }

    public function testRemovePublishesRemovedEvent(): void
    {
        $activity = ReportedEmissionsActivityMother::registered();

        $activity->remove();

        $this->assertInstanceOf(ReportedEmissionsActivityRemoved::class, $this->spySubscriber->lastEvent());
    }

    public function testRegisteredEventRebuildsFromPayload(): void
    {
        ReportedEmissionsActivityMother::registered();
        $event = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(ReportedEmissionsActivityRegistered::class, $event);

        $rebuilt = ReportedEmissionsActivityRegistered::rebuildFromPayload(
            DomainEventId::generate(),
            $event->occurredOn(),
            $event->payload(),
        );

        $this->assertSame('Reparto 2025', $rebuilt->description());
        $this->assertSame(3200.0, $rebuilt->quantity()->value());
        $this->assertSame('reported', $rebuilt->emissionFactor()->source());
    }
}
