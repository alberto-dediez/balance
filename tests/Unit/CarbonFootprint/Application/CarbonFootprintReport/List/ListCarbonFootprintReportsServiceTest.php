<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\List;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Application\CarbonFootprintReport\List\ListCarbonFootprintReportsRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\List\ListCarbonFootprintReportsService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportStatus;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\ReportServiceContextTrait;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListCarbonFootprintReportsServiceTest extends TestCase
{
    use ReportServiceContextTrait;

    private ListCarbonFootprintReportsService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->initReportContext();

        $this->sut = new ListCarbonFootprintReportsService($this->reportRepository, new CarbonFootprintReportAuthorizer());
    }

    public function testAdminListsThePendingReportsByDefault(): void
    {
        $this->givenReport(CarbonFootprintReportMother::pendingReview());
        $this->givenReport(CarbonFootprintReportMother::generated());

        $response = ($this->sut)(new ListCarbonFootprintReportsRequest(), UserMother::adminUser());

        $this->assertCount(1, $response->reports);
        $this->assertSame(1, $response->totalResults);
        $this->assertSame(CarbonFootprintReportStatus::PendingReview, $response->reports[0]->status());
    }

    public function testFiltersByStatus(): void
    {
        $this->givenReport(CarbonFootprintReportMother::pendingReview());
        $this->givenReport(CarbonFootprintReportMother::generated());

        $response = ($this->sut)(new ListCarbonFootprintReportsRequest(status: CarbonFootprintReportStatus::Generated), UserMother::adminUser());

        $this->assertCount(1, $response->reports);
        $this->assertSame(CarbonFootprintReportStatus::Generated, $response->reports[0]->status());
    }

    public function testOwnerIsDenied(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new ListCarbonFootprintReportsRequest(), $this->ownerUser());
    }
}
