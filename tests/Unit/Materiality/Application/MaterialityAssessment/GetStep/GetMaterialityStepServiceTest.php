<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialityAssessment\GetStep;

use App\CarbonFootprint\Application\CarbonFootprint\Materiality\MaterialitySummaryBuilder;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityCategoryState;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\Materiality\Application\MaterialityAssessment\GetStep\GetMaterialityStepRequest;
use App\Materiality\Application\MaterialityAssessment\GetStep\GetMaterialityStepService;
use App\Materiality\Application\MaterialityAssessment\GetStep\MaterialityQuestionnaireBoardBuilder;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityScope;
use App\Materiality\Domain\Model\MaterialityTopic\StandardScope3Category;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecisionInputMother;
use App\Tests\Unit\Materiality\Application\MaterialityAssessment\MaterialityAssessmentContextTrait;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialitySector\MaterialitySectorMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicMother;
use PHPUnit\Framework\TestCase;

final class GetMaterialityStepServiceTest extends TestCase
{
    use MaterialityAssessmentContextTrait;

    protected function setUp(): void
    {
        $this->initMaterialityAssessment();
    }

    public function testWithoutAssessmentReturnsTheSectorsAndThePreviousCompletedAssessment(): void
    {
        $sectorId = MaterialitySectorId::generate();
        $this->materialitySectors->add(MaterialitySectorMother::fromNameAndId('Logística', (string) $sectorId));
        $propertyId = PropertyId::generate();
        $previousFootprint = $this->propertyFootprint(year: 2024, propertyId: $propertyId);
        $footprint = $this->propertyFootprint(year: 2025, propertyId: $propertyId);
        $this->materialityAssessments->add(MaterialityAssessmentMother::completed(carbonFootprintId: (string) $previousFootprint->id(), year: 2024, sectorId: $sectorId));

        $response = $this->sut()(new GetMaterialityStepRequest(MaterialityAssessmentSubjectType::Property, (string) $footprint->id()), $this->ownerOf());

        $this->assertNull($response->assessment);
        $this->assertCount(1, $response->sectors);
        $this->assertSame(2024, $response->previousYear);
        $this->assertEquals($sectorId, $response->previousSectorId);
        $this->assertFalse($response->footprintCompleted);
        $this->assertTrue($response->materialityAnswered);
        $this->assertNull($response->board);
    }

    public function testAPreviousDraftAssessmentIsNotOffered(): void
    {
        $propertyId = PropertyId::generate();
        $previousFootprint = $this->propertyFootprint(year: 2024, propertyId: $propertyId);
        $footprint = $this->propertyFootprint(year: 2025, propertyId: $propertyId);
        $this->materialityAssessments->add(MaterialityAssessmentMother::draft(carbonFootprintId: (string) $previousFootprint->id(), year: 2024));

        $response = $this->sut()(new GetMaterialityStepRequest(MaterialityAssessmentSubjectType::Property, (string) $footprint->id()), $this->ownerOf());

        $this->assertNull($response->previousYear);
        $this->assertNull($response->previousSectorId);
    }

    public function testReturnsTheCurrentAssessment(): void
    {
        $footprint = $this->scope2AnsweredPropertyFootprint();
        $assessment = $this->assessmentOf($footprint);

        $response = $this->sut()(new GetMaterialityStepRequest(MaterialityAssessmentSubjectType::Property, (string) $footprint->id()), $this->ownerOf());

        $this->assertSame($assessment, $response->assessment);
        $this->assertFalse($response->materialityAnswered);
        $this->assertNotNull($response->board);
        $this->assertCount(1, $response->board->groups);
        $this->assertSame($assessment->totalAnswers(), $response->board->total);
        $this->assertNull($response->board->inheritedFromYear);
    }

    public function testTheBoardCarriesThePreviousYearWhenThereAreInheritedAnswers(): void
    {
        $propertyId = PropertyId::generate();
        $previousFootprint = $this->propertyFootprint(year: 2024, propertyId: $propertyId);
        $footprint = $this->propertyFootprint(year: 2025, propertyId: $propertyId);
        $topic = MaterialityTopicMother::random();
        $question = MaterialityQuestionMother::random();
        $this->materialityAssessments->add(MaterialityAssessmentMother::completed(carbonFootprintId: (string) $previousFootprint->id(), year: 2024, topics: [$topic], questions: [$question]));
        $this->materialityAssessments->add(MaterialityAssessmentMother::draft(
            carbonFootprintId: (string) $footprint->id(),
            topics: [$topic],
            questions: [$question],
            initialValues: [$topic->id() . '|' . $question->id() => 1],
        ));

        $response = $this->sut()(new GetMaterialityStepRequest(MaterialityAssessmentSubjectType::Property, (string) $footprint->id()), $this->ownerOf());

        $this->assertSame(2024, $response->board?->inheritedFromYear);
    }

