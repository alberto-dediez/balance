<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\PropertyCarbonFootprint;

use App\CarbonFootprint\Application\CarbonFootprintReport\Register\RegisterCarbonFootprintReportService;
use App\CarbonFootprint\Application\CarbonFootprintReport\Remove\RemoveCarbonFootprintReportService;
use App\CarbonFootprint\Domain\Event\PropertyCarbonFootprint\RegisterReportOnPropertyCarbonFootprintCompletedSubscriber;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintCompleted;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRemoved;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RegisterReportOnPropertyCarbonFootprintCompletedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToCompleted(): void
    {
        $this->assertTrue($this->createSUT()->isSubscribedTo($this->completedEvent(PropertyCarbonFootprintId::generate())));
    }

    public function testIsNotSubscribedToUnrelatedEvents(): void
    {
        $this->assertFalse($this->createSUT()->isSubscribedTo(new PropertyCarbonFootprintRemoved(PropertyCarbonFootprintId::generate())));
    }

    public function testRegistersAReportWhenNoneExists(): void
    {
        $registerService = $this->createMock(RegisterCarbonFootprintReportService::class);
        $registerService->expects($this->once())->method('__invoke');

        $removeService = $this->createMock(RemoveCarbonFootprintReportService::class);
        $removeService->expects($this->never())->method('__invoke');

        $this->createSUT($registerService, $removeService)->handle($this->completedEvent(PropertyCarbonFootprintId::generate()));
    }

    public function testSkipsAReportInProgress(): void
    {
        $id = PropertyCarbonFootprintId::generate();
        $reportRepository = CarbonFootprintReportRepositoryMother::inMemory();
        $reportRepository->add(CarbonFootprintReportMother::registeredFor(CarbonFootprintReportSubjectType::Property, (string) $id));

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
        $id = PropertyCarbonFootprintId::generate();
        $reportRepository = CarbonFootprintReportRepositoryMother::inMemory();
        $reportRepository->add(CarbonFootprintReportMother::$state(CarbonFootprintReportSubjectType::Property, (string) $id));

        $registerService = $this->createMock(RegisterCarbonFootprintReportService::class);
        $registerService->expects($this->once())->method('__invoke');

        $removeService = $this->createMock(RemoveCarbonFootprintReportService::class);
        $removeService->expects($this->once())->method('__invoke');

        $this->createSUT($registerService, $removeService, $reportRepository)->handle($this->completedEvent($id));
    }

    private function completedEvent(PropertyCarbonFootprintId $id): PropertyCarbonFootprintCompleted
    {
        return new PropertyCarbonFootprintCompleted($id, new Emissions());
    }

    private function createSUT(
        ?MockObject $registerService = null,
        ?MockObject $removeService = null,
        ?CarbonFootprintReportRepository $reportRepository = null,
    ): RegisterReportOnPropertyCarbonFootprintCompletedSubscriber {
        /** @var RegisterCarbonFootprintReportService&MockObject $register */
        $register = $registerService ?? $this->createMock(RegisterCarbonFootprintReportService::class);

        /** @var RemoveCarbonFootprintReportService&MockObject $remove */
        $remove = $removeService ?? $this->createMock(RemoveCarbonFootprintReportService::class);

        return new RegisterReportOnPropertyCarbonFootprintCompletedSubscriber(
            $register,
            $remove,
            $reportRepository ?? CarbonFootprintReportRepositoryMother::inMemory(),
            new FakeLoggedUser(UserMother::adminUser()),
        );
    }
}
