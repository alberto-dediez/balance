<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\Reissue;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Application\CarbonFootprintReport\GenerateTexts\GenerateCarbonFootprintReportTextsRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\Reissue\ReissueCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\Reissue\ReissueCarbonFootprintReportService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportId;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportStatus;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTransitionNotAllowedException;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\ReportServiceContextTrait;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportMother;
use App\Tests\Unit\Shared\Application\CommandBus\SluttyCommandHandler;
use PHPUnit\Framework\TestCase;

class ReissueCarbonFootprintReportServiceTest extends TestCase
{
    use ReportServiceContextTrait;

    private SluttyCommandHandler $spyCommandBus;
    private ReissueCarbonFootprintReportService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        CommandBusDispatcher::instance()->reset();
        $this->initReportContext();
        $this->spyCommandBus = new SluttyCommandHandler();

        $this->sut = new ReissueCarbonFootprintReportService($this->reportRepository, new CarbonFootprintReportAuthorizer());
    }

    public function testReissuesAGeneratedReportAndDispatchesTheTextsGeneration(): void
    {
        $report = $this->givenReport(CarbonFootprintReportMother::generated());

        ($this->sut)(new ReissueCarbonFootprintReportRequest($report->id()), $this->ownerUser());

        $this->assertSame(CarbonFootprintReportStatus::GeneratingTexts, $report->status());
        $this->assertSame('shared/report-es.pdf', $report->filePathFor(Locale::Spanish));

        $commands = $this->spyCommandBus->commands();
        $this->assertCount(1, $commands);
        $this->assertInstanceOf(DeferredCommand::class, $commands[0]['command']);
        $this->assertInstanceOf(GenerateCarbonFootprintReportTextsRequest::class, $commands[0]['command']->command());
    }

    public function testOnlyGeneratedReportsCanBeReissued(): void
    {
        $report = $this->givenReport(CarbonFootprintReportMother::draft());

        $this->expectException(CarbonFootprintReportTransitionNotAllowedException::class);

        ($this->sut)(new ReissueCarbonFootprintReportRequest($report->id()), $this->ownerUser());
    }

    public function testOwnerOfAnotherAccountIsDenied(): void
    {
        $report = $this->givenReport(CarbonFootprintReportMother::generated());

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new ReissueCarbonFootprintReportRequest($report->id()), $this->ownerUser('another-account-id'));
    }

    public function testReportNotFoundThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new ReissueCarbonFootprintReportRequest(CarbonFootprintReportId::generate()), $this->ownerUser());
    }
}
