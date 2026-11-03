<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Event;

use App\CarbonFootprint\Application\CarbonFootprint\Materiality\MaterialityOmitCommentGenerator;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\ApplyMaterialityDecisions\ApplyMaterialityDecisionsToCompanyCarbonFootprintService;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\ApplyMaterialityDecisions\ApplyMaterialityDecisionsToPropertyCarbonFootprintService;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Materiality\Application\MaterialityAssessment\MaterialityDecisionInputsMapper;
use App\Materiality\Domain\Event\ApplyMaterialityDecisionsOnMaterialityAssessmentCompletedSubscriber;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentId;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentRemoved;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityScope;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Materiality\Domain\Model\MaterialityTopic\StandardScope3Category;
use App\Tests\Unit\Materiality\Application\MaterialityAssessment\MaterialityAssessmentContextTrait;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use PHPUnit\Framework\TestCase;

final class ApplyMaterialityDecisionsOnMaterialityAssessmentCompletedSubscriberTest extends TestCase
{
    use MaterialityAssessmentContextTrait;

    protected function setUp(): void
    {
        $this->initMaterialityAssessment();
    }

    public function testIsSubscribedToMaterialityAssessmentCompleted(): void
    {
        $events = MaterialityAssessmentMother::completed()->recordedEvents();
        $sut = $this->createSUT();

        $this->assertTrue($sut->isSubscribedTo($events[array_key_last($events)]));
        $this->assertFalse($sut->isSubscribedTo(new MaterialityAssessmentRemoved(MaterialityAssessmentId::generate())));
    }

    public function testExcludesTheOmittedSourceOfThePropertyFootprint(): void
    {
        $footprint = $this->scope2AnsweredPropertyFootprint();
        $footprint->answerMateriality();
        $this->createSUT();

        $this->completedAssessmentFor(MaterialityAssessmentSubjectType::Property, (string) $footprint->id(), (string) $footprint->accountId());

        $this->assertSame(ExclusionReason::NotSignificant, $footprint->exclusionFor(Scope::Scope3, Source::OperationsWaste)?->reason());
        $this->assertCount(1, $footprint->materialityDecisions());
    }

    public function testExcludesTheOmittedSourceOfTheCompanyFootprint(): void
    {
        $footprint = $this->companyFootprint();
        $footprint->answerMateriality();
        $this->createSUT();

        $this->completedAssessmentFor(MaterialityAssessmentSubjectType::Company, (string) $footprint->id(), (string) $footprint->accountId());

        $this->assertTrue($footprint->isSourceExcluded(Scope::Scope3, Source::OperationsWaste));
    }

    public function testANumberedCategoryOfAnotherScopeIsNotPropagated(): void
    {
        $footprint = $this->scope2AnsweredPropertyFootprint();
        $footprint->answerMateriality();
        $this->createSUT();

        $this->materialityAssessments->add(MaterialityAssessmentMother::completed(
            carbonFootprintId: (string) $footprint->id(),
            accountId: (string) $footprint->accountId(),
            topics: [MaterialityTopicMother::from(name: 'Combustión propia', id: (string) MaterialityTopicId::generate(), scope: MaterialityScope::Scope1, direction: null, number: 5)],
            value: 1,
        ));

        $this->assertFalse($footprint->isSourceExcluded(Scope::Scope3, Source::OperationsWaste));
        $this->assertCount(1, $footprint->materialityDecisions());
        $this->assertNull($footprint->materialityDecisions()[0]->source());
    }

    private function completedAssessmentFor(MaterialityAssessmentSubjectType $subjectType, string $carbonFootprintId, string $accountId): void
    {
        $assessment = MaterialityAssessmentMother::completed(
            subjectType: $subjectType,
            carbonFootprintId: $carbonFootprintId,
            accountId: $accountId,
            topics: [MaterialityTopicMother::standard(StandardScope3Category::from(5))],
            value: 1,
        );
        $this->materialityAssessments->add($assessment);
    }

    private function createSUT(): ApplyMaterialityDecisionsOnMaterialityAssessmentCompletedSubscriber
    {
        $commentGenerator = new MaterialityOmitCommentGenerator(new FakeTranslator());

        return new ApplyMaterialityDecisionsOnMaterialityAssessmentCompletedSubscriber(
            new ApplyMaterialityDecisionsToPropertyCarbonFootprintService($this->propertyCarbonFootprints, new PropertyCarbonFootprintAuthorizer(), $commentGenerator),
            new ApplyMaterialityDecisionsToCompanyCarbonFootprintService($this->companyCarbonFootprints, new CompanyCarbonFootprintAuthorizer(), $commentGenerator),
            new MaterialityDecisionInputsMapper(),
            new FakeLoggedUser($this->ownerOf()),
        );
    }
}
