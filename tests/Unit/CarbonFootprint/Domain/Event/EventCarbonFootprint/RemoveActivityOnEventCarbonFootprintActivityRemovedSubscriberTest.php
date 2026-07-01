<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\EventCarbonFootprint;

use App\CarbonFootprint\Application\Activity\Remove\RemoveActivityService;
use App\CarbonFootprint\Domain\Event\EventCarbonFootprint\RemoveActivityOnEventCarbonFootprintActivityRemovedSubscriber;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\MaterialActivityRegistered;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintActivityRemoved;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RemoveActivityOnEventCarbonFootprintActivityRemovedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToEventCarbonFootprintActivityRemoved(): void
    {
        $sut = $this->createSUT();

        $this->assertTrue($sut->isSubscribedTo(new EventCarbonFootprintActivityRemoved(
            eventCarbonFootprintId: EventCarbonFootprintId::generate(),
            activityId: ActivityId::generate(),
        )));
    }

    public function testIsNotSubscribedToUnrelatedEvents(): void
    {
        $sut = $this->createSUT();

        $this->assertFalse($sut->isSubscribedTo(new MaterialActivityRegistered(
            activityId: ActivityId::generate(),
            materialId: \App\CarbonFootprint\Domain\Model\Material\MaterialId::generate(),
            condition: \App\CarbonFootprint\Domain\Model\Activity\ItemCondition::New,
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(),
        )));
    }

    public function testHandleCallsRemoveActivityService(): void
    {
        $activityId = ActivityId::generate();

        $service = $this->createMock(RemoveActivityService::class);
        $service->expects($this->once())->method('__invoke');

        $sut = $this->createSUT($service);
        $sut->handle(new EventCarbonFootprintActivityRemoved(
            eventCarbonFootprintId: EventCarbonFootprintId::generate(),
            activityId: $activityId,
        ));
    }

    private function createSUT(?MockObject $service = null): RemoveActivityOnEventCarbonFootprintActivityRemovedSubscriber
    {
        /** @var RemoveActivityService&MockObject $removeActivity */
        $removeActivity = $service ?? $this->createMock(RemoveActivityService::class);

        return new RemoveActivityOnEventCarbonFootprintActivityRemovedSubscriber(
            $removeActivity,
            new FakeLoggedUser(UserMother::adminUser()),
        );
    }
}
