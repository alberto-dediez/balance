<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialityAssessment\Remove;

use App\Materiality\Application\MaterialityAssessment\MaterialityAssessmentNotEditableException;
use App\Materiality\Application\MaterialityAssessment\Remove\RemoveMaterialityAssessmentRequest;
use App\Materiality\Application\MaterialityAssessment\Remove\RemoveMaterialityAssessmentService;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentNotFoundException;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\Materiality\Application\MaterialityAssessment\MaterialityAssessmentContextTrait;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

final class RemoveMaterialityAssessmentServiceTest extends TestCase
{
    use MaterialityAssessmentContextTrait;

    protected function setUp(): void
    {
        $this->initMaterialityAssessment();
    }

    public function testRemovesTheAssessmentEvenIfCompleted(): void
    {
        $assessment = $this->assessmentOf($this->propertyFootprint(), completed: true);

        $this->sut()(new RemoveMaterialityAssessmentRequest($assessment->id()), $this->ownerOf());

        $this->assertTrue($assessment->isRemoved());
        $this->assertNull($this->materialityAssessments->find($assessment->id()));
    }

    public function testRemovedAssessmentIsNotFound(): void
    {
        $assessment = $this->assessmentOf($this->propertyFootprint());
        $this->sut()(new RemoveMaterialityAssessmentRequest($assessment->id()), $this->ownerOf());

        $this->expectException(MaterialityAssessmentNotFoundException::class);

        $this->sut()(new RemoveMaterialityAssessmentRequest($assessment->id()), $this->ownerOf());
    }

    public function testOwnerOfAnotherAccountIsDenied(): void
    {
        $assessment = $this->assessmentOf($this->propertyFootprint());

        $this->expectException(UnauthorizedActionException::class);

        $this->sut()(new RemoveMaterialityAssessmentRequest($assessment->id()), $this->ownerOf('other-account'));
    }

    public function testAdminWithoutImpersonationIsDenied(): void
    {
        $assessment = $this->assessmentOf($this->propertyFootprint());

        $this->expectException(UnauthorizedActionException::class);

        $this->sut()(new RemoveMaterialityAssessmentRequest($assessment->id()), UserMother::adminUser());
    }

    public function testCompletedFootprintIsNotEditable(): void
    {
        $assessment = $this->assessmentOf($this->propertyFootprint(completed: true));

        $this->expectException(MaterialityAssessmentNotEditableException::class);

        $this->sut()(new RemoveMaterialityAssessmentRequest($assessment->id()), $this->ownerOf());
    }

    private function sut(): RemoveMaterialityAssessmentService
    {
        return new RemoveMaterialityAssessmentService($this->materialityAssessments, $this->footprints(), $this->authorizer());
    }
}
