<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialityAssessment\Start;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Materiality\Application\MaterialityAssessment\MaterialityAssessmentNotEditableException;
use App\Materiality\Application\MaterialityAssessment\MaterialityAssessmentScope2NotAnsweredException;
use App\Materiality\Application\MaterialityAssessment\Start\StartMaterialityAssessmentRequest;
use App\Materiality\Application\MaterialityAssessment\Start\StartMaterialityAssessmentService;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentAlreadyExistsException;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorNotFoundException;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\Materiality\Application\MaterialityAssessment\MaterialityAssessmentContextTrait;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialitySector\MaterialitySectorMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicMother;
use PHPUnit\Framework\TestCase;

final class StartMaterialityAssessmentServiceTest extends TestCase
{
    use MaterialityAssessmentContextTrait;

    private MaterialityTopic $topic;
    private MaterialityQuestion $question;

    protected function setUp(): void
    {
        $this->initMaterialityAssessment();

        $this->topic = MaterialityTopicMother::random();
        $this->question = MaterialityQuestionMother::random();
        $this->materialityTopics->add($this->topic);
        $this->materialityQuestions->add($this->question);
    }

    public function testStartsTheAssessmentWithTheAccountAndYearOfTheFootprint(): void
    {
        $footprint = $this->propertyFootprint(year: 2024, accountId: 'account-id');

        $response = $this->sut()(new StartMaterialityAssessmentRequest(MaterialityAssessmentSubjectType::Property, (string) $footprint->id()), $this->ownerOf());

        $assessment = $this->materialityAssessments->find($response->materialityAssessmentId);
        $this->assertNotNull($assessment);
        $this->assertSame('account-id', (string) $assessment->accountId());
        $this->assertSame(2024, $assessment->year());
        $this->assertSame((string) $footprint->id(), $assessment->carbonFootprintId());
        $this->assertNull($assessment->sectorId());
        $this->assertCount(1, $assessment->answers());
        $this->assertEquals($assessment->topics()[0]->id(), $response->firstTopicId);
    }

    public function testFootprintOfAnotherAccountIsDenied(): void
    {
        $footprint = $this->propertyFootprint(accountId: 'account-id');

        $this->expectException(UnauthorizedActionException::class);

        $this->sut()(new StartMaterialityAssessmentRequest(MaterialityAssessmentSubjectType::Property, (string) $footprint->id()), $this->ownerOf('other-account'));
    }

    public function testCompletedFootprintIsNotEditable(): void
    {
        $footprint = $this->propertyFootprint(completed: true);

        $this->expectException(MaterialityAssessmentNotEditableException::class);

        $this->sut()(new StartMaterialityAssessmentRequest(MaterialityAssessmentSubjectType::Property, (string) $footprint->id()), $this->ownerOf());
    }

    public function testFootprintWithoutScope2AnsweredIsRejected(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope1Answered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate());
        $this->propertyCarbonFootprints->add($footprint);

        $this->expectException(MaterialityAssessmentScope2NotAnsweredException::class);

