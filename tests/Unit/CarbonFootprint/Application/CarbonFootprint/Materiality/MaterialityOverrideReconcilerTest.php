<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprint\Materiality;

use App\CarbonFootprint\Application\CarbonFootprint\Materiality\MaterialityOverrideReconciler;
use App\CarbonFootprint\Application\CarbonFootprint\StageExclusionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityOverrideJustificationRequiredException;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\Security\Domain\Model\User\UserId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecisionInputMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use PHPUnit\Framework\TestCase;

final class MaterialityOverrideReconcilerTest extends TestCase
{
    public function testAnIncludedRecommendedExclusionWithoutJustificationThrows(): void
    {
        $this->expectException(MaterialityOverrideJustificationRequiredException::class);

        $this->sut()->validate($this->footprint(), Scope::Scope3, [], null, []);
    }

    public function testAnExcludedMaterialSourceWithoutJustificationThrows(): void
    {
        $this->expectException(MaterialityOverrideJustificationRequiredException::class);

        $this->sut()->validate($this->footprint(), Scope::Scope3, $this->exclusions(Source::OperationsWaste, Source::PurchasedGoodsAndServices), null, []);
    }

    public function testTheRecommendedStateNeedsNoJustification(): void
    {
        $this->sut()->validate($this->footprint(), Scope::Scope3, $this->exclusions(Source::OperationsWaste), null, []);

        $this->addToAssertionCount(1);
    }

    public function testAJustificationOrAnExistingOverrideAllowsTheDivergence(): void
    {
        $footprint = $this->footprint();
        $footprint->overrideMaterialityDecision(Source::PurchasedGoodsAndServices, UserId::generate(), 'Fuera del perímetro');

        $this->sut()->validate($footprint, Scope::Scope3, $this->exclusions(Source::PurchasedGoodsAndServices), null, [Source::OperationsWaste->value => 'Tenemos datos']);

        $this->addToAssertionCount(1);
    }

    public function testAStageExclusionOrAnotherScopeSkipsTheValidation(): void
    {
        $this->sut()->validate($this->footprint(), Scope::Scope3, [], new StageExclusionAnswer(ExclusionReason::NoDataAvailable), []);
        $this->sut()->validate($this->footprint(), Scope::Scope1, [], null, []);

        $this->addToAssertionCount(1);
    }

    public function testCompanyOnlySourcesAreIgnoredInAPropertyFootprint(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityDecisions([MaterialityDecisionInputMother::omit(14)]);

        $this->sut()->validate($footprint, Scope::Scope3, [], null, []);

        $this->addToAssertionCount(1);
    }

    public function testReconcileRecordsAndRevertsTheOverrides(): void
    {
        $footprint = $this->footprint();
        $userId = UserId::generate();
        $footprint->includeSource(Scope::Scope3, Source::OperationsWaste);

        $this->sut()->reconcile($footprint, Scope::Scope3, $userId, [Source::OperationsWaste->value => '  Tenemos datos  ']);

        $this->assertSame('Tenemos datos', $footprint->materialityDecisionFor(Source::OperationsWaste)?->overrideJustification());
        $this->assertFalse($footprint->materialityDecisionFor(Source::PurchasedGoodsAndServices)?->isOverridden());

        $footprint->excludeSource(Scope::Scope3, Source::OperationsWaste, ExclusionReason::NotSignificant);
        $this->sut()->reconcile($footprint, Scope::Scope3, $userId, []);

        $this->assertFalse($footprint->materialityDecisionFor(Source::OperationsWaste)?->isOverridden());
    }

    public function testReconcileKeepsTheExistingJustification(): void
    {
        $footprint = $this->footprint();
        $footprint->includeSource(Scope::Scope3, Source::OperationsWaste);
        $this->sut()->reconcile($footprint, Scope::Scope3, UserId::generate(), [Source::OperationsWaste->value => 'Tenemos datos']);

        $this->sut()->reconcile($footprint, Scope::Scope3, UserId::generate(), []);

        $this->assertSame('Tenemos datos', $footprint->materialityDecisionFor(Source::OperationsWaste)?->overrideJustification());
    }

    private function footprint(): PropertyCarbonFootprint
    {
        return PropertyCarbonFootprintMother::withMaterialityDecisions([
            MaterialityDecisionInputMother::omit(5),
            MaterialityDecisionInputMother::calculate(1),
        ]);
    }

    /** @return array<string, ExclusionAnswer> */
    private function exclusions(Source ...$sources): array
    {
        $exclusions = [];

        foreach ($sources as $source) {
            $exclusions[$source->value] = new ExclusionAnswer($source, ExclusionReason::NotSignificant);
        }

        return $exclusions;
    }

    private function sut(): MaterialityOverrideReconciler
    {
        return new MaterialityOverrideReconciler(new FakeTranslator());
    }
}
