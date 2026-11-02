<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialityAssessment\Complete;

use App\Materiality\Application\MaterialityAssessment\Complete\CompleteMaterialityAssessmentRequest;
use App\Materiality\Application\MaterialityAssessment\Complete\CompleteMaterialityAssessmentService;
use App\Materiality\Application\MaterialityAssessment\MaterialityAssessmentNotEditableException;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentCompleted;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentIncompleteException;
use App\Tests\Unit\Materiality\Application\MaterialityAssessment\MaterialityAssessmentContextTrait;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

final class CompleteMaterialityAssessmentServiceTest extends TestCase
{
    use MaterialityAssessmentContextTrait;

    protected function setUp(): void
    {
        $this->initMaterialityAssessment();
    }

    public function testCompletesTheAssessmentAndPublishesTheEvent(): void
    {
        $spy = new SpyDomainEventSubscriber();
        $assessment = $this->assessmentOf($this->propertyFootprint());
        MaterialityAssessmentMother::answerAll($assessment, 3);
        $owner = $this->ownerOf();

        $response = $this->sut()(new CompleteMaterialityAssessmentRequest($assessment->id()), $owner);

        $this->assertEquals($assessment->id(), $response->materialityAssessmentId);
        $this->assertTrue($assessment->isCompleted());
        $this->assertEquals($owner->id(), $assessment->completedBy());
        $this->assertInstanceOf(MaterialityAssessmentCompleted::class, $spy->lastEvent());
    }

    public function testIncompleteAssessmentThrows(): void
    {
        $assessment = $this->assessmentOf($this->propertyFootprint());

        $this->expectException(MaterialityAssessmentIncompleteException::class);

        $this->sut()(new CompleteMaterialityAssessmentRequest($assessment->id()), $this->ownerOf());
    }

    public function testCompletedFootprintIsNotEditable(): void
    {
        $assessment = $this->assessmentOf($this->propertyFootprint(completed: true));
        MaterialityAssessmentMother::answerAll($assessment, 3);

        $this->expectException(MaterialityAssessmentNotEditableException::class);

        $this->sut()(new CompleteMaterialityAssessmentRequest($assessment->id()), $this->ownerOf());
    }

    private function sut(): CompleteMaterialityAssessmentService
    {
        return new CompleteMaterialityAssessmentService($this->materialityAssessments, $this->footprints(), $this->authorizer());
    }
}
