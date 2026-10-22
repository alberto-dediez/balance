<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprint;

use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersProcessor;
use App\CarbonFootprint\Application\CarbonFootprint\StageExclusionAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\CombustibleAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ElectricityAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use PHPUnit\Framework\TestCase;

class StageAnswersProcessorTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testValidatesTheSectionsAndExclusionsOfTheStage(): void
    {
        $validated = $this->createSUT()->validate(
            Scope::Scope1,
            [$this->combustiblesSection([$this->combustibleAnswer()])],
            [new ExclusionAnswer(Source::OwnVehicles, ExclusionReason::NotApplicable, 'sin vehículos')],
        );

        $this->assertCount(1, $validated->sectionAnswers);
        $this->assertSame('combustibles', $validated->sectionAnswers[0]->definition->key);
        $this->assertArrayHasKey(Source::OwnVehicles->value, $validated->exclusionsBySource);
    }

    public function testThrowsWhenTheSectionDoesNotBelongToTheStage(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT()->validate(Scope::Scope1, [new SectionAnswer(Scope::Scope2->sections()[0], [])], []);
    }

    public function testThrowsWhenAnAnswerTypeDoesNotMatchTheSectionType(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT()->validate(
            Scope::Scope1,
            [$this->combustiblesSection([new ElectricityAnswer(null, null, new Quantity(10.0, Unit::KWh))])],
            [],
        );
    }

    public function testThrowsWhenAnExcludedSourceStillHasAnswers(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT()->validate(
            Scope::Scope1,
            [$this->combustiblesSection([$this->combustibleAnswer()])],
            [new ExclusionAnswer(Source::FixedFacilities, ExclusionReason::NotApplicable)],
        );
    }

    public function testThrowsWhenTheExcludedSourceDoesNotBelongToTheStage(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT()->validate(Scope::Scope1, [], [new ExclusionAnswer(Source::BuildingElectricity, ExclusionReason::NotApplicable)]);
    }

    public function testApplyExclusionsExcludesTheAnsweredSourcesAndIncludesTheRest(): void
    {
        $propertyCarbonFootprint = $this->registeredFootprint();

        $this->createSUT()->applyExclusions($propertyCarbonFootprint, Scope::Scope1, [
            Source::OwnVehicles->value => new ExclusionAnswer(Source::OwnVehicles, ExclusionReason::NotApplicable, 'sin vehículos'),
        ]);

        $exclusions = $propertyCarbonFootprint->exclusionsFor(Scope::Scope1);
        $this->assertArrayHasKey(Source::OwnVehicles->value, $exclusions);
        $this->assertSame('sin vehículos', $exclusions[Source::OwnVehicles->value]->comment());
        $this->assertArrayNotHasKey(Source::FixedFacilities->value, $exclusions);
    }

    public function testApplyExclusionsIncludesASourceThatWasExcludedBefore(): void
    {
        $propertyCarbonFootprint = $this->registeredFootprint();
        $propertyCarbonFootprint->excludeSource(Scope::Scope1, Source::OwnVehicles, ExclusionReason::NotApplicable);

        $this->createSUT()->applyExclusions($propertyCarbonFootprint, Scope::Scope1, []);

        $this->assertSame([], $propertyCarbonFootprint->exclusionsFor(Scope::Scope1));
    }

    public function testValidatesTheStageExclusionOfAnExcludableStage(): void
    {
        $stageExclusion = new StageExclusionAnswer(ExclusionReason::NoDataAvailable, 'sin datos');

        $validated = $this->createSUT()->validate(Scope::Scope3, [], [], $stageExclusion);

        $this->assertSame($stageExclusion, $validated->stageExclusion);
    }

    public function testThrowsWhenAnExcludedStageStillHasAnswers(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT()->validate(
            Scope::Scope3,
            [],
            [new ExclusionAnswer(Source::Investments, ExclusionReason::NotApplicable)],
            new StageExclusionAnswer(ExclusionReason::NoDataAvailable),
        );
    }

    public function testThrowsWhenTheStageIsNotExcludable(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT()->validate(Scope::Scope1, [], [], new StageExclusionAnswer(ExclusionReason::NoDataAvailable));
    }

    public function testAcceptsTheExclusionOfADerivedSource(): void
    {
        $validated = $this->createSUT()->validate(Scope::Scope3, [], [new ExclusionAnswer(Source::FuelAndEnergyRelated, ExclusionReason::NotSignificant)]);

        $this->assertArrayHasKey(Source::FuelAndEnergyRelated->value, $validated->exclusionsBySource);
    }

    public function testApplyStageExclusionExcludesTheStage(): void
    {
        $propertyCarbonFootprint = $this->registeredFootprint();

        $this->createSUT()->applyStageExclusion($propertyCarbonFootprint, Scope::Scope3, new StageExclusionAnswer(ExclusionReason::NoDataAvailable, 'sin datos'));

        $this->assertTrue($propertyCarbonFootprint->isStageExcluded(Scope::Scope3));
        $this->assertSame('sin datos', $propertyCarbonFootprint->stageExclusionComment(Scope::Scope3));
    }

    public function testApplyStageExclusionWithoutAnswerIncludesTheStage(): void
    {
        $propertyCarbonFootprint = $this->registeredFootprint();
        $propertyCarbonFootprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable);

        $this->createSUT()->applyStageExclusion($propertyCarbonFootprint, Scope::Scope3, null);

        $this->assertFalse($propertyCarbonFootprint->isStageExcluded(Scope::Scope3));
    }

    public function testApplyStageExclusionWithoutAnswerIgnoresNonExcludableStages(): void
    {
        $propertyCarbonFootprint = $this->registeredFootprint();
        $propertyCarbonFootprint->clearEvents();

        $this->createSUT()->applyStageExclusion($propertyCarbonFootprint, Scope::Scope1, null);

        $this->assertSame([], $propertyCarbonFootprint->recordedEvents());
    }

    /** @param list<CombustibleAnswer|ElectricityAnswer> $answers */
    private function combustiblesSection(array $answers): SectionAnswer
    {
        return new SectionAnswer(Scope::Scope1->sections()[0], $answers);
    }

    private function combustibleAnswer(): CombustibleAnswer
    {
        return new CombustibleAnswer(
            combustibleId: new CombustibleId('combustible-id'),
            quantity: new Quantity(10.0, Unit::Liter),
        );
    }

    private function registeredFootprint(): PropertyCarbonFootprint
    {
        return PropertyCarbonFootprintMother::from('P-1', (string) PropertyCarbonFootprintId::generate());
    }

    private function createSUT(): StageAnswersProcessor
    {
        return new StageAnswersProcessor();
    }
}
