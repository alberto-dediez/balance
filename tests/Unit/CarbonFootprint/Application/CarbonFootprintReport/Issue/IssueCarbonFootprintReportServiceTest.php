<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\Issue;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Application\CarbonFootprintReport\GeneratePdf\GenerateCarbonFootprintReportPdfRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\Issue\IssueCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\Issue\IssueCarbonFootprintReportService;
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

class IssueCarbonFootprintReportServiceTest extends TestCase
{
    use ReportServiceContextTrait;

    private SluttyCommandHandler $spyCommandBus;
    private IssueCarbonFootprintReportService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        CommandBusDispatcher::instance()->reset();
        $this->initReportContext();
        $this->spyCommandBus = new SluttyCommandHandler();

        $this->sut = new IssueCarbonFootprintReportService($this->reportRepository, new CarbonFootprintReportAuthorizer(), $this->accountRepository);
    }

    public function testIssuesTheDraftAndDispatchesThePdf(): void
    {
        $accountId = $this->givenAccount(true, true);
        $report = $this->givenReport(CarbonFootprintReportMother::draft(accountId: $accountId));

        ($this->sut)(new IssueCarbonFootprintReportRequest($report->id(), ['executiveSummary' => 'edited']), $this->ownerUser());

        $this->assertSame(CarbonFootprintReportStatus::GeneratingPdf, $report->status());
        $this->assertTrue($report->isAutoApproved());
        $this->assertSame('edited', $report->texts()->sections['executiveSummary']);

        $commands = $this->spyCommandBus->commands();
        $this->assertCount(1, $commands);
        $this->assertInstanceOf(DeferredCommand::class, $commands[0]['command']);
        $this->assertInstanceOf(GenerateCarbonFootprintReportPdfRequest::class, $commands[0]['command']->command());
    }

    public function testAReportPendingReviewCannotBeIssued(): void
    {
        $accountId = $this->givenAccount(true, true);
        $report = $this->givenReport(CarbonFootprintReportMother::pendingReview(accountId: $accountId));

        $this->expectException(CarbonFootprintReportTransitionNotAllowedException::class);

        try {
            ($this->sut)(new IssueCarbonFootprintReportRequest($report->id()), $this->ownerUser());
        } finally {
            $this->assertSame(CarbonFootprintReportStatus::PendingReview, $report->status());
            $this->assertCount(0, $this->spyCommandBus->commands());
        }
    }

    public function testDeniedWithoutAutoReview(): void
    {
        $accountId = $this->givenAccount(true, false);
        $report = $this->givenReport(CarbonFootprintReportMother::draft(accountId: $accountId));

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new IssueCarbonFootprintReportRequest($report->id()), $this->ownerUser());
    }

    public function testDeniedWhenTheAccountCannotEditTexts(): void
    {
        $accountId = $this->givenAccount(false, true);
        $report = $this->givenReport(CarbonFootprintReportMother::draft(accountId: $accountId));

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new IssueCarbonFootprintReportRequest($report->id()), $this->ownerUser());
    }
}
