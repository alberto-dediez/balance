<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Event;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\ClearMaterialityDecisions\ClearMaterialityDecisionsFromCompanyCarbonFootprintService;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\ClearMaterialityDecisions\ClearMaterialityDecisionsFromPropertyCarbonFootprintService;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Materiality\Domain\Event\ClearMaterialityDecisionsOnMaterialityAssessmentRemovedSubscriber;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentId;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentRemoved;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecisionInputMother;
use App\Tests\Unit\Materiality\Application\MaterialityAssessment\MaterialityAssessmentContextTrait;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\TestCase;

final class ClearMaterialityDecisionsOnMaterialityAssessmentRemovedSubscriberTest extends TestCase
{
    use MaterialityAssessmentContextTrait;

    protected function setUp(): void
    {
        $this->initMaterialityAssessment();
    }

    public function testIsSubscribedToMaterialityAssessmentRemoved(): void
    {
        $sut = $this->createSUT();

        $this->assertTrue($sut->isSubscribedTo(new MaterialityAssessmentRemoved(MaterialityAssessmentId::generate())));
        $events = MaterialityAssessmentMother::completed()->recordedEvents();
        $this->assertFalse($sut->isSubscribedTo($events[array_key_last($events)]));
    }

    public function testRemovingTheAssessmentClearsTheDecisionsOfThePropertyFootprint(): void
    {
        $footprint = $this->scope2AnsweredPropertyFootprint();
        $footprint->answerMateriality();
        $footprint->applyMaterialityDecisions([MaterialityDecisionInputMother::omit(5)], []);
        $assessment = $this->assessmentOf($footprint, completed: true);
        $this->createSUT();

        $assessment->remove();

        $this->assertSame([], $footprint->materialityDecisions());
        $this->assertFalse($footprint->isSourceExcluded(Scope::Scope3, Source::OperationsWaste));
    }

    public function testRemovingTheAssessmentClearsTheDecisionsOfTheCompanyFootprint(): void
    {
        $footprint = $this->companyFootprint();
        $footprint->answerMateriality();
        $footprint->applyMaterialityDecisions([MaterialityDecisionInputMother::omit(5)], []);
        $this->createSUT()->handle(new MaterialityAssessmentRemoved(MaterialityAssessmentId::generate(), MaterialityAssessmentSubjectType::Company, (string) $footprint->id()));

        $this->assertSame([], $footprint->materialityDecisions());
    }

    public function testAnEventWithoutFootprintIsIgnored(): void
    {
        $this->createSUT()->handle(new MaterialityAssessmentRemoved(MaterialityAssessmentId::generate()));

        $this->addToAssertionCount(1);
    }

    private function createSUT(): ClearMaterialityDecisionsOnMaterialityAssessmentRemovedSubscriber
    {
        return new ClearMaterialityDecisionsOnMaterialityAssessmentRemovedSubscriber(
            new ClearMaterialityDecisionsFromPropertyCarbonFootprintService($this->propertyCarbonFootprints, new PropertyCarbonFootprintAuthorizer()),
            new ClearMaterialityDecisionsFromCompanyCarbonFootprintService($this->companyCarbonFootprints, new CompanyCarbonFootprintAuthorizer()),
            new FakeLoggedUser($this->ownerOf()),
        );
    }
}
