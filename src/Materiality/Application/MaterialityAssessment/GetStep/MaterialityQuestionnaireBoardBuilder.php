<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment\GetStep;

use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessment;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentAnswer;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentTopic;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityScope;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicDirection;

final class MaterialityQuestionnaireBoardBuilder
{
    private const array DIRECTION_ORDER = [
        MaterialityTopicDirection::Upstream->value => 0,
        MaterialityTopicDirection::Downstream->value => 1,
    ];

    public function build(MaterialityAssessment $assessment, ?int $inheritedFromYear): MaterialityQuestionnaireBoard
    {
        $answersByTopic = [];

        foreach ($assessment->topics() as $topic) {
            $answersByTopic[] = [$topic, $assessment->answersOf($topic->topicId())];
        }

        $columns = $this->columns($answersByTopic);
        $genericQuestionIds = array_map(static fn(MaterialityQuestionnaireColumn $column) => (string) $column->questionId, $columns);

        /** @var array<string, array{scope: MaterialityScope, direction: ?MaterialityTopicDirection, rows: list<MaterialityQuestionnaireRow>}> $groups */
        $groups = [];

        foreach ($answersByTopic as [$topic, $answers]) {
            $key = $topic->scope()->value . '|' . ($topic->direction()->value ?? '');
            $groups[$key] ??= ['scope' => $topic->scope(), 'direction' => $topic->direction(), 'rows' => []];
            $groups[$key]['rows'][] = $this->row($topic, $answers, $genericQuestionIds);
        }

        uasort($groups, fn(array $a, array $b) => [$a['scope']->value, $this->directionOrder($a['direction'])] <=> [$b['scope']->value, $this->directionOrder($b['direction'])]);

        return new MaterialityQuestionnaireBoard(
            groups: array_values(array_map(
                static fn(array $group) => new MaterialityQuestionnaireGroup(
                    scope: $group['scope'],
                    direction: $group['direction'],
                    rows: $group['rows'],
                    answered: array_sum(array_map(static fn(MaterialityQuestionnaireRow $row) => $row->answered, $group['rows'])),
                    total: array_sum(array_map(static fn(MaterialityQuestionnaireRow $row) => $row->total, $group['rows'])),
                ),
                $groups,
            )),
            columns: $columns,
            answered: $assessment->answeredCount(),
            total: $assessment->totalAnswers(),
            fullyAnswered: $assessment->isFullyAnswered(),
            inheritedFromYear: $inheritedFromYear,
        );
    }

    /**
     * @param list<array{0: MaterialityAssessmentTopic, 1: list<MaterialityAssessmentAnswer>}> $answersByTopic
     *
     * @return list<MaterialityQuestionnaireColumn>
     */
    private function columns(array $answersByTopic): array
    {
        $answered = array_values(array_filter($answersByTopic, static fn(array $entry) => [] !== $entry[1]));

        if ([] === $answered) {
            return [];
        }

        /** @var array<string, MaterialityAssessmentAnswer> $firstAppearances */
        $firstAppearances = [];
        /** @var array<string, int> $occurrences */
        $occurrences = [];

        foreach ($answered as [, $answers]) {
            foreach ($answers as $answer) {
                $questionId = (string) $answer->questionId();
                $firstAppearances[$questionId] ??= $answer;
                $occurrences[$questionId] = ($occurrences[$questionId] ?? 0) + 1;
            }
        }

        $generic = array_filter(
            $firstAppearances,
            static fn(MaterialityAssessmentAnswer $answer) => $occurrences[(string) $answer->questionId()] === count($answered),
        );

        $columns = array_values($generic);

        usort($columns, static fn(MaterialityAssessmentAnswer $a, MaterialityAssessmentAnswer $b) => $a->position() <=> $b->position());

        return array_map(
            static fn(MaterialityAssessmentAnswer $answer) => new MaterialityQuestionnaireColumn(
                questionId: $answer->questionId(),
                text: $answer->text(),
                help: $answer->help(),
                criterion: $answer->criterion(),
                weight: $answer->weight(),
                answerScale: $answer->answerScale(),
            ),
            $columns,
        );
    }

    /**
     * @param list<MaterialityAssessmentAnswer> $answers
     * @param list<string>                      $genericQuestionIds
     */
    private function row(MaterialityAssessmentTopic $topic, array $answers, array $genericQuestionIds): MaterialityQuestionnaireRow
    {
        $genericAnswers = [];
        $specificAnswers = [];

        foreach ($answers as $answer) {
            $questionId = (string) $answer->questionId();

            if (in_array($questionId, $genericQuestionIds, true)) {
                $genericAnswers[$questionId] = $answer;
            } else {
                $specificAnswers[] = $answer;
            }
        }

        return new MaterialityQuestionnaireRow(
            topic: $topic,
            genericAnswers: $genericAnswers,
            specificAnswers: $specificAnswers,
            answered: count(array_filter($answers, static fn(MaterialityAssessmentAnswer $answer) => $answer->isAnswered())),
            total: count($answers),
        );
    }

    private function directionOrder(?MaterialityTopicDirection $direction): int
    {
        return null === $direction ? count(self::DIRECTION_ORDER) : self::DIRECTION_ORDER[$direction->value];
    }
}
