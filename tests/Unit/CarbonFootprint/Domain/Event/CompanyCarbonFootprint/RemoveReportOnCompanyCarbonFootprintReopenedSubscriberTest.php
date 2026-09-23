<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\CompanyCarbonFootprint;

use App\CarbonFootprint\Application\CarbonFootprintReport\Remove\RemoveCarbonFootprintReportService;
use App\CarbonFootprint\Domain\Event\CompanyCarbonFootprint\RemoveReportOnCompanyCarbonFootprintReopenedSubscriber;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRemoved;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintReopened;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RemoveReportOnCompanyCarbonFootprintReopenedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToReopened(): void
    {
        $this->assertTrue($this->createSUT($this->createMock(RemoveCarbonFootprintReportService::class))->isSubscribedTo(new CompanyCarbonFootprintReopened(CompanyCarbonFootprintId::generate())));
        $this->assertFalse($this->createSUT($this->createMock(RemoveCarbonFootprintReportService::class))->isSubscribedTo(new CompanyCarbonFootprintRemoved(CompanyCarbonFootprintId::generate())));
    }

    public function testRemovesTheExistingReport(): void
    {
        $id = CompanyCarbonFootprintId::generate();
        $service = $this->createMock(RemoveCarbonFootprintReportService::class);
        $service->expects($this->once())->method('__invoke');

        $this->createSUT($service, CarbonFootprintReportMother::generated(CarbonFootprintReportSubjectType::Company, (string) $id))->handle(new CompanyCarbonFootprintReopened($id));
    }

    public function testKeepsARejectedReport(): void
    {
        $id = CompanyCarbonFootprintId::generate();
        $service = $this->createMock(RemoveCarbonFootprintReportService::class);
        $service->expects($this->never())->method('__invoke');

        $this->createSUT($service, CarbonFootprintReportMother::rejected(CarbonFootprintReportSubjectType::Company, (string) $id))->handle(new CompanyCarbonFootprintReopened($id));
    }

    public function testDoesNothingWithoutReport(): void
    {
        $service = $this->createMock(RemoveCarbonFootprintReportService::class);
        $service->expects($this->never())->method('__invoke');

        $this->createSUT($service)->handle(new CompanyCarbonFootprintReopened(CompanyCarbonFootprintId::generate()));
    }

    /** @param RemoveCarbonFootprintReportService&MockObject $service */
    private function createSUT(MockObject $service, ?CarbonFootprintReport $report = null): RemoveReportOnCompanyCarbonFootprintReopenedSubscriber
    {
        $reportRepository = CarbonFootprintReportRepositoryMother::inMemory();

        if (null !== $report) {
            $reportRepository->add($report);
        }

        return new RemoveReportOnCompanyCarbonFootprintReopenedSubscriber($service, $reportRepository, new FakeLoggedUser(UserMother::adminUser()));
    }
}
