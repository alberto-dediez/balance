<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\CompanyCarbonFootprint;

use App\CarbonFootprint\Application\CarbonFootprintReport\Register\RegisterCarbonFootprintReportService;
use App\CarbonFootprint\Application\CarbonFootprintReport\Remove\RemoveCarbonFootprintReportService;
use App\CarbonFootprint\Domain\Event\CompanyCarbonFootprint\RegisterReportOnCompanyCarbonFootprintCompletedSubscriber;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintCompleted;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRemoved;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RegisterReportOnCompanyCarbonFootprintCompletedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToCompleted(): void
    {
        $this->assertTrue($this->createSUT()->isSubscribedTo($this->completedEvent(CompanyCarbonFootprintId::generate())));
    }

    public function testIsNotSubscribedToUnrelatedEvents(): void
    {
        $this->assertFalse($this->createSUT()->isSubscribedTo(new CompanyCarbonFootprintRemoved(CompanyCarbonFootprintId::generate())));
    }

    public function testRegistersAReportWhenNoneExists(): void
    {
        $registerService = $this->createMock(RegisterCarbonFootprintReportService::class);
        $registerService->expects($this->once())->method('__invoke');

        $removeService = $this->createMock(RemoveCarbonFootprintReportService::class);
        $removeService->expects($this->never())->method('__invoke');

        $this->createSUT($registerService, $removeService)->handle($this->completedEvent(CompanyCarbonFootprintId::generate()));
    }

    public function testSkipsAReportInProgress(): void
    {
        $id = CompanyCarbonFootprintId::generate();
        $reportRepository = CarbonFootprintReportRepositoryMother::inMemory();
        $reportRepository->add(CarbonFootprintReportMother::registeredFor(CarbonFootprintReportSubjectType::Company, (string) $id));

        $registerService = $this->createMock(RegisterCarbonFootprintReportService::class);
        $registerService->expects($this->never())->method('__invoke');

        $removeService = $this->createMock(RemoveCarbonFootprintReportService::class);
        $removeService->expects($this->never())->method('__invoke');

        $this->createSUT($registerService, $removeService, $reportRepository)->handle($this->completedEvent($id));
    }

    /** @return iterable<string, array{0: string}> */
    public static function replaceableStates(): iterable
    {
        yield 'generated' => ['generated'];
        yield 'rejected' => ['rejected'];
    }

    /** @dataProvider replaceableStates */
    public function testReplacesAFinishedReport(string $state): void
    {
        $id = CompanyCarbonFootprintId::generate();
        $reportRepository = CarbonFootprintReportRepositoryMother::inMemory();
        $reportRepository->add(CarbonFootprintReportMother::$state(CarbonFootprintReportSubjectType::Company, (string) $id));

        $registerService = $this->createMock(RegisterCarbonFootprintReportService::class);
        $registerService->expects($this->once())->method('__invoke');

        $removeService = $this->createMock(RemoveCarbonFootprintReportService::class);
        $removeService->expects($this->once())->method('__invoke');

        $this->createSUT($registerService, $removeService, $reportRepository)->handle($this->completedEvent($id));
    }

    private function completedEvent(CompanyCarbonFootprintId $id): CompanyCarbonFootprintCompleted
    {
        return new CompanyCarbonFootprintCompleted($id, new Emissions());
    }

    private function createSUT(
        ?MockObject $registerService = null,
        ?MockObject $removeService = null,
        ?CarbonFootprintReportRepository $reportRepository = null,
    ): RegisterReportOnCompanyCarbonFootprintCompletedSubscriber {
        /** @var RegisterCarbonFootprintReportService&MockObject $register */
        $register = $registerService ?? $this->createMock(RegisterCarbonFootprintReportService::class);

        /** @var RemoveCarbonFootprintReportService&MockObject $remove */
        $remove = $removeService ?? $this->createMock(RemoveCarbonFootprintReportService::class);

        return new RegisterReportOnCompanyCarbonFootprintCompletedSubscriber(
            $register,
            $remove,
            $reportRepository ?? CarbonFootprintReportRepositoryMother::inMemory(),
            new FakeLoggedUser(UserMother::adminUser()),
        );
    }
}
