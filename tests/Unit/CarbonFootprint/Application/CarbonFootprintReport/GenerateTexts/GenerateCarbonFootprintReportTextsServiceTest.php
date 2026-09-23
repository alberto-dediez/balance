<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\GenerateTexts;

use App\CarbonFootprint\Application\CarbonFootprintReport\Data\CarbonFootprintReportDataCollectorRegistry;
use App\CarbonFootprint\Application\CarbonFootprintReport\GeneratePdf\GenerateCarbonFootprintReportPdfRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\GenerateTexts\GenerateCarbonFootprintReportTextsRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\GenerateTexts\GenerateCarbonFootprintReportTextsService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportApproved;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportStatus;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubmittedForReview;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTextsDrafted;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\FailingOnAddCarbonFootprintReportRepository;
use App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\ReportServiceContextTrait;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CommandBus\SluttyCommandHandler;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class GenerateCarbonFootprintReportTextsServiceTest extends TestCase
{
    use ReportServiceContextTrait;

    private SluttyCommandHandler $spyCommandBus;
    private SpyDomainEventSubscriber $spySubscriber;
    private FakeCarbonFootprintReportTextsGenerator $textsGenerator;
    private GenerateCarbonFootprintReportTextsService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        CommandBusDispatcher::instance()->reset();

        $this->initReportContext();
        $this->spyCommandBus = new SluttyCommandHandler();
        $this->spySubscriber = new SpyDomainEventSubscriber();
        $this->textsGenerator = new FakeCarbonFootprintReportTextsGenerator();

        $this->sut = new GenerateCarbonFootprintReportTextsService(
            $this->reportRepository,
            new CarbonFootprintReportDataCollectorRegistry([new StubCarbonFootprintReportDataCollector()]),
            $this->textsGenerator,
            $this->accountRepository,
            'es|en',
        );
    }

    public function testAccountThatCanEditTextsGetsADraft(): void
    {
        $accountId = $this->givenAccount(true, true);
        $report = $this->givenReport(CarbonFootprintReportMother::registeredFor(CarbonFootprintReportSubjectType::Property, accountId: $accountId));

        ($this->sut)(new GenerateCarbonFootprintReportTextsRequest($report->id()), UserMother::systemUser());

        $this->assertSame(CarbonFootprintReportStatus::Draft, $report->status());
        $this->assertInstanceOf(CarbonFootprintReportTextsDrafted::class, $this->spySubscriber->lastEvent());
        $this->assertCount(4, $report->sections());
        $this->assertCount(0, $this->spyCommandBus->commands());
    }

    public function testAccountWithAutoReviewGetsAnAutomaticApprovalAndThePdfIsDispatched(): void
    {
        $accountId = $this->givenAccount(false, true);
        $report = $this->givenReport(CarbonFootprintReportMother::registeredFor(CarbonFootprintReportSubjectType::Company, accountId: $accountId));

        ($this->sut)(new GenerateCarbonFootprintReportTextsRequest($report->id()), UserMother::systemUser());

        $this->assertSame(CarbonFootprintReportStatus::GeneratingPdf, $report->status());
        $this->assertTrue($report->isAutoApproved());
        $this->assertInstanceOf(CarbonFootprintReportApproved::class, $this->spySubscriber->lastEvent());

        $commands = $this->spyCommandBus->commands();
        $this->assertCount(1, $commands);
        $this->assertInstanceOf(DeferredCommand::class, $commands[0]['command']);
        $this->assertInstanceOf(GenerateCarbonFootprintReportPdfRequest::class, $commands[0]['command']->command());
    }

    public function testAccountWithoutAutoReviewGoesToPendingReview(): void
    {
        $accountId = $this->givenAccount(false, false);
        $report = $this->givenReport(CarbonFootprintReportMother::registeredFor(CarbonFootprintReportSubjectType::Event, accountId: $accountId));

        ($this->sut)(new GenerateCarbonFootprintReportTextsRequest($report->id()), UserMother::systemUser());

        $this->assertSame(CarbonFootprintReportStatus::PendingReview, $report->status());
        $this->assertInstanceOf(CarbonFootprintReportSubmittedForReview::class, $this->spySubscriber->lastEvent());
        $this->assertCount(0, $this->spyCommandBus->commands());
    }

    public function testIncompleteTextsMarkTheReportAsFailed(): void
    {
        $accountId = $this->givenAccount(false, true);
        $report = $this->givenReport(CarbonFootprintReportMother::registeredFor(accountId: $accountId));
        $this->textsGenerator->returnIncompleteTexts();

        $this->expectException(\RuntimeException::class);

        try {
            ($this->sut)(new GenerateCarbonFootprintReportTextsRequest($report->id()), UserMother::systemUser());
        } finally {
            $this->assertSame(CarbonFootprintReportStatus::Failed, $report->status());
        }
    }

    public function testGeneratorFailureMarksTheReportAsFailedAndRethrows(): void
    {
        $accountId = $this->givenAccount(false, true);
        $report = $this->givenReport(CarbonFootprintReportMother::registeredFor(accountId: $accountId));
        $this->textsGenerator->failOnGenerate();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Gemini failure');

        try {
            ($this->sut)(new GenerateCarbonFootprintReportTextsRequest($report->id()), UserMother::systemUser());
        } finally {
            $this->assertSame(CarbonFootprintReportStatus::Failed, $report->status());
            $this->assertSame('Gemini failure', $report->errorMessage());
        }
    }

    public function testAFailureAfterTheTransitionIsRethrownWithoutMarkingTheReportAsFailed(): void
    {
        $accountId = $this->givenAccount(true, true);
        $this->reportRepository = new FailingOnAddCarbonFootprintReportRepository();
        $report = $this->givenReport(CarbonFootprintReportMother::registeredFor(accountId: $accountId));
        $this->reportRepository->failOnNextAdd();

        $sut = new GenerateCarbonFootprintReportTextsService(
            $this->reportRepository,
            new CarbonFootprintReportDataCollectorRegistry([new StubCarbonFootprintReportDataCollector()]),
            $this->textsGenerator,
            $this->accountRepository,
            'es|en',
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('persistence failure');

        try {
            $sut(new GenerateCarbonFootprintReportTextsRequest($report->id()), UserMother::systemUser());
        } finally {
            $this->assertSame(CarbonFootprintReportStatus::Draft, $report->status());
            $this->assertNull($report->errorMessage());
        }
    }

    public function testDoesNothingWhenTheReportIsNotGeneratingTexts(): void
    {
        $report = $this->givenReport(CarbonFootprintReportMother::draft());

        ($this->sut)(new GenerateCarbonFootprintReportTextsRequest($report->id()), UserMother::systemUser());

        $this->assertCount(0, $this->textsGenerator->generateCalls());
        $this->assertSame(CarbonFootprintReportStatus::Draft, $report->status());
    }

    public function testUnknownAccountFallsBackToTheDefaultPermissions(): void
    {
        $report = $this->givenReport(CarbonFootprintReportMother::registeredFor(accountId: new AccountId('missing-account')));

        ($this->sut)(new GenerateCarbonFootprintReportTextsRequest($report->id()), UserMother::systemUser());

        $this->assertSame(CarbonFootprintReportStatus::GeneratingPdf, $report->status());
        $this->assertTrue($report->isAutoApproved());
    }

    public function testReportNotFoundThrows(): void
    {
        $this->expectException(\RuntimeException::class);

        ($this->sut)(new GenerateCarbonFootprintReportTextsRequest(CarbonFootprintReportMother::random()->id()), UserMother::systemUser());
    }
}