    public function testTheBoardHasNoInheritedYearWithoutInheritedAnswers(): void
    {
        $propertyId = PropertyId::generate();
        $previousFootprint = $this->propertyFootprint(year: 2024, propertyId: $propertyId);
        $footprint = $this->propertyFootprint(year: 2025, propertyId: $propertyId);
        $this->materialityAssessments->add(MaterialityAssessmentMother::completed(carbonFootprintId: (string) $previousFootprint->id(), year: 2024));
        $this->materialityAssessments->add(MaterialityAssessmentMother::draft(carbonFootprintId: (string) $footprint->id()));

        $response = $this->sut()(new GetMaterialityStepRequest(MaterialityAssessmentSubjectType::Property, (string) $footprint->id()), $this->ownerOf());

        $this->assertSame(2024, $response->previousYear);
        $this->assertNull($response->board?->inheritedFromYear);
    }

    public function testACompletedAssessmentReturnsTheSummaryRows(): void
    {
        $footprint = $this->scope2AnsweredPropertyFootprint();
        $footprint->answerMateriality();
        $assessment = MaterialityAssessmentMother::completed(
            carbonFootprintId: (string) $footprint->id(),
            topics: [MaterialityTopicMother::standard(StandardScope3Category::from(5))],
            value: 1,
        );
        $this->materialityAssessments->add($assessment);
        $footprint->applyMaterialityDecisions([MaterialityDecisionInputMother::omit(5, 0.0, topicId: (string) $assessment->topics()[0]->topicId())], []);

        $response = $this->sut()(new GetMaterialityStepRequest(MaterialityAssessmentSubjectType::Property, (string) $footprint->id()), $this->ownerOf());

        $this->assertNotEmpty($response->summaryRows);
        $this->assertSame(MaterialityCategoryState::ExcludedRecommended, $response->summaryRows[0]->state);
        $this->assertSame(Source::OperationsWaste, $response->summaryRows[0]->source);
        $this->assertSame(MaterialityCategoryState::NotEvaluated, $response->summaryRows[array_key_last($response->summaryRows)]->state);
        $this->assertFalse($response->stageExcluded);
        $this->assertFalse($response->pendingReview);
    }

    public function testANumberedCategoryOfAnotherScopeHasNoSource(): void
    {
        $footprint = $this->scope2AnsweredPropertyFootprint();
        $footprint->answerMateriality();
        $this->materialityAssessments->add(MaterialityAssessmentMother::completed(
            carbonFootprintId: (string) $footprint->id(),
            topics: [MaterialityTopicMother::from(name: 'Combustión propia', id: 'T-S1', scope: MaterialityScope::Scope1, direction: null, number: 5)],
            value: 1,
        ));

        $response = $this->sut()(new GetMaterialityStepRequest(MaterialityAssessmentSubjectType::Property, (string) $footprint->id()), $this->ownerOf());

        $this->assertNull($response->summaryRows[0]->source);
        $this->assertSame(MaterialityCategoryState::WithoutSource, $response->summaryRows[0]->state);
        $this->assertContains(Source::OperationsWaste, array_map(static fn($row) => $row->source, array_slice($response->summaryRows, 1)));
    }

    public function testADraftAssessmentWithoutDecisionsHasNoSummary(): void
    {
        $footprint = $this->scope2AnsweredPropertyFootprint();
        $this->assessmentOf($footprint);

        $response = $this->sut()(new GetMaterialityStepRequest(MaterialityAssessmentSubjectType::Property, (string) $footprint->id()), $this->ownerOf());

        $this->assertSame([], $response->summaryRows);
    }

    public function testAReopenedAssessmentHasNoSummaryEvenWithDecisions(): void
    {
        $footprint = $this->scope2AnsweredPropertyFootprint();
        $footprint->answerMateriality();
        $assessment = MaterialityAssessmentMother::completed(
            carbonFootprintId: (string) $footprint->id(),
            topics: [MaterialityTopicMother::standard(StandardScope3Category::from(5))],
            value: 1,
        );
        $this->materialityAssessments->add($assessment);
        $footprint->applyMaterialityDecisions([MaterialityDecisionInputMother::omit(5, 0.0, topicId: (string) $assessment->topics()[0]->topicId())], []);
        $assessment->reopen();

        $response = $this->sut()(new GetMaterialityStepRequest(MaterialityAssessmentSubjectType::Property, (string) $footprint->id()), $this->ownerOf());

        $this->assertSame([], $response->summaryRows);
    }

    public function testOwnerOfAnotherAccountIsDenied(): void
    {
        $footprint = $this->propertyFootprint();

        $this->expectException(UnauthorizedActionException::class);

        $this->sut()(new GetMaterialityStepRequest(MaterialityAssessmentSubjectType::Property, (string) $footprint->id()), $this->ownerOf('other-account'));
    }

    private function sut(): GetMaterialityStepService
    {
        return new GetMaterialityStepService($this->materialityAssessments, $this->materialitySectors, $this->footprints(), new MaterialitySummaryBuilder(), new MaterialityQuestionnaireBoardBuilder());
    }
}
