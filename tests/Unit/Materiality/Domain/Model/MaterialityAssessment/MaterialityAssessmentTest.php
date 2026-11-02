<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Model\MaterialityAssessment;

use App\Materiality\Domain\Model\MaterialityAssessment\InvalidMaterialityAnswerValueException;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessment;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentAlreadyCompletedException;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentCompleted;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentIncompleteException;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentNotCompletedException;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentRemoved;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentReopened;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentStarted;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentStatus;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentTopicAnswered;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityAnswerScale;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityCriterion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityScope;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

final class MaterialityAssessmentTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testStartCreatesATopicPerCategoryAndAnAnswerPerCategoryAndQuestion(): void
    {
        $topicA = MaterialityTopicMother::from(name: 'Topic A', id: (string) MaterialityTopicId::generate(), number: 1, position: 1);
        $topicB = MaterialityTopicMother::from(name: 'Topic B', id: (string) MaterialityTopicId::generate(), number: 2, position: 2);
        $generic = MaterialityQuestionMother::from(text: 'Generic', id: (string) MaterialityQuestionId::generate());
        $specific = MaterialityQuestionMother::from(text: 'Specific', id: (string) MaterialityQuestionId::generate(), topicId: $topicB->id(), position: 1);

        $assessment = MaterialityAssessmentMother::draft(topics: [$topicA, $topicB], questions: [$generic, $specific]);

        $this->assertCount(2, $assessment->topics());
        $this->assertSame('Topic A', $assessment->topics()[0]->name());
        $this->assertSame('Topic A description', $assessment->topics()[0]->description());
        $this->assertSame(1, $assessment->topics()[0]->topicNumber());
        $this->assertCount(3, $assessment->answers());
        $this->assertCount(1, $assessment->answersOf($topicA->id()));
        $this->assertSame(['Generic', 'Specific'], array_map(static fn($answer) => $answer->text(), $assessment->answersOf($topicB->id())));
        $this->assertSame('Generic help', $assessment->answersOf($topicA->id())[0]->help());
        $this->assertSame(MaterialityAssessmentStatus::Draft, $assessment->status());
        $this->assertSame(0, $assessment->answeredCount());
        $this->assertSame(3, $assessment->totalAnswers());
        $this->assertFalse($assessment->isFullyAnswered());
        $this->assertInstanceOf(MaterialityAssessmentStarted::class, $this->spySubscriber->lastEvent());
    }

    public function testStartAppliesOnlyTheInitialValuesValidForTheScale(): void
    {
        $topic = MaterialityTopicMother::random();
        $level = MaterialityQuestionMother::from(text: 'Level', id: (string) MaterialityQuestionId::generate(), position: 1);
        $yesNo = MaterialityQuestionMother::from(text: 'YesNo', id: (string) MaterialityQuestionId::generate(), answerScale: MaterialityAnswerScale::YesNo, position: 2);

        $assessment = MaterialityAssessmentMother::draft(
            topics: [$topic],
            questions: [$level, $yesNo],
            initialValues: [
                $topic->id() . '|' . $level->id() => 2,
                $topic->id() . '|' . $yesNo->id() => 2,
            ],
        );

        $answers = $assessment->answersOf($topic->id());

        $this->assertSame(2, $answers[0]->value());
        $this->assertNull($answers[1]->value());
    }

    public function testAnswerTopicStoresValuesAndAcceptsNull(): void
    {
        $assessment = MaterialityAssessmentMother::draft(questions: $this->fiveQuestions());
        $topic = $assessment->topics()[0];
        $answers = $assessment->answersOf($topic->topicId());

        $assessment->answerTopic($topic->id(), [
            (string) $answers[0]->id() => 3,
            (string) $answers[1]->id() => null,
        ]);

        $this->assertSame(3, $answers[0]->value());
        $this->assertNotNull($answers[0]->answeredAt());
        $this->assertNull($answers[1]->value());
        $this->assertSame(1, $assessment->answeredCount());
        $this->assertInstanceOf(MaterialityAssessmentTopicAnswered::class, $this->spySubscriber->lastEvent());
    }

    public function testAnswerTopicWithAValueOutsideTheScaleThrows(): void
    {
        $assessment = MaterialityAssessmentMother::draft(questions: [
            MaterialityQuestionMother::from(text: 'YesNo', id: (string) MaterialityQuestionId::generate(), answerScale: MaterialityAnswerScale::YesNo),
        ]);
        $topic = $assessment->topics()[0];
        $answer = $assessment->answersOf($topic->topicId())[0];

        $this->expectException(InvalidMaterialityAnswerValueException::class);

        $assessment->answerTopic($topic->id(), [(string) $answer->id() => 2]);
    }

    public function testAnswerTopicWithAnAnswerOfAnotherTopicThrows(): void
    {
        $assessment = MaterialityAssessmentMother::draft(topics: [MaterialityTopicMother::random(), MaterialityTopicMother::random()]);
        [$first, $second] = $assessment->topics();
        $foreign = $assessment->answersOf($second->topicId())[0];

        $this->expectException(InvalidMaterialityAnswerValueException::class);

        $assessment->answerTopic($first->id(), [(string) $foreign->id() => 3]);
    }

    public function testAnswerTopicOnACompletedAssessmentThrows(): void
    {
        $assessment = MaterialityAssessmentMother::completed();
        $topic = $assessment->topics()[0];

        $this->expectException(MaterialityAssessmentAlreadyCompletedException::class);

        $assessment->answerTopic($topic->id(), []);
    }

    public function testCompleteWithUnansweredQuestionsThrows(): void
    {
        $assessment = MaterialityAssessmentMother::draft();

        $this->expectException(MaterialityAssessmentIncompleteException::class);

        $assessment->complete(UserId::generate());
    }

    public function testCompleteScoresEachTopic(): void
    {
        $assessment = MaterialityAssessmentMother::draft(questions: $this->fiveQuestions());
        $this->answerFirstTopic($assessment, [3, 2, 1, 3, 3]);
        $userId = UserId::generate();

        $assessment->complete($userId);

        $topic = $assessment->topics()[0];
        $this->assertTrue($assessment->isCompleted());
        $this->assertEquals($userId, $assessment->completedBy());
        $this->assertNotNull($assessment->completedAt());
        $this->assertSame(15, $topic->points());
        $this->assertSame(6, $topic->minPoints());
        $this->assertSame(18, $topic->maxPoints());
        $this->assertSame(75.0, $topic->scorePercent());
        $this->assertSame(3, $topic->highestMagnitudeLevel());
        $this->assertInstanceOf(MaterialityAssessmentCompleted::class, $this->spySubscriber->lastEvent());
    }

    public function testCompleteWithEverythingAtTheMinimumScoresZero(): void
    {
        $assessment = MaterialityAssessmentMother::draft(questions: $this->fiveQuestions());
        $this->answerFirstTopic($assessment, [1, 1, 1, 1, 1]);

        $assessment->complete(UserId::generate());

        $this->assertSame(0.0, $assessment->topics()[0]->scorePercent());
    }

    public function testCompleteWithEverythingAtTheMaximumScoresOneHundred(): void
    {
        $assessment = MaterialityAssessmentMother::draft(questions: $this->fiveQuestions());
        $this->answerFirstTopic($assessment, [3, 3, 3, 3, 3]);

        $assessment->complete(UserId::generate());

        $this->assertSame(100.0, $assessment->topics()[0]->scorePercent());
    }

    public function testATopicWithoutQuestionsHasNoScore(): void
    {
        $topic = MaterialityTopicMother::random();
        $assessment = MaterialityAssessmentMother::draft(topics: [$topic], questions: [
            MaterialityQuestionMother::forTopic(MaterialityTopicId::generate()),
        ]);

        $assessment->complete(UserId::generate());

        $this->assertSame(0, $assessment->topics()[0]->points());
        $this->assertNull($assessment->topics()[0]->scorePercent());
        $this->assertNull($assessment->topics()[0]->highestMagnitudeLevel());
    }

    public function testHighestMagnitudeLevelIsNullWithoutMagnitudeQuestions(): void
    {
        $assessment = MaterialityAssessmentMother::answered(questions: [
            MaterialityQuestionMother::from(text: 'Risk', id: (string) MaterialityQuestionId::generate(), criterion: MaterialityCriterion::Risk),
        ]);

        $assessment->complete(UserId::generate());

        $this->assertNull($assessment->topics()[0]->highestMagnitudeLevel());
        $this->assertSame(100.0, $assessment->topics()[0]->scorePercent());
    }

    public function testReopenClearsTheScoresAndReturnsToDraft(): void
    {
        $assessment = MaterialityAssessmentMother::completed();

        $assessment->reopen();

        $topic = $assessment->topics()[0];
        $this->assertSame(MaterialityAssessmentStatus::Draft, $assessment->status());
        $this->assertNull($assessment->completedBy());
        $this->assertNull($assessment->completedAt());
        $this->assertNull($topic->points());
        $this->assertNull($topic->minPoints());
        $this->assertNull($topic->maxPoints());
        $this->assertNull($topic->highestMagnitudeLevel());
        $this->assertNull($topic->scorePercent());
        $this->assertTrue($assessment->isFullyAnswered());
        $this->assertInstanceOf(MaterialityAssessmentReopened::class, $this->spySubscriber->lastEvent());
    }

    public function testReopenADraftThrows(): void
    {
        $assessment = MaterialityAssessmentMother::draft();

        $this->expectException(MaterialityAssessmentNotCompletedException::class);

        $assessment->reopen();
    }

    public function testRemoveIsIdempotent(): void
    {
        $assessment = MaterialityAssessmentMother::draft();

        $assessment->remove();
        $assessment->remove();

        $removed = array_filter($assessment->recordedEvents(), static fn($event) => $event instanceof MaterialityAssessmentRemoved);
        $this->assertCount(1, $removed);
        $this->assertTrue($assessment->isRemoved());
        $this->assertNotNull($assessment->removedAt());
    }

    public function testNextAndPreviousTopics(): void
    {
        $assessment = MaterialityAssessmentMother::draft(topics: [
            MaterialityTopicMother::from(name: 'B', id: (string) MaterialityTopicId::generate(), position: 2),
            MaterialityTopicMother::from(name: 'A', id: (string) MaterialityTopicId::generate(), position: 1),
            MaterialityTopicMother::from(name: 'C', id: (string) MaterialityTopicId::generate(), position: 3),
        ]);
        [$first, $second, $third] = $assessment->topics();

        $this->assertSame(['A', 'B', 'C'], [$first->name(), $second->name(), $third->name()]);
        $this->assertSame($second, $assessment->nextTopicAfter($first->id()));
        $this->assertNull($assessment->nextTopicAfter($third->id()));
        $this->assertSame($first, $assessment->previousTopicBefore($second->id()));
        $this->assertNull($assessment->previousTopicBefore($first->id()));
    }

    public function testTopicsAreOrderedByScopeBeforePosition(): void
    {
        $assessment = MaterialityAssessmentMother::draft(topics: [
            MaterialityTopicMother::from(name: 'A', id: (string) MaterialityTopicId::generate(), scope: MaterialityScope::Scope3, position: 1),
            MaterialityTopicMother::from(name: 'B', id: (string) MaterialityTopicId::generate(), scope: MaterialityScope::Scope1, position: 1),
            MaterialityTopicMother::from(name: 'C', id: (string) MaterialityTopicId::generate(), scope: MaterialityScope::Scope2, position: 1),
        ]);

        $this->assertSame(['B', 'C', 'A'], array_map(static fn($topic) => $topic->name(), $assessment->topics()));
    }

    public function testEventsCanBeRebuiltFromTheirPayload(): void
    {
        $assessment = MaterialityAssessmentMother::completed();
        $assessment->reopen();
        $assessment->remove();

        $events = array_values($assessment->recordedEvents());
        $classes = array_map(static fn(DomainEvent $event) => $event::class, $events);

        $this->assertContains(MaterialityAssessmentStarted::class, $classes);
        $this->assertContains(MaterialityAssessmentTopicAnswered::class, $classes);
        $this->assertContains(MaterialityAssessmentCompleted::class, $classes);
        $this->assertContains(MaterialityAssessmentReopened::class, $classes);
        $this->assertContains(MaterialityAssessmentRemoved::class, $classes);

        foreach ($events as $event) {
            $rebuilt = $event::rebuildFromPayload($event->id(), $event->occurredOn(), $event->payload());

            $this->assertEquals($event->payload(), $rebuilt->payload());
        }
    }

    public function testTheAggregateCanBeRebuiltFromTheEventPayloads(): void
    {
        $assessment = MaterialityAssessmentMother::completed(
            topics: [
                MaterialityTopicMother::from(name: 'A', id: (string) MaterialityTopicId::generate(), position: 1),
                MaterialityTopicMother::from(name: 'B', id: (string) MaterialityTopicId::generate(), position: 2),
            ],
            questions: $this->fiveQuestions(),
            value: 3,
        );

        $rebuiltEvents = [];

        foreach ($assessment->recordedEvents() as $key => $event) {
            $rebuiltEvents[$key] = $event::rebuildFromPayload($event->id(), $event->occurredOn(), $event->payload());
        }

        $rebuilt = new MaterialityAssessment(id: $assessment->id(), events: $rebuiltEvents);
        $rebuilt->rebuild();

        $this->assertEquals(
            array_map(static fn($topic) => [(string) $topic->id(), $topic->points(), $topic->minPoints(), $topic->maxPoints(), $topic->highestMagnitudeLevel()], $assessment->topics()),
            array_map(static fn($topic) => [(string) $topic->id(), $topic->points(), $topic->minPoints(), $topic->maxPoints(), $topic->highestMagnitudeLevel()], $rebuilt->topics()),
        );
        $this->assertEquals(
            array_map(static fn($answer) => [(string) $answer->id(), $answer->value()], $assessment->answers()),
            array_map(static fn($answer) => [(string) $answer->id(), $answer->value()], $rebuilt->answers()),
        );
        $this->assertSame(10, $rebuilt->totalAnswers());
        $this->assertTrue($rebuilt->isCompleted());
    }

    /** @return list<MaterialityQuestion> */
    private function fiveQuestions(): array
    {
        return [
            MaterialityQuestionMother::from(text: 'Magnitude', id: (string) MaterialityQuestionId::generate(), criterion: MaterialityCriterion::Magnitude, weight: 2, position: 1),
            MaterialityQuestionMother::from(text: 'Influence', id: (string) MaterialityQuestionId::generate(), criterion: MaterialityCriterion::Influence, position: 2),
            MaterialityQuestionMother::from(text: 'Risk', id: (string) MaterialityQuestionId::generate(), criterion: MaterialityCriterion::Risk, position: 3),
            MaterialityQuestionMother::from(text: 'Stakeholders', id: (string) MaterialityQuestionId::generate(), criterion: MaterialityCriterion::StakeholderInterest, position: 4),
            MaterialityQuestionMother::from(text: 'Outsourcing', id: (string) MaterialityQuestionId::generate(), criterion: MaterialityCriterion::Outsourcing, answerScale: MaterialityAnswerScale::YesNo, position: 5),
        ];
    }

    /** @param list<int> $values */
    private function answerFirstTopic(MaterialityAssessment $assessment, array $values): void
    {
        $topic = $assessment->topics()[0];
        $answers = $assessment->answersOf($topic->topicId());
        $valuesByAnswerId = [];

        foreach ($answers as $index => $answer) {
            $valuesByAnswerId[(string) $answer->id()] = $values[$index];
        }

        $assessment->answerTopic($topic->id(), $valuesByAnswerId);
    }
}
