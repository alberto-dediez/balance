<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprint\Materiality;

use App\CarbonFootprint\Application\CarbonFootprint\Materiality\MaterialitySummaryBuilder;
use App\CarbonFootprint\Application\CarbonFootprint\Materiality\MaterialitySummaryRow;
use App\CarbonFootprint\Application\CarbonFootprint\Materiality\MaterialitySummaryTopicInput;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityCategoryState;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecisionInput;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\User\UserId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecisionInputMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use PHPUnit\Framework\TestCase;

final class MaterialitySummaryBuilderTest extends TestCase
{
    public function testEachCategoryGetsItsState(): void
    {
        $decisions = [
            MaterialityDecisionInputMother::omit(5),
            MaterialityDecisionInputMother::calculate(1),
            MaterialityDecisionInputMother::omit(2),
            MaterialityDecisionInputMother::calculate(6),
            MaterialityDecisionInputMother::omit(4),
            MaterialityDecisionInputMother::omit(null),
            MaterialityDecisionInputMother::omit(14),
        ];
        $footprint = PropertyCarbonFootprintMother::withMaterialityAnswered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate());
        $footprint->addActivity(ActivityId::generate(), new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport));
        $footprint->answerScope(Scope::Scope3);
        $footprint->applyMaterialityDecisions($decisions, []);
        $footprint->includeSource(Scope::Scope3, Source::CapitalGoods);
        $footprint->overrideMaterialityDecision(Source::CapitalGoods, UserId::generate(), 'Tenemos datos');
        $footprint->excludeSource(Scope::Scope3, Source::BusinessTravel, ExclusionReason::OutOfScope);
        $footprint->overrideMaterialityDecision(Source::BusinessTravel, UserId::generate(), 'Fuera del perímetro');

        $topics = array_map(static fn(MaterialityDecisionInput $decision) => self::topicOf($decision), $decisions);
        $topics[] = new MaterialitySummaryTopicInput('topic-10', 10, null, null, false, null);

        $rows = (new MaterialitySummaryBuilder())->build($footprint, CarbonFootprintReportSubjectType::Property, $topics);
        $states = $this->statesByTopic($rows);

        $this->assertSame(MaterialityCategoryState::ExcludedRecommended, $states['topic-5']);
        $this->assertSame(MaterialityCategoryState::Active, $states['topic-1']);
        $this->assertSame(MaterialityCategoryState::ActiveOverridden, $states['topic-2']);
        $this->assertSame(MaterialityCategoryState::ExcludedOverridden, $states['topic-6']);
        $this->assertSame(MaterialityCategoryState::PendingReview, $states['topic-4']);
        $this->assertSame(MaterialityCategoryState::WithoutSource, $states['topic-custom']);
        $this->assertSame(MaterialityCategoryState::NotApplicableToSubject, $states['topic-14']);
        $this->assertSame(MaterialityCategoryState::WithoutScore, $states['topic-10']);

        $notEvaluated = array_values(array_filter($rows, static fn(MaterialitySummaryRow $row) => MaterialityCategoryState::NotEvaluated === $row->state));
        $notEvaluatedSources = array_map(static fn(MaterialitySummaryRow $row) => $row->source, $notEvaluated);
        $this->assertContains(Source::FuelAndEnergyRelated, $notEvaluatedSources);
        $this->assertNotContains(Source::Franchises, $notEvaluatedSources);
        $this->assertNotContains(Source::OperationsWaste, $notEvaluatedSources);
        $this->assertNull($notEvaluated[0]->topicId);
    }

    public function testAManualExclusionOfAMaterialCategoryIsPendingOfReviewAlsoInTheFootprint(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope3Answered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate());
        $footprint->excludeSource(Scope::Scope3, Source::PurchasedGoodsAndServices, ExclusionReason::NoDataAvailable);
        $footprint->applyMaterialityDecisions([MaterialityDecisionInputMother::calculate(1)], []);

        $rows = (new MaterialitySummaryBuilder())->build($footprint, CarbonFootprintReportSubjectType::Property, [self::topicOf(MaterialityDecisionInputMother::calculate(1))]);

        $this->assertSame(MaterialityCategoryState::PendingReview, $this->statesByTopic($rows)['topic-1']);
        $this->assertTrue($footprint->hasPendingMaterialityReview());
    }

    public function testAScoredCategoryWithoutVerdictIsNotEvaluated(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityAnswered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate());

        $rows = (new MaterialitySummaryBuilder())->build($footprint, CarbonFootprintReportSubjectType::Property, [
            new MaterialitySummaryTopicInput('topic-5', 5, 62.5, null, false, null),
            new MaterialitySummaryTopicInput('topic-10', 10, null, null, false, null),
        ]);
        $states = $this->statesByTopic($rows);

        $this->assertSame(62.5, $rows[0]->scorePercent);
        $this->assertSame(MaterialityCategoryState::NotEvaluated, $states['topic-5']);
        $this->assertSame(MaterialityCategoryState::WithoutScore, $states['topic-10']);
    }

    public function testAnExcludedScope3MarksEveryCategory(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityDecisions([MaterialityDecisionInputMother::omit(5)]);
        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NotApplicable);

        $rows = (new MaterialitySummaryBuilder())->build($footprint, CarbonFootprintReportSubjectType::Property, [self::topicOf(MaterialityDecisionInputMother::omit(5))]);

        foreach ($rows as $row) {
            $this->assertSame(MaterialityCategoryState::StageExcluded, $row->state);
        }
    }

    public function testADraftAssessmentShowsTheLastAppliedDecision(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityDecisions([MaterialityDecisionInputMother::omit(5, 38.0)]);

        $rows = (new MaterialitySummaryBuilder())->build($footprint, CarbonFootprintReportSubjectType::Property, [
            new MaterialitySummaryTopicInput('topic-5', 5, null, null, false, null),
        ]);

        $this->assertSame(38.0, $rows[0]->scorePercent);
        $this->assertSame(50, $rows[0]->thresholdPercent);
        $this->assertSame(MaterialityCategoryState::ExcludedRecommended, $rows[0]->state);
        $this->assertNotNull($rows[0]->decision);
        $this->assertNotNull($rows[0]->exclusion);
    }

    private static function topicOf(MaterialityDecisionInput $decision): MaterialitySummaryTopicInput
    {
        return new MaterialitySummaryTopicInput($decision->topicId, $decision->topicNumber, $decision->scorePercent, $decision->thresholdPercent, $decision->forcedByHighMagnitude, $decision->verdict);
    }

    /**
     * @param list<MaterialitySummaryRow> $rows
     *
     * @return array<string, MaterialityCategoryState>
     */
    private function statesByTopic(array $rows): array
    {
        $states = [];

        foreach ($rows as $row) {
            if (null !== $row->topicId) {
                $states[$row->topicId] = $row->state;
            }
        }

        return $states;
    }
}
