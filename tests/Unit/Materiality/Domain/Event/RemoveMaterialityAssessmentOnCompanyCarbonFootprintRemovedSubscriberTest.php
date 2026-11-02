<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Event;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRemoved;
use App\Materiality\Application\MaterialityAssessment\RemoveOfCarbonFootprint\RemoveMaterialityAssessmentOfCarbonFootprintService;
use App\Materiality\Domain\Event\RemoveMaterialityAssessmentOnCompanyCarbonFootprintRemovedSubscriber;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Tests\Unit\Materiality\Application\MaterialityAssessment\MaterialityAssessmentContextTrait;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\TestCase;

final class RemoveMaterialityAssessmentOnCompanyCarbonFootprintRemovedSubscriberTest extends TestCase
{
    use MaterialityAssessmentContextTrait;

    protected function setUp(): void
    {
        $this->initMaterialityAssessment();
    }

    public function testIsSubscribedToCompanyCarbonFootprintRemoved(): void
    {
        $this->assertTrue($this->createSUT()->isSubscribedTo(new CompanyCarbonFootprintRemoved(CompanyCarbonFootprintId::generate())));
    }

    public function testRemovesTheAssessmentOfTheRemovedFootprint(): void
    {
        $footprintId = CompanyCarbonFootprintId::generate();
        $assessment = MaterialityAssessmentMother::completed(subjectType: MaterialityAssessmentSubjectType::Company, carbonFootprintId: (string) $footprintId);
        $other = MaterialityAssessmentMother::draft(subjectType: MaterialityAssessmentSubjectType::Company, carbonFootprintId: 'other-footprint');
        $this->materialityAssessments->add($assessment);
        $this->materialityAssessments->add($other);

        $this->createSUT()->handle(new CompanyCarbonFootprintRemoved($footprintId));

        $this->assertTrue($assessment->isRemoved());
        $this->assertFalse($other->isRemoved());
    }

    private function createSUT(): RemoveMaterialityAssessmentOnCompanyCarbonFootprintRemovedSubscriber
    {
        return new RemoveMaterialityAssessmentOnCompanyCarbonFootprintRemovedSubscriber(
            new RemoveMaterialityAssessmentOfCarbonFootprintService($this->materialityAssessments, $this->authorizer()),
            new FakeLoggedUser($this->ownerOf()),
        );
    }
}
