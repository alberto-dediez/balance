<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\Data;

use App\CarbonFootprint\Application\CarbonFootprint\Materiality\MaterialitySummaryBuilder;
use App\CarbonFootprint\Application\CarbonFootprintReport\Data\MaterialityAnnexBuilder;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityCategoryState;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecisionInput;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAnnexJustification;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAnnexTopicLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportMaterialityAnnex;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecisionInputMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAssessmentSnapshotMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Infrastructure\Materiality\InMemoryMaterialityAssessmentSnapshotProvider;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

final class MaterialityAnnexBuilderTest extends TestCase
{
    private const string FOOTPRINT_ID = 'property-carbon-footprint-id';

    private InMemoryMaterialityAssessmentSnapshotProvider $snapshots;
    private InMemoryUserRepository $users;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->snapshots = new InMemoryMaterialityAssessmentSnapshotProvider();
        $this->users = new InMemoryUserRepository();
    }

    public function testWithoutAssessmentNorDecisionsTheAnnexIsEmpty(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityAnswered('property-id', self::FOOTPRINT_ID);

        $annex = $this->build($footprint);

        $this->assertTrue($annex->isEmpty());
        $this->assertSame([], $annex->justifications);
        $this->assertSame([MaterialityCategoryState::NotEvaluated], array_values(array_unique(array_map(static fn(MaterialityAnnexTopicLine $line) => $line->state, $annex->topics), SORT_REGULAR)));
    }

    public function testWithDecisionsButWithoutAssessmentTheTableComesFromTheDecisions(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityDecisions($this->decisions());

        $annex = $this->build($footprint);

        $this->assertFalse($annex->isEmpty());
        $this->assertFalse($annex->hasAssessment());
        $this->assertTrue($annex->hasDecisions);
        $line = $this->lineOf($annex, 10);
        $this->assertSame(MaterialityCategoryState::ExcludedRecommended, $line->state);
        $this->assertSame(0.0, $line->scorePercent);
        $this->assertSame(50, $line->thresholdPercent);
        $this->assertNull($line->name);
        $this->assertSame(ExclusionReason::NotSignificant, $line->exclusionReason);
        $this->assertNotNull($line->recommendedAt);
        $this->assertSame([10, 11], $this->justifiedNumbers($annex));
        $this->assertSame([], $annex->justifications[0]->answers);
    }

    public function testACompletedAssessmentJustifiesEachExcludedSourceWithItsAnswers(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityDecisions($this->decisions());
        $this->snapshots->add(CarbonFootprintReportSubjectType::Property, self::FOOTPRINT_ID, MaterialityAssessmentSnapshotMother::completed());

        $annex = $this->build($footprint);

        $this->assertTrue($annex->showsAnswers());
        $this->assertSame([10, 11], $this->justifiedNumbers($annex));
        $justification = $annex->justifications[0];
        $this->assertSame('Procesamiento de los productos vendidos', $justification->topic->name);
        $this->assertCount(5, $justification->answers);
        $this->assertSame(6, $justification->points);
        $this->assertSame(6, $justification->minPoints);
        $this->assertSame(18, $justification->maxPoints);
        $this->assertSame(MaterialityCategoryState::Active, $this->lineOf($annex, 1)->state);
        $this->assertTrue($this->lineOf($annex, 2)->forcedByHighMagnitude);
    }

    public function testADraftAssessmentKeepsTheAppliedDecisionsWithoutAnswers(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityDecisions($this->decisions());
        $this->snapshots->add(CarbonFootprintReportSubjectType::Property, self::FOOTPRINT_ID, MaterialityAssessmentSnapshotMother::draft());

        $annex = $this->build($footprint);

        $this->assertFalse($annex->showsAnswers());
        $this->assertSame([10, 11], $this->justifiedNumbers($annex));
        $this->assertSame([], $annex->justifications[0]->answers);
        $this->assertSame(0.0, $annex->justifications[0]->topic->scorePercent);
        $this->assertSame(50, $annex->justifications[0]->topic->thresholdPercent);
    }

    public function testAnOverrideCarriesTheUserNameOrAPlaceholderWhenTheUserNoLongerExists(): void
    {
        $user = UserMother::fromUsernameAndRole('agarcia', Role::Owner);
        $this->users->add($user);
        $footprint = PropertyCarbonFootprintMother::withMaterialityDecisions($this->decisions());
        $footprint->includeSource(Scope::Scope3, Source::SoldProductsUse);
        $footprint->overrideMaterialityDecision(Source::SoldProductsUse, $user->id(), 'Exigencia del cliente principal');
        $footprint->excludeSource(Scope::Scope3, Source::PurchasedGoodsAndServices, ExclusionReason::OutOfScope);
        $footprint->overrideMaterialityDecision(Source::PurchasedGoodsAndServices, UserId::generate(), 'Fuera del perímetro');

        $annex = $this->build($footprint);

        $active = $this->lineOf($annex, 11);
        $this->assertSame(MaterialityCategoryState::ActiveOverridden, $active->state);
        $this->assertSame('agarcia', $active->overriddenByName);
        $this->assertSame('Exigencia del cliente principal', $active->overrideJustification);
        $this->assertNotNull($active->overriddenAt);

        $excluded = $this->lineOf($annex, 1);
        $this->assertSame(MaterialityCategoryState::ExcludedOverridden, $excluded->state);
        $this->assertSame('---', $excluded->overriddenByName);
        $this->assertSame([1, 10], $this->justifiedNumbers($annex));
    }

    public function testTheCategoryWithoutSourceAndThePendingReviewAreNotJustified(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityAnswered('property-id', self::FOOTPRINT_ID);
        $footprint->addActivity(ActivityId::generate(), new ActivityPlacement(Scope::Scope3, Source::BusinessTravel));
        $footprint->answerScope(Scope::Scope3);
        $footprint->applyMaterialityDecisions([MaterialityDecisionInputMother::omit(8), MaterialityDecisionInputMother::omit(6)], []);

        $annex = $this->build($footprint);

        $this->assertSame(MaterialityCategoryState::WithoutSource, $this->lineOf($annex, 8)->state);
        $this->assertSame(MaterialityCategoryState::PendingReview, $this->lineOf($annex, 6)->state);
        $this->assertSame([], $annex->justifications);
    }

    public function testACustomCategoryOfTheAssessmentHasNoSourceAndIsNotJustified(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityDecisions([...$this->decisions(), MaterialityDecisionInputMother::omit(null, 0.0)]);
        $this->snapshots->add(CarbonFootprintReportSubjectType::Property, self::FOOTPRINT_ID, MaterialityAssessmentSnapshotMother::withCustomTopic());

        $annex = $this->build($footprint);

        $custom = array_values(array_filter($annex->topics, static fn(MaterialityAnnexTopicLine $line): bool => MaterialityAssessmentSnapshotMother::topicId(null) === $line->topicId));
        $this->assertCount(1, $custom);
        $this->assertSame('Categoría propia', $custom[0]->name);
        $this->assertSame(MaterialityCategoryState::WithoutSource, $custom[0]->state);
        $this->assertSame([10, 11], $this->justifiedNumbers($annex));
    }

    public function testWithTheScope3ExcludedEveryCategoryIsStageExcluded(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityDecisions($this->decisions());
        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable, 'Sin datos');
        $this->snapshots->add(CarbonFootprintReportSubjectType::Property, self::FOOTPRINT_ID, MaterialityAssessmentSnapshotMother::completed());

        $annex = $this->build($footprint);

        $this->assertSame(MaterialityCategoryState::StageExcluded, $this->lineOf($annex, 10)->state);
        $this->assertSame(MaterialityCategoryState::StageExcluded, $this->lineOf($annex, 1)->state);
        $this->assertSame([], $annex->justifications);
    }

    /** @return list<MaterialityDecisionInput> */
    private function decisions(): array
    {
        return [
            MaterialityDecisionInputMother::calculate(1, 83.33),
            MaterialityDecisionInputMother::omit(10, 0.0),
            MaterialityDecisionInputMother::omit(11, 16.67),
            MaterialityDecisionInputMother::calculate(2, 33.33, forcedByHighMagnitude: true),
        ];
    }

    private function build(PropertyCarbonFootprint $footprint): OrganizationalCarbonFootprintReportMaterialityAnnex
    {
        return new MaterialityAnnexBuilder($this->snapshots, new MaterialitySummaryBuilder(), $this->users)
            ->build($footprint, CarbonFootprintReportSubjectType::Property);
    }

    private function lineOf(OrganizationalCarbonFootprintReportMaterialityAnnex $annex, int $number): MaterialityAnnexTopicLine
    {
        foreach ($annex->topics as $topic) {
            if ($number === $topic->number && null !== $topic->topicId) {
                return $topic;
            }
        }

        $this->fail(sprintf('Category %d not found in the annex', $number));
    }

    /** @return list<?int> */
    private function justifiedNumbers(OrganizationalCarbonFootprintReportMaterialityAnnex $annex): array
    {
        $numbers = array_map(static fn(MaterialityAnnexJustification $justification): ?int => $justification->topic->number, $annex->justifications);
        sort($numbers);

        return $numbers;
    }
}
