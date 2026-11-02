<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialityAssessment\GetStep;

use App\Materiality\Application\MaterialityAssessment\GetStep\GetMaterialityStepRequest;
use App\Materiality\Application\MaterialityAssessment\GetStep\GetMaterialityStepService;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\Materiality\Application\MaterialityAssessment\MaterialityAssessmentContextTrait;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialitySector\MaterialitySectorMother;
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
    }

    public function testOwnerOfAnotherAccountIsDenied(): void
    {
        $footprint = $this->propertyFootprint();

        $this->expectException(UnauthorizedActionException::class);

        $this->sut()(new GetMaterialityStepRequest(MaterialityAssessmentSubjectType::Property, (string) $footprint->id()), $this->ownerOf('other-account'));
    }

    private function sut(): GetMaterialityStepService
    {
        return new GetMaterialityStepService($this->materialityAssessments, $this->materialitySectors, $this->footprints());
    }
}
