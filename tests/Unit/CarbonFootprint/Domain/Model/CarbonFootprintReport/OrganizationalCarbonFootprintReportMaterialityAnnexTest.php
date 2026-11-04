<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityCategoryState;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAssessmentSnapshot;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportMaterialityAnnex;
use PHPUnit\Framework\TestCase;

final class OrganizationalCarbonFootprintReportMaterialityAnnexTest extends TestCase
{
    public function testAnAnnexWithoutAssessmentNorDecisionsIsEmpty(): void
    {
        $this->assertTrue(OrganizationalCarbonFootprintReportMaterialityAnnexMother::withoutAssessment()->isEmpty());
        $this->assertFalse(OrganizationalCarbonFootprintReportMaterialityAnnexMother::complete()->isEmpty());
        $this->assertFalse(new OrganizationalCarbonFootprintReportMaterialityAnnex(null, true, [], [])->isEmpty());
        $this->assertFalse(new OrganizationalCarbonFootprintReportMaterialityAnnex(MaterialityAssessmentSnapshotMother::draft(), false, [], [])->isEmpty());
    }

    public function testTheAnswersAreShownOnlyWithACompletedAssessment(): void
    {
        $this->assertTrue(OrganizationalCarbonFootprintReportMaterialityAnnexMother::complete()->showsAnswers());
        $this->assertFalse(OrganizationalCarbonFootprintReportMaterialityAnnexMother::reopened()->showsAnswers());
        $this->assertFalse(OrganizationalCarbonFootprintReportMaterialityAnnexMother::withoutAssessment()->showsAnswers());
        $this->assertTrue(OrganizationalCarbonFootprintReportMaterialityAnnexMother::complete()->hasAssessment());
        $this->assertFalse(OrganizationalCarbonFootprintReportMaterialityAnnexMother::withoutAssessment()->hasAssessment());
    }

    public function testTheAnalysisIsMissingWithoutDecisionsNorCompletedVerdicts(): void
    {
        $completed = MaterialityAssessmentSnapshotMother::completed();
        $completedWithoutVerdicts = new MaterialityAssessmentSnapshot(
            assessmentId: $completed->assessmentId,
            completed: true,
            startedAt: $completed->startedAt,
            completedAt: $completed->completedAt,
            completedByName: $completed->completedByName,
            sectorName: $completed->sectorName,
            thresholdPercent: $completed->thresholdPercent,
            highMagnitudeRule: $completed->highMagnitudeRule,
            questionSetVersion: $completed->questionSetVersion,
            questionSetChangedSince: false,
            topics: [],
            answers: [],
        );

        $this->assertTrue(OrganizationalCarbonFootprintReportMaterialityAnnexMother::withoutAssessment()->isAnalysisMissing());
        $this->assertTrue(new OrganizationalCarbonFootprintReportMaterialityAnnex(MaterialityAssessmentSnapshotMother::draft(), false, [], [])->isAnalysisMissing());
        $this->assertTrue(new OrganizationalCarbonFootprintReportMaterialityAnnex($completedWithoutVerdicts, false, [], [])->isAnalysisMissing());
        $this->assertFalse(new OrganizationalCarbonFootprintReportMaterialityAnnex($completed, false, [], [])->isAnalysisMissing());
        $this->assertFalse(OrganizationalCarbonFootprintReportMaterialityAnnexMother::reopened()->isAnalysisMissing());
        $this->assertFalse(new OrganizationalCarbonFootprintReportMaterialityAnnex(null, true, [], [])->isAnalysisMissing());
    }

    public function testTheAssessmentIsReopenedOnlyWhenItIsADraft(): void
    {
        $this->assertTrue(OrganizationalCarbonFootprintReportMaterialityAnnexMother::reopened()->isReopened());
        $this->assertTrue(new OrganizationalCarbonFootprintReportMaterialityAnnex(MaterialityAssessmentSnapshotMother::draft(), false, [], [])->isReopened());
        $this->assertFalse(OrganizationalCarbonFootprintReportMaterialityAnnexMother::complete()->isReopened());
        $this->assertFalse(OrganizationalCarbonFootprintReportMaterialityAnnexMother::withoutAssessment()->isReopened());
        $this->assertFalse(new OrganizationalCarbonFootprintReportMaterialityAnnex(null, true, [], [])->isReopened());
    }

    public function testTheTopicOfASourceIsFoundByItsCategory(): void
    {
        $annex = OrganizationalCarbonFootprintReportMaterialityAnnexMother::complete();

        $topic = $annex->topicFor(Source::SoldProductsProcessing);

        $this->assertNotNull($topic);
        $this->assertSame(10, $topic->number);
        $this->assertSame(MaterialityCategoryState::ExcludedRecommended, $topic->state);
        $this->assertNull($annex->topicFor(Source::Investments));
    }

    public function testTheJustificationsCoverTheExcludedCategoriesWithTheirAnswers(): void
    {
        $annex = OrganizationalCarbonFootprintReportMaterialityAnnexMother::complete();

        $this->assertSame([10, 11], array_map(static fn($justification): ?int => $justification->topic->number, $annex->justifications));
        $this->assertCount(5, $annex->justifications[0]->answers);
        $this->assertSame(6, $annex->justifications[0]->points);
    }

    public function testTheNarrativeIsRequestedOnlyWhenTheAnalysisAppliesAndThereAreExcludedCategories(): void
    {
        $this->assertTrue(OrganizationalCarbonFootprintReportMaterialityAnnexMother::complete()->requestsNarrative(false));
        $this->assertTrue(OrganizationalCarbonFootprintReportMaterialityAnnexMother::reopened()->requestsNarrative(false));
        $this->assertFalse(OrganizationalCarbonFootprintReportMaterialityAnnexMother::complete()->requestsNarrative(true));
        $this->assertFalse(OrganizationalCarbonFootprintReportMaterialityAnnexMother::stageExcluded()->requestsNarrative(false));
        $this->assertFalse(OrganizationalCarbonFootprintReportMaterialityAnnexMother::withoutAssessment()->requestsNarrative(false));
        $this->assertFalse(new OrganizationalCarbonFootprintReportMaterialityAnnex(MaterialityAssessmentSnapshotMother::completed(), false, [], [])->requestsNarrative(false));
    }
}
