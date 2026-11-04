<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialityAssessment\GetStep;

use App\Materiality\Application\MaterialityAssessment\GetStep\MaterialityQuestionnaireBoardBuilder;
use App\Materiality\Application\MaterialityAssessment\GetStep\MaterialityQuestionnaireGroup;
use App\Materiality\Application\MaterialityAssessment\GetStep\MaterialityQuestionnaireRow;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentAnswer;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityTopicSnapshot;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityScope;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicDirection;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicMother;
use PHPUnit\Framework\TestCase;

final class MaterialityQuestionnaireBoardBuilderTest extends TestCase
{
    public function testGroupsByScopeAndDirectionOmittingTheEmptyGroups(): void
    {
        $assessment = MaterialityAssessmentMother::draftWithQuestionsByTopic([
            $this->topic('Downstream', MaterialityScope::Scope3, MaterialityTopicDirection::Downstream),
            $this->topic('Without direction', MaterialityScope::Scope3, null),
            $this->topic('Scope 1', MaterialityScope::Scope1, null),
            $this->topic('Upstream', MaterialityScope::Scope3, MaterialityTopicDirection::Upstream),
            $this->topic('Upstream bis', MaterialityScope::Scope3, MaterialityTopicDirection::Upstream),
        ]);

        $board = $this->sut()->build($assessment, null);

        $this->assertSame(
            [['scope_1', null], ['scope_3', 'upstream'], ['scope_3', 'downstream'], ['scope_3', null]],
            array_map(static fn(MaterialityQuestionnaireGroup $group) => [$group->scope->value, $group->direction?->value], $board->groups),
        );
        $this->assertSame(
            ['Upstream', 'Upstream bis'],
            array_map(static fn(MaterialityQuestionnaireRow $row) => $row->topic->name(), $board->groups[1]->rows),
        );
    }

    public function testOnlyTheQuestionsOfEveryCategoryWithQuestionsAreGenericColumns(): void
    {
        $topicA = MaterialityTopicMother::random();
        $topicB = MaterialityTopicMother::random();
        $topicWithoutQuestions = MaterialityTopicMother::random();
        $volume = MaterialityQuestionMother::from(text: 'Volume', id: (string) MaterialityQuestionId::generate(), weight: 2, position: 2);
        $outsourcing = MaterialityQuestionMother::from(text: 'Outsourcing', id: (string) MaterialityQuestionId::generate(), position: 1);
        $specific = MaterialityQuestionMother::from(text: 'Specific', id: (string) MaterialityQuestionId::generate(), topicId: $topicB->id(), position: 3);

        $assessment = MaterialityAssessmentMother::draftWithQuestionsByTopic(
            array_map(static fn(MaterialityTopic $topic) => MaterialityTopicSnapshot::fromTopic($topic), [$topicA, $topicB, $topicWithoutQuestions]),
            [
                (string) $topicA->id() => [$volume, $outsourcing],
                (string) $topicB->id() => [$volume, $outsourcing, $specific],
            ],
        );

        $board = $this->sut()->build($assessment, null);

        $this->assertSame(['Outsourcing', 'Volume'], array_map(static fn($column) => $column->text, $board->columns));
        $this->assertSame(2, $board->columns[1]->weight);

        $rows = $board->groups[0]->rows;
        $rowB = $this->rowOf($rows, $topicB->id());

        $this->assertSame([(string) $outsourcing->id(), (string) $volume->id()], array_keys($rowB->genericAnswers));
        $this->assertSame(['Specific'], array_map(static fn(MaterialityAssessmentAnswer $answer) => $answer->text(), $rowB->specificAnswers));
        $this->assertSame([], $this->rowOf($rows, $topicWithoutQuestions->id())->genericAnswers);
    }

    public function testWithoutCommonQuestionsThereAreNoColumns(): void
    {
        $topicA = MaterialityTopicMother::random();
        $topicB = MaterialityTopicMother::random();

        $assessment = MaterialityAssessmentMother::draftWithQuestionsByTopic(
            array_map(static fn(MaterialityTopic $topic) => MaterialityTopicSnapshot::fromTopic($topic), [$topicA, $topicB]),
            [
                (string) $topicA->id() => [MaterialityQuestionMother::forTopic($topicA->id(), 'A only')],
                (string) $topicB->id() => [MaterialityQuestionMother::forTopic($topicB->id(), 'B only')],
            ],
        );

        $board = $this->sut()->build($assessment, null);

        $this->assertSame([], $board->columns);
        $this->assertCount(1, $board->groups[0]->rows[0]->specificAnswers);
    }

    public function testCountsTheAnswersByRowGroupAndInTotal(): void
    {
        $upstream = MaterialityTopicMother::from(name: 'Upstream', id: (string) MaterialityTopicId::generate(), direction: MaterialityTopicDirection::Upstream);
        $downstream = MaterialityTopicMother::from(name: 'Downstream', id: (string) MaterialityTopicId::generate(), direction: MaterialityTopicDirection::Downstream);
        $assessment = MaterialityAssessmentMother::draft(
            topics: [$upstream, $downstream],
            questions: [
                MaterialityQuestionMother::from(text: 'First', id: (string) MaterialityQuestionId::generate(), position: 1),
                MaterialityQuestionMother::from(text: 'Second', id: (string) MaterialityQuestionId::generate(), position: 2),
            ],
        );
        $answers = $assessment->answersOf($upstream->id());
        $upstreamTopic = array_values(array_filter($assessment->topics(), static fn($topic) => $topic->topicId()->equals($upstream->id())))[0];
        $assessment->answerTopic($upstreamTopic->id(), [(string) $answers[0]->id() => 3]);

        $board = $this->sut()->build($assessment, null);

        $this->assertSame([1, 2], [$board->groups[0]->rows[0]->answered, $board->groups[0]->rows[0]->total]);
        $this->assertSame([1, 2], [$board->groups[0]->answered, $board->groups[0]->total]);
        $this->assertSame([0, 2], [$board->groups[1]->answered, $board->groups[1]->total]);
        $this->assertSame([1, 4], [$board->answered, $board->total]);
        $this->assertFalse($board->fullyAnswered);

        MaterialityAssessmentMother::answerAll($assessment, 1);

        $this->assertTrue($this->sut()->build($assessment, null)->fullyAnswered);
    }

    public function testPropagatesTheYearOfTheInheritedAnswers(): void
    {
        $board = $this->sut()->build(MaterialityAssessmentMother::draft(), 2024);

        $this->assertSame(2024, $board->inheritedFromYear);
    }

    private function topic(string $name, MaterialityScope $scope, ?MaterialityTopicDirection $direction): MaterialityTopicSnapshot
    {
        return new MaterialityTopicSnapshot(
            topicId: MaterialityTopicId::generate(),
            number: null,
            scope: $scope,
            direction: $direction,
            name: $name,
            description: '',
            position: 0,
        );
    }

    /** @param list<MaterialityQuestionnaireRow> $rows */
    private function rowOf(array $rows, MaterialityTopicId $topicId): MaterialityQuestionnaireRow
    {
        foreach ($rows as $row) {
            if ($row->topic->topicId()->equals($topicId)) {
                return $row;
            }
        }

        $this->fail(sprintf('Row of topic %s not found', $topicId));
    }

    private function sut(): MaterialityQuestionnaireBoardBuilder
    {
        return new MaterialityQuestionnaireBoardBuilder();
    }
}
