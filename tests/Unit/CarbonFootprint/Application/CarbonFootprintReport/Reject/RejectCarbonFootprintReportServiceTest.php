<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\Reject;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Application\CarbonFootprintReport\Reject\RejectCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\Reject\RejectCarbonFootprintReportService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRejectionCommentRequiredException;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportStatus;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\ReportServiceContextTrait;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class RejectCarbonFootprintReportServiceTest extends TestCase
{
    use ReportServiceContextTrait;

    private RejectCarbonFootprintReportService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->initReportContext();

        $this->sut = new RejectCarbonFootprintReportService($this->reportRepository, new CarbonFootprintReportAuthorizer());
    }

    public function testAdminRejectsWithAComment(): void
    {
        $report = $this->givenReport(CarbonFootprintReportMother::pendingReview());

        ($this->sut)(new RejectCarbonFootprintReportRequest($report->id(), 'Revisa el alcance 2'), UserMother::adminUser());

        $this->assertSame(CarbonFootprintReportStatus::Rejected, $report->status());
        $this->assertSame('Revisa el alcance 2', $report->reviewComment());
    }

    public function testEmptyCommentThrows(): void
    {
        $report = $this->givenReport(CarbonFootprintReportMother::pendingReview());

        $this->expectException(CarbonFootprintReportRejectionCommentRequiredException::class);

        ($this->sut)(new RejectCarbonFootprintReportRequest($report->id(), ' '), UserMother::adminUser());
    }

    public function testOwnerIsDenied(): void
    {
        $report = $this->givenReport(CarbonFootprintReportMother::pendingReview());

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new RejectCarbonFootprintReportRequest($report->id(), 'nope'), $this->ownerUser());
    }
}
