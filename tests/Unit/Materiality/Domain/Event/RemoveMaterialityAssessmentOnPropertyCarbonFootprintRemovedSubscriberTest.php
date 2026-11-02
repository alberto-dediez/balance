<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Event;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRemoved;
use App\Materiality\Application\MaterialityAssessment\RemoveOfCarbonFootprint\RemoveMaterialityAssessmentOfCarbonFootprintService;
use App\Materiality\Domain\Event\RemoveMaterialityAssessmentOnPropertyCarbonFootprintRemovedSubscriber;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Tests\Unit\Materiality\Application\MaterialityAssessment\MaterialityAssessmentContextTrait;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\TestCase;

final class RemoveMaterialityAssessmentOnPropertyCarbonFootprintRemovedSubscriberTest extends TestCase
{
    use MaterialityAssessmentContextTrait;

    protected function setUp(): void
    {
        $this->initMaterialityAssessment();
    }

    public function testIsSubscribedToPropertyCarbonFootprintRemoved(): void
    {
        $this->assertTrue($this->createSUT()->isSubscribedTo(new PropertyCarbonFootprintRemoved(PropertyCarbonFootprintId::generate())));
    }

    public function testRemovesTheAssessmentOfTheRemovedFootprint(): void
    {
        $footprintId = PropertyCarbonFootprintId::generate();
        $assessment = MaterialityAssessmentMother::completed(subjectType: MaterialityAssessmentSubjectType::Property, carbonFootprintId: (string) $footprintId);
        $other = MaterialityAssessmentMother::draft(subjectType: MaterialityAssessmentSubjectType::Property, carbonFootprintId: 'other-footprint');
        $this->materialityAssessments->add($assessment);
        $this->materialityAssessments->add($other);

        $this->createSUT()->handle(new PropertyCarbonFootprintRemoved($footprintId));

        $this->assertTrue($assessment->isRemoved());
        $this->assertFalse($other->isRemoved());
    }

    private function createSUT(): RemoveMaterialityAssessmentOnPropertyCarbonFootprintRemovedSubscriber
    {
        return new RemoveMaterialityAssessmentOnPropertyCarbonFootprintRemovedSubscriber(
            new RemoveMaterialityAssessmentOfCarbonFootprintService($this->materialityAssessments, $this->authorizer()),
            new FakeLoggedUser($this->ownerOf()),
        );
    }
}
