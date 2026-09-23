<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\Approve;

use App\CarbonFootprint\Application\CarbonFootprintReport\Approve\ApproveCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\Approve\ApproveCarbonFootprintReportService;
use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Application\CarbonFootprintReport\GeneratePdf\GenerateCarbonFootprintReportPdfRequest;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportStatus;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTransitionNotAllowedException;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\ReportServiceContextTrait;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CommandBus\SluttyCommandHandler;
use PHPUnit\Framework\TestCase;

class ApproveCarbonFootprintReportServiceTest extends TestCase
{
    use ReportServiceContextTrait;

    private SluttyCommandHandler $spyCommandBus;
    private ApproveCarbonFootprintReportService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        CommandBusDispatcher::instance()->reset();
        $this->initReportContext();
        $this->spyCommandBus = new SluttyCommandHandler();

        $this->sut = new ApproveCarbonFootprintReportService($this->reportRepository, new CarbonFootprintReportAuthorizer(), $this->accountRepository);
    }

    public function testAdminApprovesWithEditedTextsAndDispatchesThePdf(): void
    {
        $report = $this->givenReport(CarbonFootprintReportMother::pendingReview());

        ($this->sut)(new ApproveCarbonFootprintReportRequest($report->id(), ['executiveSummary' => 'reviewed']), UserMother::adminUser());

        $this->assertSame(CarbonFootprintReportStatus::GeneratingPdf, $report->status());
        $this->assertFalse($report->isAutoApproved());
        $this->assertSame('reviewed', $report->texts()->sections['executiveSummary']);

        $commands = $this->spyCommandBus->commands();
        $this->assertCount(1, $commands);
        $this->assertInstanceOf(DeferredCommand::class, $commands[0]['command']);
        $this->assertInstanceOf(GenerateCarbonFootprintReportPdfRequest::class, $commands[0]['command']->command());
    }

    public function testOwnerIsDenied(): void
    {
        $report = $this->givenReport(CarbonFootprintReportMother::pendingReview());

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new ApproveCarbonFootprintReportRequest($report->id()), $this->ownerUser());
    }

    public function testDraftCannotBeApprovedByAReviewer(): void
    {
        $report = $this->givenReport(CarbonFootprintReportMother::draft());

        $this->expectException(CarbonFootprintReportTransitionNotAllowedException::class);

        ($this->sut)(new ApproveCarbonFootprintReportRequest($report->id()), UserMother::adminUser());
    }
}
