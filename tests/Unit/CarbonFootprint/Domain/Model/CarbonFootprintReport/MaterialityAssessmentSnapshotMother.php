<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityVerdict;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAssessmentSnapshot;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAssessmentSnapshotAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAssessmentSnapshotTopic;

class MaterialityAssessmentSnapshotMother
{
    public const string ASSESSMENT_ID = 'materiality-assessment-id';

    /** @var array<int, array{name: string, values: list<int>, verdict: MaterialityVerdict, forced: bool}> */
    private const array TOPICS = [
        1 => ['name' => 'Bienes y servicios adquiridos', 'values' => [3, 2, 2, 3, 3], 'verdict' => MaterialityVerdict::Calculate, 'forced' => false],
        10 => ['name' => 'Procesamiento de los productos vendidos', 'values' => [1, 1, 1, 1, 1], 'verdict' => MaterialityVerdict::Omit, 'forced' => false],
        11 => ['name' => 'Uso de los productos vendidos', 'values' => [1, 2, 2, 1, 1], 'verdict' => MaterialityVerdict::Omit, 'forced' => false],
        2 => ['name' => 'Bienes de capital', 'values' => [3, 1, 1, 1, 1], 'verdict' => MaterialityVerdict::Calculate, 'forced' => true],
    ];

    /** @var list<array{id: string, criterion: string, text: string, scale: string, weight: int}> */
    private const array QUESTIONS = [
        ['id' => 'question-magnitude', 'criterion' => 'magnitude', 'text' => '¿Qué magnitud tienen las emisiones de esta categoría?', 'scale' => 'level', 'weight' => 2],
        ['id' => 'question-influence', 'criterion' => 'influence', 'text' => '¿Qué capacidad de influencia tiene la organización?', 'scale' => 'level', 'weight' => 1],
        ['id' => 'question-risk', 'criterion' => 'risk', 'text' => '¿Qué riesgo supone la categoría?', 'scale' => 'level', 'weight' => 1],
        ['id' => 'question-stakeholders', 'criterion' => 'stakeholder_interest', 'text' => '¿Interesa a los grupos de interés?', 'scale' => 'level', 'weight' => 1],
        ['id' => 'question-outsourcing', 'criterion' => 'outsourcing', 'text' => '¿Está externalizada la actividad?', 'scale' => 'yes_no', 'weight' => 1],
    ];

    public static function completed(): MaterialityAssessmentSnapshot
    {
        return self::build(true);
    }

    public static function draft(): MaterialityAssessmentSnapshot
    {
        return self::build(false);
    }

    public static function withChangedQuestions(): MaterialityAssessmentSnapshot
    {
        return self::build(true, changedQuestionId: 'question-risk', removedQuestionId: 'question-outsourcing');
    }

    public static function withCustomTopic(): MaterialityAssessmentSnapshot
    {
        return self::build(true, withCustomTopic: true);
    }

    public static function topicId(?int $topicNumber): string
    {
        return sprintf('topic-%s', $topicNumber ?? 'custom');
    }

    public static function questionSetVersion(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-01-15 10:00:00');
    }

    private static function build(bool $completed, ?string $changedQuestionId = null, ?string $removedQuestionId = null, bool $withCustomTopic = false): MaterialityAssessmentSnapshot
    {
        $topics = [];
        $answers = [];
        $definitions = self::TOPICS;

        if ($withCustomTopic) {
            $definitions[0] = ['name' => 'Categoría propia', 'values' => [1, 1, 1, 1, 1], 'verdict' => MaterialityVerdict::Omit, 'forced' => false];
        }

        foreach ($definitions as $number => $definition) {
            $topicNumber = 0 === $number ? null : $number;
            $topicId = self::topicId($topicNumber);
            $points = 0;

            foreach (self::QUESTIONS as $index => $question) {
                $value = $definition['values'][$index];
                $points += $value * $question['weight'];
                $answers[] = new MaterialityAssessmentSnapshotAnswer(
                    topicId: $topicId,
                    questionId: $question['id'],
                    criterionKey: $question['criterion'],
                    text: $question['text'],
                    answerLabelKey: sprintf('%s_%d', $question['scale'], $value),
                    answerScaleKey: $question['scale'],
                    weight: $question['weight'],
                    value: $value,
                    points: $value * $question['weight'],
                    minPoints: $question['weight'],
                    maxPoints: 3 * $question['weight'],
                    questionUpdatedAt: self::questionSetVersion(),
                    questionChangedSince: $question['id'] === $changedQuestionId,
                    questionRemoved: $question['id'] === $removedQuestionId,
                );
            }

            $scorePercent = round(100 * ($points - 6) / (18 - 6), 2);

            $topics[] = new MaterialityAssessmentSnapshotTopic(
                topicId: $topicId,
                topicNumber: $topicNumber,
                name: $definition['name'],
                points: $completed ? $points : null,
                minPoints: $completed ? 6 : null,
                maxPoints: $completed ? 18 : null,
                scorePercent: $completed ? $scorePercent : null,
                thresholdPercent: $completed ? 50 : null,
                forcedByHighMagnitude: $completed && $definition['forced'],
                verdict: $completed ? $definition['verdict'] : null,
            );
        }

        return new MaterialityAssessmentSnapshot(
            assessmentId: self::ASSESSMENT_ID,
            completed: $completed,
            startedAt: new \DateTimeImmutable('2026-03-10 09:00:00'),
            completedAt: $completed ? new \DateTimeImmutable('2026-03-12 12:00:00') : null,
            completedByName: $completed ? 'jgarcia' : null,
            sectorName: 'Hostelería',
            thresholdPercent: $completed ? 50 : null,
            highMagnitudeRule: true,
            questionSetVersion: self::questionSetVersion(),
            questionSetChangedSince: null !== $changedQuestionId || null !== $removedQuestionId,
            topics: $topics,
            answers: $answers,
        );
    }
}
