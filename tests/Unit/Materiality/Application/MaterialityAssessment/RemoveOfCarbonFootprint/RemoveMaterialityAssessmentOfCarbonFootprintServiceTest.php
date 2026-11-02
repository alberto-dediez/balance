<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialityAssessment\RemoveOfCarbonFootprint;

use App\Materiality\Application\MaterialityAssessment\RemoveOfCarbonFootprint\RemoveMaterialityAssessmentOfCarbonFootprintRequest;
use App\Materiality\Application\MaterialityAssessment\RemoveOfCarbonFootprint\RemoveMaterialityAssessmentOfCarbonFootprintService;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Tests\Unit\Materiality\Application\MaterialityAssessment\MaterialityAssessmentContextTrait;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentMother;
use PHPUnit\Framework\TestCase;

final class RemoveMaterialityAssessmentOfCarbonFootprintServiceTest extends TestCase
{
    use MaterialityAssessmentContextTrait;

    protected function setUp(): void
    {
        $this->initMaterialityAssessment();
    }

    public function testWithoutAssessmentDoesNothing(): void
    {
        $response = $this->sut()(new RemoveMaterialityAssessmentOfCarbonFootprintRequest(MaterialityAssessmentSubjectType::Property, 'footprint-id'), $this->ownerOf());

        $this->assertNull($response->materialityAssessmentId);
    }

    public function testRemovesTheAssessmentAlthoughTheFootprintNoLongerExists(): void
    {
        $assessment = MaterialityAssessmentMother::completed(carbonFootprintId: 'removed-footprint');
        $this->materialityAssessments->add($assessment);

        $this->sut()(new RemoveMaterialityAssessmentOfCarbonFootprintRequest(MaterialityAssessmentSubjectType::Property, 'removed-footprint'), $this->ownerOf());

        $this->assertTrue($assessment->isRemoved());
    }

    private function sut(): RemoveMaterialityAssessmentOfCarbonFootprintService
    {
        return new RemoveMaterialityAssessmentOfCarbonFootprintService($this->materialityAssessments, $this->authorizer());
    }
}
