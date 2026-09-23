<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\EventCarbonFootprint;

use App\CarbonFootprint\Application\CarbonFootprintReport\Register\RegisterCarbonFootprintReportService;
use App\CarbonFootprint\Application\CarbonFootprintReport\Remove\RemoveCarbonFootprintReportService;
use App\CarbonFootprint\Domain\Event\EventCarbonFootprint\RegisterReportOnEventCarbonFootprintCompletedSubscriber;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintCompleted;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintProvidersAnswered;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepositoryMother;
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
        $registerService = $this->createMock(RegisterCarbonFootprintReportService::class);
        $registerService->expects($this->once())->method('__invoke');

        $removeService = $this->createMock(RemoveCarbonFootprintReportService::class);
        $removeService->expects($this->never())->method('__invoke');

        $sut = $this->createSUT($registerService, $removeService);
        $sut->handle($this->completedEvent(EventCarbonFootprintId::generate()));
    }

    public function testHandleSkipsWhenPendingReportExists(): void
    {
        $eventCarbonFootprintId = EventCarbonFootprintId::generate();

        $reportRepository = CarbonFootprintReportRepositoryMother::inMemory();
        $reportRepository->add(CarbonFootprintReportMother::registeredFor(CarbonFootprintReportSubjectType::Event, (string) $eventCarbonFootprintId));

        $registerService = $this->createMock(RegisterCarbonFootprintReportService::class);
        $registerService->expects($this->never())->method('__invoke');

        $removeService = $this->createMock(RemoveCarbonFootprintReportService::class);
        $removeService->expects($this->never())->method('__invoke');

        $sut = $this->createSUT($registerService, $removeService, $reportRepository);
        $sut->handle($this->completedEvent($eventCarbonFootprintId));
    }

    public function testHandleRemovesExistingReportAndRegistersANewOne(): void
    {
        $eventCarbonFootprintId = EventCarbonFootprintId::generate();

        $reportRepository = CarbonFootprintReportRepositoryMother::inMemory();
        $reportRepository->add(CarbonFootprintReportMother::generated(CarbonFootprintReportSubjectType::Event, (string) $eventCarbonFootprintId));

        $registerService = $this->createMock(RegisterCarbonFootprintReportService::class);
        $registerService->expects($this->once())->method('__invoke');

        $removeService = $this->createMock(RemoveCarbonFootprintReportService::class);
        $removeService->expects($this->once())->method('__invoke');

        $sut = $this->createSUT($registerService, $removeService, $reportRepository);
        $sut->handle($this->completedEvent($eventCarbonFootprintId));
    }

    public function testHandleReplacesARejectedReport(): void
    {
        $eventCarbonFootprintId = EventCarbonFootprintId::generate();

        $reportRepository = CarbonFootprintReportRepositoryMother::inMemory();
        $reportRepository->add(CarbonFootprintReportMother::rejected(CarbonFootprintReportSubjectType::Event, (string) $eventCarbonFootprintId));

        $registerService = $this->createMock(RegisterCarbonFootprintReportService::class);
        $registerService->expects($this->once())->method('__invoke');

        $removeService = $this->createMock(RemoveCarbonFootprintReportService::class);
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
        ?CarbonFootprintReportRepository $reportRepository = null,
    ): RegisterReportOnEventCarbonFootprintCompletedSubscriber {
        /** @var RegisterCarbonFootprintReportService&MockObject $registerCarbonFootprintReport */
        $registerCarbonFootprintReport = $registerService ?? $this->createMock(RegisterCarbonFootprintReportService::class);

        /** @var RemoveCarbonFootprintReportService&MockObject $removeCarbonFootprintReport */
        $removeCarbonFootprintReport = $removeService ?? $this->createMock(RemoveCarbonFootprintReportService::class);

        return new RegisterReportOnEventCarbonFootprintCompletedSubscriber(
            $registerCarbonFootprintReport,
            $removeCarbonFootprintReport,
            $reportRepository ?? CarbonFootprintReportRepositoryMother::inMemory(),
            new FakeLoggedUser(UserMother::adminUser()),
        );
    }
}
