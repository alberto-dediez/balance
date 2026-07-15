<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\EventCarbonFootprint;

use App\CarbonFootprint\Application\EventCarbonFootprintReport\Remove\RemoveEventCarbonFootprintReportService;
use App\CarbonFootprint\Domain\Event\EventCarbonFootprint\RemoveReportOnEventCarbonFootprintReopenedSubscriber;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintProvidersAnswered;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintReopened;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RemoveReportOnEventCarbonFootprintReopenedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToEventCarbonFootprintReopened(): void
    {
        $sut = $this->createSUT($this->createMock(RemoveEventCarbonFootprintReportService::class));

        $this->assertTrue($sut->isSubscribedTo(new EventCarbonFootprintReopened(
            eventCarbonFootprintId: EventCarbonFootprintId::generate(),
        )));
    }

    public function testIsNotSubscribedToUnrelatedEvents(): void
    {
        $sut = $this->createSUT($this->createMock(RemoveEventCarbonFootprintReportService::class));

        $this->assertFalse($sut->isSubscribedTo(new EventCarbonFootprintProvidersAnswered(
            eventCarbonFootprintId: EventCarbonFootprintId::generate(),
        )));
    }

    public function testHandleRemovesTheExistingReport(): void
    {
        $eventCarbonFootprintId = EventCarbonFootprintId::generate();
        $report = EventCarbonFootprintReportMother::generated((string) $eventCarbonFootprintId);

        $service = $this->createMock(RemoveEventCarbonFootprintReportService::class);
        $service->expects($this->once())->method('__invoke');

        $sut = $this->createSUT($service, $report);
        $sut->handle(new EventCarbonFootprintReopened(eventCarbonFootprintId: $eventCarbonFootprintId));
    }

    public function testHandleDoesNothingWhenThereIsNoReport(): void
    {
        $service = $this->createMock(RemoveEventCarbonFootprintReportService::class);
        $service->expects($this->never())->method('__invoke');

        $sut = $this->createSUT($service);
        $sut->handle(new EventCarbonFootprintReopened(eventCarbonFootprintId: EventCarbonFootprintId::generate()));
    }

    /** @param RemoveEventCarbonFootprintReportService&MockObject $service */
    private function createSUT(
        MockObject $service,
        mixed $report = null,
    ): RemoveReportOnEventCarbonFootprintReopenedSubscriber {
        $reportRepository = EventCarbonFootprintReportRepositoryMother::inMemory();

        if (null !== $report) {
            $reportRepository->add($report);
        }

        return new RemoveReportOnEventCarbonFootprintReopenedSubscriber(
            $service,
            $reportRepository,
            new FakeLoggedUser(UserMother::adminUser()),
        );
    }
}
