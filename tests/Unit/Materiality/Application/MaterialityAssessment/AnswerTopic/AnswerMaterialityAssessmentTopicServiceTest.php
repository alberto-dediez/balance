<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialityAssessment\AnswerTopic;

use App\Materiality\Application\MaterialityAssessment\AnswerTopic\AnswerMaterialityAssessmentTopicRequest;
use App\Materiality\Application\MaterialityAssessment\AnswerTopic\AnswerMaterialityAssessmentTopicService;
use App\Materiality\Application\MaterialityAssessment\MaterialityAssessmentNotEditableException;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessment;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentAlreadyCompletedException;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\Materiality\Application\MaterialityAssessment\MaterialityAssessmentContextTrait;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicMother;
use PHPUnit\Framework\TestCase;

final class AnswerMaterialityAssessmentTopicServiceTest extends TestCase
{
    use MaterialityAssessmentContextTrait;

    protected function setUp(): void
    {
        $this->initMaterialityAssessment();
    }

    public function testSavesTheAnswersAndReturnsTheNextTopic(): void
    {
        $assessment = $this->twoTopicsAssessment();
        [$first, $second] = $assessment->topics();

        $response = $this->sut()($this->request($assessment, 0, 3), $this->ownerOf());

        $this->assertSame(3, $assessment->answersOf($first->topicId())[0]->value());
        $this->assertEquals($second->id(), $response->nextTopicId);
    }

    public function testTheLastTopicHasNoNextTopic(): void
    {
        $assessment = $this->twoTopicsAssessment();

        $response = $this->sut()($this->request($assessment, 1, 1), $this->ownerOf());

        $this->assertNull($response->nextTopicId);
    }

    public function testOwnerOfAnotherAccountIsDenied(): void
    {
        $assessment = $this->twoTopicsAssessment();

        $this->expectException(UnauthorizedActionException::class);

        $this->sut()($this->request($assessment, 0, 3), $this->ownerOf('other-account'));
    }

    public function testCompletedFootprintIsNotEditable(): void
    {
        $footprint = $this->propertyFootprint(completed: true);
        $assessment = $this->assessmentOf($footprint);

        $this->expectException(MaterialityAssessmentNotEditableException::class);

        $this->sut()($this->request($assessment, 0, 3), $this->ownerOf());
    }

    public function testCompletedAssessmentCannotBeAnswered(): void
    {
        $assessment = $this->assessmentOf($this->propertyFootprint(), completed: true);

        $this->expectException(MaterialityAssessmentAlreadyCompletedException::class);

        $this->sut()($this->request($assessment, 0, 3), $this->ownerOf());
    }

    private function twoTopicsAssessment(): MaterialityAssessment
    {
        $footprint = $this->propertyFootprint();
        $assessment = MaterialityAssessmentMother::draft(
            subjectType: MaterialityAssessmentSubjectType::Property,
            carbonFootprintId: (string) $footprint->id(),
            topics: [MaterialityTopicMother::random(), MaterialityTopicMother::random()],
        );
        $this->materialityAssessments->add($assessment);

        return $assessment;
    }

    private function request(MaterialityAssessment $assessment, int $topicIndex, int $value): AnswerMaterialityAssessmentTopicRequest
    {
        $topic = $assessment->topics()[$topicIndex];
        $values = [];

        foreach ($assessment->answersOf($topic->topicId()) as $answer) {
            $values[(string) $answer->id()] = $value;
        }

        return new AnswerMaterialityAssessmentTopicRequest($assessment->id(), $topic->id(), $values);
    }

    private function sut(): AnswerMaterialityAssessmentTopicService
    {
        return new AnswerMaterialityAssessmentTopicService($this->materialityAssessments, $this->footprints(), $this->authorizer());
    }
}
