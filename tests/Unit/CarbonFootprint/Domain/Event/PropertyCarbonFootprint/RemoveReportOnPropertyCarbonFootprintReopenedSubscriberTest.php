<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\PropertyCarbonFootprint;

use App\CarbonFootprint\Application\CarbonFootprintReport\Remove\RemoveCarbonFootprintReportService;
use App\CarbonFootprint\Domain\Event\PropertyCarbonFootprint\RemoveReportOnPropertyCarbonFootprintReopenedSubscriber;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRemoved;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintReopened;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RemoveReportOnPropertyCarbonFootprintReopenedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToReopened(): void
    {
        $this->assertTrue($this->createSUT($this->createMock(RemoveCarbonFootprintReportService::class))->isSubscribedTo(new PropertyCarbonFootprintReopened(PropertyCarbonFootprintId::generate())));
        $this->assertFalse($this->createSUT($this->createMock(RemoveCarbonFootprintReportService::class))->isSubscribedTo(new PropertyCarbonFootprintRemoved(PropertyCarbonFootprintId::generate())));
    }

    public function testRemovesTheExistingReport(): void
    {
        $id = PropertyCarbonFootprintId::generate();
        $service = $this->createMock(RemoveCarbonFootprintReportService::class);
        $service->expects($this->once())->method('__invoke');

        $this->createSUT($service, CarbonFootprintReportMother::generated(CarbonFootprintReportSubjectType::Property, (string) $id))->handle(new PropertyCarbonFootprintReopened($id));
    }

    public function testKeepsARejectedReport(): void
    {
        $id = PropertyCarbonFootprintId::generate();
        $service = $this->createMock(RemoveCarbonFootprintReportService::class);
        $service->expects($this->never())->method('__invoke');

        $this->createSUT($service, CarbonFootprintReportMother::rejected(CarbonFootprintReportSubjectType::Property, (string) $id))->handle(new PropertyCarbonFootprintReopened($id));
    }

    public function testDoesNothingWithoutReport(): void
    {
        $service = $this->createMock(RemoveCarbonFootprintReportService::class);
        $service->expects($this->never())->method('__invoke');

        $this->createSUT($service)->handle(new PropertyCarbonFootprintReopened(PropertyCarbonFootprintId::generate()));
    }

    /** @param RemoveCarbonFootprintReportService&MockObject $service */
    private function createSUT(MockObject $service, ?CarbonFootprintReport $report = null): RemoveReportOnPropertyCarbonFootprintReopenedSubscriber
    {
        $reportRepository = CarbonFootprintReportRepositoryMother::inMemory();

        if (null !== $report) {
            $reportRepository->add($report);
        }

        return new RemoveReportOnPropertyCarbonFootprintReopenedSubscriber($service, $reportRepository, new FakeLoggedUser(UserMother::adminUser()));
    }
}
