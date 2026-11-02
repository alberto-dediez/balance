<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialityAssessment\Reopen;

use App\Materiality\Application\MaterialityAssessment\MaterialityAssessmentNotEditableException;
use App\Materiality\Application\MaterialityAssessment\Reopen\ReopenMaterialityAssessmentRequest;
use App\Materiality\Application\MaterialityAssessment\Reopen\ReopenMaterialityAssessmentService;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentNotCompletedException;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentStatus;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\Materiality\Application\MaterialityAssessment\MaterialityAssessmentContextTrait;
use PHPUnit\Framework\TestCase;

final class ReopenMaterialityAssessmentServiceTest extends TestCase
{
    use MaterialityAssessmentContextTrait;

    protected function setUp(): void
    {
        $this->initMaterialityAssessment();
    }

    public function testReopensACompletedAssessment(): void
    {
        $assessment = $this->assessmentOf($this->propertyFootprint(), completed: true);

        $this->sut()(new ReopenMaterialityAssessmentRequest($assessment->id()), $this->ownerOf());

        $this->assertSame(MaterialityAssessmentStatus::Draft, $assessment->status());
        $this->assertNull($assessment->topics()[0]->scorePercent());
    }

    public function testADraftCannotBeReopened(): void
    {
        $assessment = $this->assessmentOf($this->propertyFootprint());

        $this->expectException(MaterialityAssessmentNotCompletedException::class);

        $this->sut()(new ReopenMaterialityAssessmentRequest($assessment->id()), $this->ownerOf());
    }

    public function testOwnerOfAnotherAccountIsDenied(): void
    {
        $assessment = $this->assessmentOf($this->propertyFootprint(), completed: true);

        $this->expectException(UnauthorizedActionException::class);

        $this->sut()(new ReopenMaterialityAssessmentRequest($assessment->id()), $this->ownerOf('other-account'));
    }

    public function testCompletedFootprintIsNotEditable(): void
    {
        $assessment = $this->assessmentOf($this->propertyFootprint(completed: true), completed: true);

        $this->expectException(MaterialityAssessmentNotEditableException::class);

        $this->sut()(new ReopenMaterialityAssessmentRequest($assessment->id()), $this->ownerOf());
    }

    private function sut(): ReopenMaterialityAssessmentService
    {
        return new ReopenMaterialityAssessmentService($this->materialityAssessments, $this->footprints(), $this->authorizer());
    }
}
