<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialityAssessment;

use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityQuestionSnapshot;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityAnswerScale;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityCriterion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityScope;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Materiality\Domain\Model\MaterialityTopic\StandardScope3Category;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicMother;
use PHPUnit\Framework\TestCase;

final class MaterialityQuestionnaireBuilderTest extends TestCase
{
    use MaterialityAssessmentContextTrait;

    protected function setUp(): void
    {
        $this->initMaterialityAssessment();
    }

    public function testInactiveTopicsAreLeftOut(): void
    {
        $active = MaterialityTopicMother::random();
        $inactive = new MaterialityTopic(id: MaterialityTopicId::generate(), name: 'Inactive', active: false);
        $this->materialityTopics->add($active);
        $this->materialityTopics->add($inactive);

        $questionnaire = $this->questionnaireBuilder()->build(null);

        $this->assertCount(1, $questionnaire->topics);
        $this->assertEquals($active->id(), $questionnaire->topics[0]->topicId);
    }

    public function testTopicsAreOrderedByScopeAndPosition(): void
    {
        $this->materialityTopics->add(MaterialityTopicMother::from(name: 'Scope 3 first', id: (string) MaterialityTopicId::generate(), position: 1));
        $this->materialityTopics->add(MaterialityTopicMother::from(name: 'Scope 1', id: (string) MaterialityTopicId::generate(), scope: MaterialityScope::Scope1, direction: null, position: 5));
        $this->materialityTopics->add(MaterialityTopicMother::from(name: 'Scope 3 second', id: (string) MaterialityTopicId::generate(), position: 2));

        $questionnaire = $this->questionnaireBuilder()->build(null);

        $this->assertSame(['Scope 1', 'Scope 3 first', 'Scope 3 second'], array_map(static fn($topic) => $topic->name, $questionnaire->topics));
    }

    public function testGenericQuestionsGoToEveryTopicAndSpecificOnlyToTheirs(): void
    {
        $topicA = MaterialityTopicMother::from(name: 'A', id: (string) MaterialityTopicId::generate(), position: 1);
        $topicB = MaterialityTopicMother::from(name: 'B', id: (string) MaterialityTopicId::generate(), position: 2);
        $this->materialityTopics->add($topicA);
        $this->materialityTopics->add($topicB);
        $this->materialityQuestions->add(MaterialityQuestionMother::from(text: 'Generic 2', id: (string) MaterialityQuestionId::generate(), position: 2));
        $this->materialityQuestions->add(MaterialityQuestionMother::from(text: 'Generic 1', id: (string) MaterialityQuestionId::generate(), position: 1));
        $this->materialityQuestions->add(MaterialityQuestionMother::from(text: 'Specific B', id: (string) MaterialityQuestionId::generate(), topicId: $topicB->id(), position: 1));

        $questionnaire = $this->questionnaireBuilder()->build(null);

        $this->assertSame(['Generic 1', 'Generic 2'], $this->textsOf($questionnaire->questions, $topicA->id()));
        $this->assertSame(['Generic 1', 'Specific B', 'Generic 2'], $this->textsOf($questionnaire->questions, $topicB->id()));
    }

    public function testQuestionsAreFilteredBySector(): void
    {
        $transport = MaterialityTopicMother::standard(StandardScope3Category::UpstreamTransportationAndDistribution);
        $travel = MaterialityTopicMother::standard(StandardScope3Category::BusinessTravel);
        $this->materialityTopics->add($transport);
        $this->materialityTopics->add($travel);
        $logistics = MaterialitySectorId::generate();
        $hotel = MaterialitySectorId::generate();

        foreach (['Magnitude', 'Influence', 'Risk', 'Stakeholders', 'Guidance'] as $index => $text) {
            $this->materialityQuestions->add(MaterialityQuestionMother::from(text: $text, id: (string) MaterialityQuestionId::generate(), position: $index + 1));
        }
        $this->materialityQuestions->add(MaterialityQuestionMother::from(
            text: '¿Subcontratas la logística?',
            id: (string) MaterialityQuestionId::generate(),
            criterion: MaterialityCriterion::Outsourcing,
            topicId: $transport->id(),
            answerScale: MaterialityAnswerScale::YesNo,
            position: 10,
            sectorIds: [$logistics],
        ));

        $withoutSector = $this->questionnaireBuilder()->build(null);
        $withLogistics = $this->questionnaireBuilder()->build($logistics);
        $withHotel = $this->questionnaireBuilder()->build($hotel);

        $this->assertCount(5, $this->textsOf($withoutSector->questions, $transport->id()));
        $this->assertCount(5, $this->textsOf($withoutSector->questions, $travel->id()));
        $this->assertCount(6, $this->textsOf($withLogistics->questions, $transport->id()));
        $this->assertCount(5, $this->textsOf($withLogistics->questions, $travel->id()));
        $this->assertCount(5, $this->textsOf($withHotel->questions, $transport->id()));
        $this->assertCount(5, $this->textsOf($withHotel->questions, $travel->id()));
    }

    public function testQuestionUpdatedAtFallsBackToRegisteredAt(): void
    {
        $topic = MaterialityTopicMother::random();
        $this->materialityTopics->add($topic);
        $registered = new MaterialityQuestion(id: MaterialityQuestionId::generate(), text: 'Registered', position: 1, registeredAt: new \DateTimeImmutable('2026-01-01'));
        $updated = new MaterialityQuestion(id: MaterialityQuestionId::generate(), text: 'Updated', position: 2, registeredAt: new \DateTimeImmutable('2026-01-01'), updatedAt: new \DateTimeImmutable('2026-02-01'));
        $this->materialityQuestions->add($registered);
        $this->materialityQuestions->add($updated);

        $questions = $this->questionnaireBuilder()->build(null)->questions;

        $this->assertEquals(new \DateTimeImmutable('2026-01-01'), $questions[0]->questionUpdatedAt);
        $this->assertEquals(new \DateTimeImmutable('2026-02-01'), $questions[1]->questionUpdatedAt);
    }

    /**
     * @param list<MaterialityQuestionSnapshot> $questions
     *
     * @return list<string>
     */
    private function textsOf(array $questions, MaterialityTopicId $topicId): array
    {
        return array_values(array_map(
            static fn(MaterialityQuestionSnapshot $question) => $question->text,
            array_filter($questions, static fn(MaterialityQuestionSnapshot $question) => $question->topicId->equals($topicId)),
        ));
    }
}
