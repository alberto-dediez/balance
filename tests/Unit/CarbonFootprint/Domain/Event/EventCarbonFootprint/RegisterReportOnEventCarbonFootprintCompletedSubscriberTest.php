<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\EventCarbonFootprint;

use App\CarbonFootprint\Application\EventCarbonFootprintReport\Register\RegisterEventCarbonFootprintReportService;
use App\CarbonFootprint\Application\EventCarbonFootprintReport\Remove\RemoveEventCarbonFootprintReportService;
use App\CarbonFootprint\Domain\Event\EventCarbonFootprint\RegisterReportOnEventCarbonFootprintCompletedSubscriber;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintCompleted;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintProvidersAnswered;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RegisterReportOnEventCarbonFootprintCompletedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToEventCarbonFootprintCompleted(): void
    {
        $sut = $this->createSUT();

        $this->assertTrue($sut->isSubscribedTo($this->completedEvent(EventCarbonFootprintId::generate())));
    }

    public function testIsNotSubscribedToUnrelatedEvents(): void
    {
        $sut = $this->createSUT();

        $this->assertFalse($sut->isSubscribedTo(new EventCarbonFootprintProvidersAnswered(
            eventCarbonFootprintId: EventCarbonFootprintId::generate(),
        )));
    }

    public function testHandleRegistersReportWhenNoneExists(): void
    {
        $registerService = $this->createMock(RegisterEventCarbonFootprintReportService::class);
        $registerService->expects($this->once())->method('__invoke');

        $removeService = $this->createMock(RemoveEventCarbonFootprintReportService::class);
        $removeService->expects($this->never())->method('__invoke');

        $sut = $this->createSUT($registerService, $removeService);
        $sut->handle($this->completedEvent(EventCarbonFootprintId::generate()));
    }

    public function testHandleSkipsWhenPendingReportExists(): void
    {
        $eventCarbonFootprintId = EventCarbonFootprintId::generate();

        $reportRepository = EventCarbonFootprintReportRepositoryMother::inMemory();
        $reportRepository->add(EventCarbonFootprintReportMother::pending((string) $eventCarbonFootprintId));

        $registerService = $this->createMock(RegisterEventCarbonFootprintReportService::class);
        $registerService->expects($this->never())->method('__invoke');

        $removeService = $this->createMock(RemoveEventCarbonFootprintReportService::class);
        $removeService->expects($this->never())->method('__invoke');

        $sut = $this->createSUT($registerService, $removeService, $reportRepository);
        $sut->handle($this->completedEvent($eventCarbonFootprintId));
    }

    public function testHandleRemovesExistingReportAndRegistersANewOne(): void
    {
        $eventCarbonFootprintId = EventCarbonFootprintId::generate();

        $reportRepository = EventCarbonFootprintReportRepositoryMother::inMemory();
        $reportRepository->add(EventCarbonFootprintReportMother::generated((string) $eventCarbonFootprintId));

        $registerService = $this->createMock(RegisterEventCarbonFootprintReportService::class);
        $registerService->expects($this->once())->method('__invoke');

        $removeService = $this->createMock(RemoveEventCarbonFootprintReportService::class);
        $removeService->expects($this->once())->method('__invoke');

        $sut = $this->createSUT($registerService, $removeService, $reportRepository);
        $sut->handle($this->completedEvent($eventCarbonFootprintId));
    }

    private function completedEvent(EventCarbonFootprintId $eventCarbonFootprintId): EventCarbonFootprintCompleted
    {
        return new EventCarbonFootprintCompleted(
            eventCarbonFootprintId: $eventCarbonFootprintId,
            totalEmissions: new Emissions(),
            emissionsPerAttendee: new Emissions(),
        );
    }

    private function createSUT(
        ?MockObject $registerService = null,
        ?MockObject $removeService = null,
        ?EventCarbonFootprintReportRepository $reportRepository = null,
    ): RegisterReportOnEventCarbonFootprintCompletedSubscriber {
        /** @var RegisterEventCarbonFootprintReportService&MockObject $registerEventCarbonFootprintReport */
        $registerEventCarbonFootprintReport = $registerService ?? $this->createMock(RegisterEventCarbonFootprintReportService::class);

        /** @var RemoveEventCarbonFootprintReportService&MockObject $removeEventCarbonFootprintReport */
        $removeEventCarbonFootprintReport = $removeService ?? $this->createMock(RemoveEventCarbonFootprintReportService::class);

        return new RegisterReportOnEventCarbonFootprintCompletedSubscriber(
            $registerEventCarbonFootprintReport,
            $removeEventCarbonFootprintReport,
            $reportRepository ?? EventCarbonFootprintReportRepositoryMother::inMemory(),
            new FakeLoggedUser(UserMother::adminUser()),
        );
    }
}