        $this->sut()(new StartMaterialityAssessmentRequest(MaterialityAssessmentSubjectType::Property, (string) $footprint->id()), $this->ownerOf());
    }

    public function testAnAssessmentAlreadyExists(): void
    {
        $footprint = $this->propertyFootprint();
        $this->assessmentOf($footprint);

        $this->expectException(MaterialityAssessmentAlreadyExistsException::class);

        $this->sut()(new StartMaterialityAssessmentRequest(MaterialityAssessmentSubjectType::Property, (string) $footprint->id()), $this->ownerOf());
    }

    public function testUnknownSectorThrows(): void
    {
        $footprint = $this->propertyFootprint();

        $this->expectException(MaterialitySectorNotFoundException::class);

        $this->sut()(new StartMaterialityAssessmentRequest(MaterialityAssessmentSubjectType::Property, (string) $footprint->id(), MaterialitySectorId::generate()), $this->ownerOf());
    }

    public function testTheSectorNameIsCopied(): void
    {
        $footprint = $this->propertyFootprint();
        $sector = MaterialitySectorMother::fromNameAndId('Logística', (string) MaterialitySectorId::generate());
        $this->materialitySectors->add($sector);

        $response = $this->sut()(new StartMaterialityAssessmentRequest(MaterialityAssessmentSubjectType::Property, (string) $footprint->id(), $sector->id()), $this->ownerOf());

        $assessment = $this->materialityAssessments->find($response->materialityAssessmentId);
        $this->assertNotNull($assessment);
        $this->assertEquals($sector->id(), $assessment->sectorId());
        $this->assertSame('Logística', $assessment->sectorName());
    }

    public function testCopiesTheMatchingAnswersOfThePreviousCompletedAssessment(): void
    {
        $propertyId = PropertyId::generate();
        $previousFootprint = $this->propertyFootprint(year: 2024, propertyId: $propertyId);
        $footprint = $this->propertyFootprint(year: 2025, propertyId: $propertyId);
        $this->materialityAssessments->add(MaterialityAssessmentMother::completed(
            carbonFootprintId: (string) $previousFootprint->id(),
            year: 2024,
            topics: [$this->topic],
            questions: [$this->question],
            value: 2,
        ));
        $newQuestion = MaterialityQuestionMother::from(text: 'New', id: (string) MaterialityQuestionId::generate(), position: 5);
        $this->materialityQuestions->add($newQuestion);

        $response = $this->sut()(new StartMaterialityAssessmentRequest(MaterialityAssessmentSubjectType::Property, (string) $footprint->id(), copyPreviousAnswers: true), $this->ownerOf());

        $assessment = $this->materialityAssessments->find($response->materialityAssessmentId);
        $this->assertNotNull($assessment);
        $answers = $assessment->answersOf($this->topic->id());
        $this->assertSame(2, $answers[0]->value());
        $this->assertNull($answers[1]->value());
        $this->assertFalse($assessment->isCompleted());
    }

    public function testDoesNotCopyAnythingWithoutPreviousAssessment(): void
    {
        $propertyId = PropertyId::generate();
        $this->propertyFootprint(year: 2024, propertyId: $propertyId);
        $footprint = $this->propertyFootprint(year: 2025, propertyId: $propertyId);

        $response = $this->sut()(new StartMaterialityAssessmentRequest(MaterialityAssessmentSubjectType::Property, (string) $footprint->id(), copyPreviousAnswers: true), $this->ownerOf());

        $assessment = $this->materialityAssessments->find($response->materialityAssessmentId);
        $this->assertNotNull($assessment);
        $this->assertSame(0, $assessment->answeredCount());
    }

    public function testDoesNotUseAPreviousDraftAssessment(): void
    {
        $propertyId = PropertyId::generate();
        $previousFootprint = $this->propertyFootprint(year: 2024, propertyId: $propertyId);
        $footprint = $this->propertyFootprint(year: 2025, propertyId: $propertyId);
        $this->materialityAssessments->add(MaterialityAssessmentMother::answered(
            carbonFootprintId: (string) $previousFootprint->id(),
            year: 2024,
            topics: [$this->topic],
            questions: [$this->question],
        ));

        $response = $this->sut()(new StartMaterialityAssessmentRequest(MaterialityAssessmentSubjectType::Property, (string) $footprint->id(), copyPreviousAnswers: true), $this->ownerOf());

        $assessment = $this->materialityAssessments->find($response->materialityAssessmentId);
        $this->assertNotNull($assessment);
        $this->assertSame(0, $assessment->answeredCount());
    }

    private function sut(): StartMaterialityAssessmentService
    {
        return new StartMaterialityAssessmentService(
            $this->materialityAssessments,
            $this->materialitySectors,
            $this->footprints(),
            $this->questionnaireBuilder(),
            $this->authorizer(),
        );
    }
}
