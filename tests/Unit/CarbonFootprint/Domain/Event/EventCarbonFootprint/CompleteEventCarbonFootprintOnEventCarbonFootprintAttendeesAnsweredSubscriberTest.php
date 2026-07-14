<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\EventCarbonFootprint;

use App\CarbonFootprint\Application\EventCarbonFootprint\Complete\CompleteEventCarbonFootprintService;
use App\CarbonFootprint\Domain\Event\EventCarbonFootprint\CompleteEventCarbonFootprintOnEventCarbonFootprintAttendeesAnsweredSubscriber;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintAttendeesAnswered;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintProvidersAnswered;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompleteEventCarbonFootprintOnEventCarbonFootprintAttendeesAnsweredSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToEventCarbonFootprintAttendeesAnswered(): void
    {
        $sut = $this->createSUT();

        $this->assertTrue($sut->isSubscribedTo(new EventCarbonFootprintAttendeesAnswered(
            eventCarbonFootprintId: EventCarbonFootprintId::generate(),
        )));
    }

    public function testIsNotSubscribedToUnrelatedEvents(): void
    {
        $sut = $this->createSUT();

        $this->assertFalse($sut->isSubscribedTo(new EventCarbonFootprintProvidersAnswered(
            eventCarbonFootprintId: EventCarbonFootprintId::generate(),
        )));
    }

    public function testHandleCallsCompleteEventCarbonFootprintService(): void
    {
        $service = $this->createMock(CompleteEventCarbonFootprintService::class);
        $service->expects($this->once())->method('__invoke');

        $sut = $this->createSUT($service);
        $sut->handle(new EventCarbonFootprintAttendeesAnswered(
            eventCarbonFootprintId: EventCarbonFootprintId::generate(),
        ));
    }

    private function createSUT(?MockObject $service = null): CompleteEventCarbonFootprintOnEventCarbonFootprintAttendeesAnsweredSubscriber
    {
        /** @var CompleteEventCarbonFootprintService&MockObject $completeEventCarbonFootprint */
        $completeEventCarbonFootprint = $service ?? $this->createMock(CompleteEventCarbonFootprintService::class);

        return new CompleteEventCarbonFootprintOnEventCarbonFootprintAttendeesAnsweredSubscriber(
            $completeEventCarbonFootprint,
            new FakeLoggedUser(UserMother::adminUser()),
        );
    }
}
