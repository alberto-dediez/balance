<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\UpdateTexts;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Application\CarbonFootprintReport\UpdateTexts\UpdateCarbonFootprintReportTextsRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\UpdateTexts\UpdateCarbonFootprintReportTextsService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSectionKey;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTransitionNotAllowedException;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\ReportServiceContextTrait;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class UpdateCarbonFootprintReportTextsServiceTest extends TestCase
{
    use ReportServiceContextTrait;

    private UpdateCarbonFootprintReportTextsService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->initReportContext();

        $this->sut = new UpdateCarbonFootprintReportTextsService($this->reportRepository, new CarbonFootprintReportAuthorizer(), $this->accountRepository);
    }

    public function testOwnerUpdatesTheDraftWhenTheAccountCanEditTexts(): void
    {
        $accountId = $this->givenAccount(true, true);
        $report = $this->givenReport(CarbonFootprintReportMother::draft(accountId: $accountId));

        ($this->sut)(new UpdateCarbonFootprintReportTextsRequest($report->id(), ['executiveSummary' => 'edited', 'methodology' => 'm', 'emissionFactorSources' => 's', 'analysisAndRecommendations' => 'a']), $this->ownerUser());

        $this->assertSame('edited', $report->texts()->get(CarbonFootprintReportSectionKey::ExecutiveSummary));
        $this->assertTrue($report->texts()->has(CarbonFootprintReportSectionKey::Compensation));
    }

    public function testOwnerIsDeniedWhenTheAccountCannotEditTexts(): void
    {
        $accountId = $this->givenAccount(false, true);
        $report = $this->givenReport(CarbonFootprintReportMother::draft(accountId: $accountId));

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new UpdateCarbonFootprintReportTextsRequest($report->id(), ['executiveSummary' => 'edited']), $this->ownerUser());
    }

    public function testAdminIsDeniedOnADraft(): void
    {
        $accountId = $this->givenAccount(true, true);
        $report = $this->givenReport(CarbonFootprintReportMother::draft(accountId: $accountId));

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new UpdateCarbonFootprintReportTextsRequest($report->id(), ['executiveSummary' => 'edited']), UserMother::adminUser());
    }

    public function testAdminUpdatesAReportPendingReview(): void
    {
        $report = $this->givenReport(CarbonFootprintReportMother::pendingReview());

        ($this->sut)(new UpdateCarbonFootprintReportTextsRequest($report->id(), ['executiveSummary' => 'reviewed']), UserMother::adminUser());

        $this->assertSame('reviewed', $report->texts()->get(CarbonFootprintReportSectionKey::ExecutiveSummary));
    }

    public function testOwnerIsDeniedOnAReportPendingReview(): void
    {
        $accountId = $this->givenAccount(true, true);
        $report = $this->givenReport(CarbonFootprintReportMother::pendingReview(accountId: $accountId));

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new UpdateCarbonFootprintReportTextsRequest($report->id(), ['executiveSummary' => 'edited']), $this->ownerUser());
    }

    public function testGeneratedReportCannotBeChanged(): void
    {
        $accountId = $this->givenAccount(true, true);
        $report = $this->givenReport(CarbonFootprintReportMother::generated(accountId: $accountId));

        $this->expectException(CarbonFootprintReportTransitionNotAllowedException::class);

        ($this->sut)(new UpdateCarbonFootprintReportTextsRequest($report->id(), ['executiveSummary' => 'edited']), $this->ownerUser());
    }

    public function testUnknownKeysAreIgnored(): void
    {
        $accountId = $this->givenAccount(true, true);
        $report = $this->givenReport(CarbonFootprintReportMother::draft(accountId: $accountId));

        ($this->sut)(new UpdateCarbonFootprintReportTextsRequest($report->id(), ['purpose' => 'nope', 'executiveSummary' => 'edited']), $this->ownerUser());

        $this->assertArrayNotHasKey('purpose', $report->texts()->sections);
        $this->assertSame('edited', $report->texts()->get(CarbonFootprintReportSectionKey::ExecutiveSummary));
    }
}
