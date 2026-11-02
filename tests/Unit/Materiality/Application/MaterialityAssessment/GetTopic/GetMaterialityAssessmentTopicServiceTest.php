<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialityAssessment\GetTopic;

use App\Materiality\Application\MaterialityAssessment\GetTopic\GetMaterialityAssessmentTopicRequest;
use App\Materiality\Application\MaterialityAssessment\GetTopic\GetMaterialityAssessmentTopicService;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\Materiality\Application\MaterialityAssessment\MaterialityAssessmentContextTrait;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicMother;
use PHPUnit\Framework\TestCase;

final class GetMaterialityAssessmentTopicServiceTest extends TestCase
{
    use MaterialityAssessmentContextTrait;

    protected function setUp(): void
    {
        $this->initMaterialityAssessment();
    }

    public function testReturnsTheIndexTotalAndNeighbours(): void
    {
        $footprint = $this->propertyFootprint();
        $assessment = MaterialityAssessmentMother::draft(
            subjectType: MaterialityAssessmentSubjectType::Property,
            carbonFootprintId: (string) $footprint->id(),
            topics: [MaterialityTopicMother::random(), MaterialityTopicMother::random(), MaterialityTopicMother::random()],
        );
        $this->materialityAssessments->add($assessment);
        [$first, $second, $third] = $assessment->topics();

        $response = $this->sut()(new GetMaterialityAssessmentTopicRequest($assessment->id(), $second->id()), $this->ownerOf());

        $this->assertSame($second, $response->topic);
        $this->assertSame(2, $response->index);
        $this->assertSame(3, $response->total);
        $this->assertEquals($first->id(), $response->previousTopicId);
        $this->assertEquals($third->id(), $response->nextTopicId);
        $this->assertCount(1, $response->answers);
        $this->assertSame($footprint, $response->footprint);
        $this->assertFalse($response->readOnly);
    }

    public function testIsReadOnlyWithACompletedFootprint(): void
    {
        $assessment = $this->assessmentOf($this->propertyFootprint(completed: true));

        $response = $this->sut()(new GetMaterialityAssessmentTopicRequest($assessment->id(), $assessment->topics()[0]->id()), $this->ownerOf());

        $this->assertTrue($response->readOnly);
    }

    public function testIsReadOnlyWithACompletedAssessment(): void
    {
        $assessment = $this->assessmentOf($this->propertyFootprint(), completed: true);

        $response = $this->sut()(new GetMaterialityAssessmentTopicRequest($assessment->id(), $assessment->topics()[0]->id()), $this->ownerOf());

        $this->assertTrue($response->readOnly);
    }

    public function testOwnerOfAnotherAccountIsDenied(): void
    {
        $assessment = $this->assessmentOf($this->propertyFootprint());

        $this->expectException(UnauthorizedActionException::class);

        $this->sut()(new GetMaterialityAssessmentTopicRequest($assessment->id(), $assessment->topics()[0]->id()), $this->ownerOf('other-account'));
    }

    private function sut(): GetMaterialityAssessmentTopicService
    {
        return new GetMaterialityAssessmentTopicService($this->materialityAssessments, $this->footprints(), $this->authorizer());
    }
}
