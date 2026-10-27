<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\SubmitForReview;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Application\CarbonFootprintReport\SubmitForReview\SubmitCarbonFootprintReportForReviewRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\SubmitForReview\SubmitCarbonFootprintReportForReviewService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportStatus;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubmittedForReview;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTextsIncompleteException;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\ReportServiceContextTrait;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class SubmitCarbonFootprintReportForReviewServiceTest extends TestCase
{
    use ReportServiceContextTrait;

    private SpyDomainEventSubscriber $spySubscriber;
    private SubmitCarbonFootprintReportForReviewService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->initReportContext();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $this->sut = new SubmitCarbonFootprintReportForReviewService($this->reportRepository, new CarbonFootprintReportAuthorizer(), $this->reportPermissionsPolicy);
    }

    public function testSubmitsTheDraftWithTheEditedTexts(): void
    {
        $accountId = $this->givenAccount(true, false);
        $report = $this->givenReport(CarbonFootprintReportMother::draft(accountId: $accountId));

        ($this->sut)(new SubmitCarbonFootprintReportForReviewRequest($report->id(), ['executiveSummary' => 'edited', 'methodology' => 'm', 'emissionFactorSources' => 's', 'analysisAndRecommendations' => 'a']), $this->ownerUser());

        $this->assertSame(CarbonFootprintReportStatus::PendingReview, $report->status());
        $this->assertInstanceOf(CarbonFootprintReportSubmittedForReview::class, $this->spySubscriber->lastEvent());
        $this->assertSame('edited', $report->texts()->sections['executiveSummary']);
    }

    public function testDeniedWhenTheAccountCannotEditTexts(): void
    {
        $accountId = $this->givenAccount(false, false);
        $report = $this->givenReport(CarbonFootprintReportMother::draft(accountId: $accountId));

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new SubmitCarbonFootprintReportForReviewRequest($report->id()), $this->ownerUser());
    }

    public function testIncompleteSectionsThrow(): void
    {
        $accountId = $this->givenAccount(true, false);
        $report = $this->givenReport(CarbonFootprintReportMother::draft(accountId: $accountId));

        $this->expectException(CarbonFootprintReportTextsIncompleteException::class);

        ($this->sut)(new SubmitCarbonFootprintReportForReviewRequest($report->id(), ['executiveSummary' => '', 'methodology' => 'm', 'emissionFactorSources' => 's', 'analysisAndRecommendations' => 'a']), $this->ownerUser());
    }

    public function testConsultantOwnerSubmitsWithTheConsultantEditPermission(): void
    {
        $consultantId = $this->givenConsultantAccount(true, false);
        $accountId = $this->givenManagedAccount($consultantId, false, false);
        $report = $this->givenReport(CarbonFootprintReportMother::draft(accountId: $accountId));

        ($this->impersonatingSut($accountId))(new SubmitCarbonFootprintReportForReviewRequest($report->id()), $this->consultantOwnerUser());

        $this->assertSame(CarbonFootprintReportStatus::PendingReview, $report->status());
        $this->assertInstanceOf(CarbonFootprintReportSubmittedForReview::class, $this->spySubscriber->lastEvent());
    }

    private function impersonatingSut(AccountId $accountId): SubmitCarbonFootprintReportForReviewService
    {
        return new SubmitCarbonFootprintReportForReviewService(
            $this->reportRepository,
            $this->impersonatingAuthorizer($accountId),
            $this->reportPermissionsPolicy,
        );
    }
}
