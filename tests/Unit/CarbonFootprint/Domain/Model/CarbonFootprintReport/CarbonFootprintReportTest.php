<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportApproved;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportFailed;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportGenerated;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportId;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRegistered;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportReissued;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRejected;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRejectionCommentRequiredException;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRemoved;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRetried;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSectionKey;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportStatus;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubmittedForReview;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTexts;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTextsChanged;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTextsDrafted;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTextsIncompleteException;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTransitionNotAllowedException;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class CarbonFootprintReportTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRegisterPublishesEventAndStartsGeneratingTexts(): void
    {
        $report = new CarbonFootprintReport(id: CarbonFootprintReportId::generate());
        $report->register(CarbonFootprintReportSubjectType::Property, 'footprint-1', new AccountId('account-id'), UserId::generate(), Locale::Spanish);

        $this->assertSame(CarbonFootprintReportStatus::GeneratingTexts, $report->status());
        $this->assertSame(CarbonFootprintReportSubjectType::Property, $report->subjectType());
        $this->assertSame('footprint-1', $report->carbonFootprintId());
        $this->assertSame(Locale::Spanish, $report->baseLocale());
        $this->assertInstanceOf(CarbonFootprintReportRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testDraftTextsStoresTheSectionsInOrder(): void
    {
        $report = CarbonFootprintReportMother::registeredFor();

        $report->draftTexts(CarbonFootprintReportTextsMother::forEvent());

        $this->assertSame(CarbonFootprintReportStatus::Draft, $report->status());
        $this->assertTrue($report->isEditableByOwner());
        $this->assertInstanceOf(CarbonFootprintReportTextsDrafted::class, $this->spySubscriber->lastEvent());
        $this->assertSame(
            ['executiveSummary', 'methodology', 'emissionFactorSources', 'analysisAndRecommendations', 'compensation'],
            array_keys($report->texts()->sections),
        );
        $this->assertSame([0, 1, 2, 3, 4], array_map(static fn($section) => $section->position(), $report->sections()));
    }

    public function testChangeTextsReordersAndRemovesSections(): void
    {
        $report = CarbonFootprintReportMother::draft();

        $report->changeTexts(new CarbonFootprintReportTexts([
            'analysisAndRecommendations' => 'analysis',
            'executiveSummary' => 'summary',
            'methodology' => 'methodology',
            'emissionFactorSources' => 'sources',
        ]));

        $this->assertInstanceOf(CarbonFootprintReportTextsChanged::class, $this->spySubscriber->lastEvent());
        $this->assertSame(['analysisAndRecommendations', 'executiveSummary', 'methodology', 'emissionFactorSources'], array_keys($report->texts()->sections));
        $this->assertSame('summary', $report->texts()->get(CarbonFootprintReportSectionKey::ExecutiveSummary));
        $this->assertFalse($report->texts()->has(CarbonFootprintReportSectionKey::Compensation));
    }

    public function testChangeTextsIsAllowedWhilePendingReview(): void
    {
        $report = CarbonFootprintReportMother::pendingReview();

        $report->changeTexts(CarbonFootprintReportTextsMother::forEvent(false));

        $this->assertCount(4, $report->sections());
        $this->assertTrue($report->isReviewable());
    }

    public function testChangeTextsWhileGeneratingPdfIsNotAllowed(): void
    {
        $report = CarbonFootprintReportMother::generatingPdf();

        $this->expectException(CarbonFootprintReportTransitionNotAllowedException::class);

        $report->changeTexts(CarbonFootprintReportTextsMother::forEvent());
    }

    public function testSubmitForReviewFromGeneratingTextsRequiresTexts(): void
    {
        $report = CarbonFootprintReportMother::registeredFor();

        $this->expectException(CarbonFootprintReportTransitionNotAllowedException::class);

        $report->submitForReview();
    }

    public function testSubmitForReviewFromGeneratingTextsWithTexts(): void
    {
        $report = CarbonFootprintReportMother::registeredFor(CarbonFootprintReportSubjectType::Company);

        $report->submitForReview(CarbonFootprintReportTextsMother::forOrganization());

        $this->assertSame(CarbonFootprintReportStatus::PendingReview, $report->status());
        $this->assertNotNull($report->submittedAt());
        $this->assertCount(4, $report->sections());
        $this->assertInstanceOf(CarbonFootprintReportSubmittedForReview::class, $this->spySubscriber->lastEvent());
    }

    public function testSubmitForReviewFromDraftKeepsTheDraftedTexts(): void
    {
        $report = CarbonFootprintReportMother::draft();

        $report->submitForReview();

        $this->assertSame(CarbonFootprintReportStatus::PendingReview, $report->status());
        $this->assertCount(5, $report->sections());
    }

    public function testSubmitForReviewWithAnEmptyRequiredSectionThrows(): void
    {
        $report = CarbonFootprintReportMother::draft();

        $this->expectException(CarbonFootprintReportTextsIncompleteException::class);

        $report->submitForReview(new CarbonFootprintReportTexts([
            'executiveSummary' => 'summary',
            'methodology' => '   ',
            'emissionFactorSources' => 'sources',
            'analysisAndRecommendations' => 'analysis',
        ]));
    }

    public function testAutomaticApprovalFromGeneratingTexts(): void
    {
        $reviewer = UserId::generate();
        $report = CarbonFootprintReportMother::registeredFor();

        $report->approve($reviewer, true, CarbonFootprintReportTextsMother::forEvent());

        $this->assertSame(CarbonFootprintReportStatus::GeneratingPdf, $report->status());
        $this->assertTrue($report->isAutoApproved());
        $this->assertNotNull($report->approvedAt());
        $this->assertEquals($reviewer, $report->reviewedBy());
        $this->assertInstanceOf(CarbonFootprintReportApproved::class, $this->spySubscriber->lastEvent());
    }

    public function testAutomaticApprovalFromDraft(): void
    {
        $report = CarbonFootprintReportMother::draft();

        $report->approve(UserId::generate(), true);

        $this->assertSame(CarbonFootprintReportStatus::GeneratingPdf, $report->status());
        $this->assertCount(5, $report->sections());
    }

    public function testReviewerApprovalFromPendingReviewReplacesTheTexts(): void
    {
        $report = CarbonFootprintReportMother::pendingReview();

        $report->approve(UserId::generate(), false, CarbonFootprintReportTextsMother::forEvent(false));

        $this->assertSame(CarbonFootprintReportStatus::GeneratingPdf, $report->status());
        $this->assertFalse($report->isAutoApproved());
        $this->assertCount(4, $report->sections());
    }

    public function testReviewerApprovalFromDraftIsNotAllowed(): void
    {
        $report = CarbonFootprintReportMother::draft();

        $this->expectException(CarbonFootprintReportTransitionNotAllowedException::class);

        $report->approve(UserId::generate(), false);
    }

    public function testAutomaticApproveFromPendingReviewIsNotAllowed(): void
    {
        $report = CarbonFootprintReportMother::pendingReview();

        $this->expectException(CarbonFootprintReportTransitionNotAllowedException::class);

        $report->approve(UserId::generate(), true);
    }

    public function testApproveFromGeneratedIsNotAllowed(): void
    {
        $report = CarbonFootprintReportMother::generated();

        $this->expectException(CarbonFootprintReportTransitionNotAllowedException::class);

        $report->approve(UserId::generate(), true);
    }

    public function testRejectFromPendingReview(): void
    {
        $reviewer = UserId::generate();
        $report = CarbonFootprintReportMother::pendingReview();

        $report->reject($reviewer, 'Revisa las cifras');

        $this->assertSame(CarbonFootprintReportStatus::Rejected, $report->status());
        $this->assertSame('Revisa las cifras', $report->reviewComment());
        $this->assertEquals($reviewer, $report->reviewedBy());
        $this->assertNotNull($report->reviewedAt());
        $this->assertInstanceOf(CarbonFootprintReportRejected::class, $this->spySubscriber->lastEvent());
    }

    public function testRejectFromDraftIsNotAllowed(): void
    {
        $report = CarbonFootprintReportMother::draft();

        $this->expectException(CarbonFootprintReportTransitionNotAllowedException::class);

        $report->reject(UserId::generate(), 'nope');
    }

    public function testRejectWithoutCommentThrows(): void
    {
        $report = CarbonFootprintReportMother::pendingReview();

        $this->expectException(CarbonFootprintReportRejectionCommentRequiredException::class);

        $report->reject(UserId::generate(), '  ');
    }

    public function testTranslateAndGenerateFromGeneratingPdf(): void
    {
        $report = CarbonFootprintReportMother::generatingPdf();

        $report->translate('shared/report-es.pdf', Locale::Spanish);
        $report->translate('shared/report-en.pdf', Locale::English);
        $report->generate();

        $this->assertSame(CarbonFootprintReportStatus::Generated, $report->status());
        $this->assertTrue($report->isDownloadable());
        $this->assertSame('shared/report-es.pdf', $report->filePathFor(Locale::Spanish));
        $this->assertSame('shared/report-en.pdf', $report->filePathFor(Locale::English));
        $this->assertNull($report->filePathFor(Locale::French));
        $this->assertNull($report->errorMessage());
        $this->assertInstanceOf(CarbonFootprintReportGenerated::class, $this->spySubscriber->lastEvent());
    }

    public function testTranslateWhileGeneratingTextsIsNotAllowed(): void
    {
        $report = CarbonFootprintReportMother::registeredFor();

        $this->expectException(CarbonFootprintReportTransitionNotAllowedException::class);

        $report->translate('shared/report-es.pdf', Locale::Spanish);
    }

    public function testFailPublishesEventAndSetsFailedStatus(): void
    {
        $report = CarbonFootprintReportMother::registeredFor();

        $report->fail('something went wrong');

        $this->assertSame(CarbonFootprintReportStatus::Failed, $report->status());
        $this->assertSame('something went wrong', $report->errorMessage());
        $this->assertInstanceOf(CarbonFootprintReportFailed::class, $this->spySubscriber->lastEvent());
    }

    public function testFailFromDraftIsNotAllowed(): void
    {
        $report = CarbonFootprintReportMother::draft();

        $this->expectException(CarbonFootprintReportTransitionNotAllowedException::class);

        $report->fail('boom');
    }

    public function testReissueClearsSectionsAndReviewDataButKeepsTheFiles(): void
    {
        $report = CarbonFootprintReportMother::generated();
        $previousGeneratedAt = $report->generatedAt();

        $report->reissue();

        $this->assertSame(CarbonFootprintReportStatus::GeneratingTexts, $report->status());
        $this->assertCount(0, $report->sections());
        $this->assertNull($report->approvedAt());
        $this->assertNull($report->reviewedBy());
        $this->assertNull($report->submittedAt());
        $this->assertFalse($report->isAutoApproved());
        $this->assertSame($previousGeneratedAt, $report->generatedAt());
        $this->assertSame('shared/report-es.pdf', $report->filePathFor(Locale::Spanish));
        $this->assertInstanceOf(CarbonFootprintReportReissued::class, $this->spySubscriber->lastEvent());
    }

    public function testReissueFromDraftIsNotAllowed(): void
    {
        $report = CarbonFootprintReportMother::draft();

        $this->expectException(CarbonFootprintReportTransitionNotAllowedException::class);

        $report->reissue();
    }

    public function testRetryWithoutApprovalGoesBackToGeneratingTextsWithoutSections(): void
    {
        $report = CarbonFootprintReportMother::failedBeforeApproval();

        $report->retry();

        $this->assertSame(CarbonFootprintReportStatus::GeneratingTexts, $report->status());
        $this->assertCount(0, $report->sections());
        $this->assertNull($report->errorMessage());
        $this->assertInstanceOf(CarbonFootprintReportRetried::class, $this->spySubscriber->lastEvent());
    }

    public function testRetryAfterApprovalGoesBackToGeneratingPdfKeepingTheSections(): void
    {
        $report = CarbonFootprintReportMother::failedAfterApproval();

        $report->retry();

        $this->assertSame(CarbonFootprintReportStatus::GeneratingPdf, $report->status());
        $this->assertCount(5, $report->sections());
        $this->assertNotNull($report->approvedAt());
    }

    public function testRetryFromGeneratedIsNotAllowed(): void
    {
        $report = CarbonFootprintReportMother::generated();

        $this->expectException(CarbonFootprintReportTransitionNotAllowedException::class);

        $report->retry();
    }

    public function testRemovePublishesEvent(): void
    {
        $report = CarbonFootprintReportMother::generated();

        $report->remove();

        $this->assertInstanceOf(CarbonFootprintReportRemoved::class, $this->spySubscriber->lastEvent());
    }

    public function testFilePathForReturnsNullWhileGeneratingTexts(): void
    {
        $report = CarbonFootprintReportMother::registeredFor();

        $this->assertNull($report->filePathFor(Locale::Spanish));
    }

    public function testTranslationForCurrentLocaleFallsBackToDefaultLocale(): void
    {
        $report = CarbonFootprintReportMother::generated();

        LocaleGetter::setLocale(Locale::French);

        try {
            $translation = $report->currentTranslation();

            $this->assertNotNull($translation);
            $this->assertSame(LocaleGetter::default(), $translation->locale());
            $this->assertSame('shared/report-es.pdf', $translation->filePath());
        } finally {
            LocaleGetter::setLocale(null);
        }
    }

    public function testRegisterAcceptsAnyFootprintIdentifier(): void
    {
        $footprintId = (string) EventCarbonFootprintId::generate();
        $report = CarbonFootprintReportMother::registeredFor(CarbonFootprintReportSubjectType::Event, $footprintId);

        $this->assertSame($footprintId, $report->carbonFootprintId());
    }
}
