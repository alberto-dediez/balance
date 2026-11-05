<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment\Snapshot;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityVerdict;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAssessmentSnapshot;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAssessmentSnapshotAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAssessmentSnapshotProvider;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAssessmentSnapshotTopic;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentAnswer;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentRepository;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentTopic;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityAnswerScale;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepository;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityScope;
use App\Security\Domain\Model\User\UserRepository;

class MaterialityAssessmentSnapshotAdapter implements MaterialityAssessmentSnapshotProvider
{
    public function __construct(
        private MaterialityAssessmentRepository $materialityAssessmentRepository,
        private MaterialityQuestionRepository $materialityQuestionRepository,
        private UserRepository $userRepository,
    ) {}

    public function find(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId): ?MaterialityAssessmentSnapshot
    {
        $assessment = $this->materialityAssessmentRepository->findCurrentByCarbonFootprint(
            MaterialityAssessmentSubjectType::from($subjectType->value),
            $carbonFootprintId,
        );

        if (null === $assessment) {
            return null;
        }

        $scope3Topics = array_values(array_filter(
            $assessment->topics(),
            static fn(MaterialityAssessmentTopic $topic): bool => MaterialityScope::Scope3 === $topic->scope(),
        ));

        $topics = array_map(
            static fn(MaterialityAssessmentTopic $topic): MaterialityAssessmentSnapshotTopic => new MaterialityAssessmentSnapshotTopic(
                topicId: (string) $topic->topicId(),
                topicNumber: $topic->topicNumber(),
                name: $topic->name(),
                points: $topic->points(),
                minPoints: $topic->minPoints(),
                maxPoints: $topic->maxPoints(),
                scorePercent: $topic->scorePercent(),
                thresholdPercent: $topic->thresholdPercent(),
                forcedByHighMagnitude: $topic->isForcedByHighMagnitude(),
                verdict: $topic->verdict(),
            ),
            $scope3Topics,
        );

        $questions = [];
        $answers = [];

        foreach ($scope3Topics as $topic) {
            foreach ($assessment->answersOf($topic->topicId()) as $answer) {
                $answers[] = $this->answerOf($answer, $questions);
            }
        }

        $thresholdPercent = null;

        foreach ($assessment->verdicts() as $topic) {
            $thresholdPercent = $topic->thresholdPercent();

            break;
        }

        $questionSetVersion = null;
        $questionSetChangedSince = false;

        foreach ($answers as $answer) {
            if (null === $questionSetVersion || $answer->questionUpdatedAt > $questionSetVersion) {
                $questionSetVersion = $answer->questionUpdatedAt;
            }

            $questionSetChangedSince = $questionSetChangedSince || $answer->questionChangedSince || $answer->questionRemoved;
        }

        $completedBy = $assessment->completedBy();
        $completedByUser = null === $completedBy ? null : $this->userRepository->find($completedBy);

        return new MaterialityAssessmentSnapshot(
            assessmentId: (string) $assessment->id(),
            completed: $assessment->isCompleted(),
            startedAt: $assessment->registeredAt(),
            completedAt: $assessment->completedAt(),
            completedByName: null === $completedByUser ? null : (string) $completedByUser,
            sectorName: $assessment->sectorName(),
            thresholdPercent: $thresholdPercent,
            highMagnitudeRule: $this->highMagnitudeRuleOf($scope3Topics),
            questionSetVersion: $questionSetVersion,
            questionSetChangedSince: $questionSetChangedSince,
            topics: $topics,
            answers: $answers,
        );
    }

    /** @param array<string, ?MaterialityQuestion> $questions */
    private function answerOf(MaterialityAssessmentAnswer $answer, array &$questions): MaterialityAssessmentSnapshotAnswer
    {
        $questionId = (string) $answer->questionId();

        if (!array_key_exists($questionId, $questions)) {
            $questions[$questionId] = $this->materialityQuestionRepository->find($answer->questionId());
        }

        $question = $questions[$questionId];
        $value = $answer->value();

        return new MaterialityAssessmentSnapshotAnswer(
            topicId: (string) $answer->topicId(),
            questionId: $questionId,
            criterionKey: $answer->criterion()->value,
            text: $answer->text(),
            answerLabelKey: null === $value ? '' : $answer->answerScale()->labelKey($value),
            answerScaleKey: $answer->answerScale()->value,
            weight: $answer->weight(),
            value: $value,
            points: $answer->points(),
            minPoints: $answer->minPoints(),
            maxPoints: $answer->maxPoints(),
            questionUpdatedAt: $answer->questionUpdatedAt(),
            questionChangedSince: null !== $question && ($question->updatedAt() ?? $question->registeredAt()) > $answer->questionUpdatedAt(),
            questionRemoved: null === $question,
        );
    }

    /** @param list<MaterialityAssessmentTopic> $topics */
    private function highMagnitudeRuleOf(array $topics): ?bool
    {
        $omittedWithHighestMagnitude = false;

        foreach ($topics as $topic) {
            if ($topic->isForcedByHighMagnitude()) {
                return true;
            }

            if (MaterialityVerdict::Omit === $topic->verdict() && MaterialityAnswerScale::Level->max() === $topic->highestMagnitudeLevel()) {
                $omittedWithHighestMagnitude = true;
            }
        }

        return $omittedWithHighestMagnitude ? false : null;
    }
}
