<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\Retry;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Application\CarbonFootprintReport\GeneratePdf\GenerateCarbonFootprintReportPdfRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\GenerateTexts\GenerateCarbonFootprintReportTextsRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\Retry\RetryCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\Retry\RetryCarbonFootprintReportService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportStatus;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTransitionNotAllowedException;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\ReportServiceContextTrait;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportMother;
use App\Tests\Unit\Shared\Application\CommandBus\SluttyCommandHandler;
use PHPUnit\Framework\TestCase;

class RetryCarbonFootprintReportServiceTest extends TestCase
{
    use ReportServiceContextTrait;

    private SluttyCommandHandler $spyCommandBus;
    private RetryCarbonFootprintReportService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        CommandBusDispatcher::instance()->reset();
        $this->initReportContext();
        $this->spyCommandBus = new SluttyCommandHandler();

        $this->sut = new RetryCarbonFootprintReportService($this->reportRepository, new CarbonFootprintReportAuthorizer());
    }

    public function testRetryWithoutApprovalDispatchesTheTexts(): void
    {
        $report = $this->givenReport(CarbonFootprintReportMother::failedBeforeApproval());

        ($this->sut)(new RetryCarbonFootprintReportRequest($report->id()), $this->ownerUser());

        $this->assertSame(CarbonFootprintReportStatus::GeneratingTexts, $report->status());
        $command = $this->spyCommandBus->commands()[0]['command'];
        $this->assertInstanceOf(DeferredCommand::class, $command);
        $this->assertInstanceOf(GenerateCarbonFootprintReportTextsRequest::class, $command->command());
    }

    public function testRetryAfterApprovalDispatchesThePdf(): void
    {
        $report = $this->givenReport(CarbonFootprintReportMother::failedAfterApproval());

        ($this->sut)(new RetryCarbonFootprintReportRequest($report->id()), $this->ownerUser());

        $this->assertSame(CarbonFootprintReportStatus::GeneratingPdf, $report->status());
        $command = $this->spyCommandBus->commands()[0]['command'];
        $this->assertInstanceOf(DeferredCommand::class, $command);
        $this->assertInstanceOf(GenerateCarbonFootprintReportPdfRequest::class, $command->command());
    }

    public function testAnOwnerOfAnotherAccountCannotRetry(): void
    {
        $report = $this->givenReport(CarbonFootprintReportMother::failedBeforeApproval());

        $this->expectException(UnauthorizedActionException::class);

        try {
            ($this->sut)(new RetryCarbonFootprintReportRequest($report->id()), $this->ownerUser('other-account'));
        } finally {
            $this->assertSame(CarbonFootprintReportStatus::Failed, $report->status());
            $this->assertCount(0, $this->spyCommandBus->commands());
        }
    }

    public function testOnlyFailedReportsCanBeRetried(): void
    {
        $report = $this->givenReport(CarbonFootprintReportMother::generated());

        $this->expectException(CarbonFootprintReportTransitionNotAllowedException::class);

        ($this->sut)(new RetryCarbonFootprintReportRequest($report->id()), $this->ownerUser());
    }
}
