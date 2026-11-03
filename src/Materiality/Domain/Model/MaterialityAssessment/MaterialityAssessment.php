<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityAssessment;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityVerdict;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityCriterion;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialityDecisionRule;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\AggregateRoot;
use Doctrine\Common\Collections\Collection;

class MaterialityAssessment implements \Stringable
{
    use AggregateRoot;

    /** @var iterable<MaterialityAssessmentTopic>|Collection<int, MaterialityAssessmentTopic> */
    private iterable $topics = [];

    /** @var iterable<MaterialityAssessmentAnswer>|Collection<int, MaterialityAssessmentAnswer> */
    private iterable $answers = [];

    /** @param array<mixed> $events */
    public function __construct(
        private MaterialityAssessmentId $id,
        private MaterialityAssessmentSubjectType $subjectType = MaterialityAssessmentSubjectType::Property,
        private string $carbonFootprintId = '',
        private ?AccountId $accountId = null,
        private int $year = 0,
        private ?MaterialitySectorId $sectorId = null,
        private ?string $sectorName = null,
        private MaterialityAssessmentStatus $status = MaterialityAssessmentStatus::Draft,
        private ?UserId $completedBy = null,
        private ?\DateTimeImmutable $completedAt = null,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): MaterialityAssessmentId
    {
        return $this->id;
    }

    public function subjectType(): MaterialityAssessmentSubjectType
    {
        return $this->subjectType;
    }

    public function carbonFootprintId(): string
    {
        return $this->carbonFootprintId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function year(): int
    {
        return $this->year;
    }

    public function sectorId(): ?MaterialitySectorId
    {
        return $this->sectorId;
    }

    public function sectorName(): ?string
    {
        return $this->sectorName;
    }

    public function status(): MaterialityAssessmentStatus
    {
        return $this->status;
    }

    public function isCompleted(): bool
    {
        return $this->status->isCompleted();
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function completedBy(): ?UserId
    {
        return $this->completedBy;
    }

    public function completedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    /** @return list<MaterialityAssessmentTopic> */
    public function topics(): array
    {
        $topics = is_array($this->topics) ? array_values($this->topics) : array_values(iterator_to_array($this->topics));

        usort($topics, static fn(MaterialityAssessmentTopic $a, MaterialityAssessmentTopic $b) => [$a->scope()->value, $a->position(), $a->name()] <=> [$b->scope()->value, $b->position(), $b->name()]);

        return $topics;
    }

    /** @return list<MaterialityAssessmentTopic> */
    public function verdicts(): array
    {
        return array_values(array_filter($this->topics(), static fn(MaterialityAssessmentTopic $topic) => $topic->hasVerdict()));
    }

    public function topic(MaterialityAssessmentTopicId $assessmentTopicId): MaterialityAssessmentTopic
    {
        foreach ($this->topics() as $topic) {
            if ($topic->id()->equals($assessmentTopicId)) {
                return $topic;
            }
        }

        throw new MaterialityAssessmentTopicNotFoundException($assessmentTopicId);
    }

    /** @return list<MaterialityAssessmentAnswer> */
    public function answers(): array
    {
        return is_array($this->answers) ? array_values($this->answers) : array_values(iterator_to_array($this->answers));
    }

    /** @return list<MaterialityAssessmentAnswer> */
    public function answersOf(MaterialityTopicId $topicId): array
    {
        $answers = array_values(array_filter(
            $this->answers(),
            static fn(MaterialityAssessmentAnswer $answer) => $answer->topicId()->equals($topicId),
        ));

        usort($answers, static fn(MaterialityAssessmentAnswer $a, MaterialityAssessmentAnswer $b) => $a->position() <=> $b->position());

        return $answers;
    }

    public function answeredCount(): int
    {
        return count(array_filter($this->answers(), static fn(MaterialityAssessmentAnswer $answer) => $answer->isAnswered()));
    }

    public function totalAnswers(): int
    {
        return count($this->answers());
    }

    public function isFullyAnswered(): bool
    {
        return $this->answeredCount() === $this->totalAnswers();
    }

    public function nextTopicAfter(MaterialityAssessmentTopicId $assessmentTopicId): ?MaterialityAssessmentTopic
    {
        $topics = $this->topics();

        foreach ($topics as $index => $topic) {
            if ($topic->id()->equals($assessmentTopicId)) {
                return $topics[$index + 1] ?? null;
            }
        }

        return null;
    }

    public function previousTopicBefore(MaterialityAssessmentTopicId $assessmentTopicId): ?MaterialityAssessmentTopic
    {
        $topics = $this->topics();

        foreach ($topics as $index => $topic) {
            if ($topic->id()->equals($assessmentTopicId)) {
                return $topics[$index - 1] ?? null;
            }
        }

        return null;
    }

    /**
     * @param list<MaterialityTopicSnapshot>    $topics
     * @param list<MaterialityQuestionSnapshot> $questions
     * @param array<string, int>                $initialValues
     */
    public function start(
        MaterialityAssessmentSubjectType $subjectType,
        string $carbonFootprintId,
        AccountId $accountId,
        int $year,
        ?MaterialitySectorId $sectorId,
        ?string $sectorName,
        array $topics,
        array $questions,
        array $initialValues = [],
    ): void {
        $this->recordApplyAndPublish(
            new MaterialityAssessmentStarted(
                materialityAssessmentId: $this->id,
                subjectType: $subjectType,
                carbonFootprintId: $carbonFootprintId,
                accountId: $accountId,
                year: $year,
                sectorId: $sectorId,
                sectorName: $sectorName,
                topics: array_map(
                    static fn(MaterialityTopicSnapshot $topic) => $topic->withAssessmentTopicId($topic->assessmentTopicId ?? MaterialityAssessmentTopicId::generate()),
                    $topics,
                ),
                questions: array_map(
                    static fn(MaterialityQuestionSnapshot $question) => $question->withAssessmentAnswerId($question->assessmentAnswerId ?? MaterialityAssessmentAnswerId::generate()),
                    $questions,
                ),
                initialValues: $initialValues,
            ),
        );
    }

    /** @param array<string, int|null> $valuesByAnswerId */
    public function answerTopic(
        MaterialityAssessmentTopicId $assessmentTopicId,
        array $valuesByAnswerId,
        \DateTimeImmutable $at = new \DateTimeImmutable('NOW'),
    ): void {
        $this->ensureNotRemoved();
        $this->ensureIsDraft();

        $topic = $this->topic($assessmentTopicId);
        $answers = $this->answersIndexedById($topic->topicId());

        foreach ($valuesByAnswerId as $answerId => $value) {
            $answer = $answers[(string) $answerId] ?? null;

            if (null === $answer) {
                throw InvalidMaterialityAnswerValueException::foreignAnswer((string) $answerId);
            }

            if (!$answer->accepts($value)) {
                throw InvalidMaterialityAnswerValueException::outOfScale((int) $value, (string) $answerId);
            }
        }

        $this->recordApplyAndPublish(
            new MaterialityAssessmentTopicAnswered(
                materialityAssessmentId: $this->id,
                assessmentTopicId: $assessmentTopicId,
                topicId: $topic->topicId(),
                values: $valuesByAnswerId,
                answeredAt: $at,
            ),
        );
    }

    public function complete(UserId $userId, MaterialityDecisionRule $rule): void
    {
        $this->ensureNotRemoved();
        $this->ensureIsDraft();

        if (!$this->isFullyAnswered()) {
            throw new MaterialityAssessmentIncompleteException($this->id);
        }

        assert($this->accountId instanceof AccountId);

        $scores = [];

        foreach ($this->topics() as $topic) {
            $answers = $this->answersOf($topic->topicId());
            $magnitudeLevels = array_map(
                static fn(MaterialityAssessmentAnswer $answer) => (int) $answer->value(),
                array_filter($answers, static fn(MaterialityAssessmentAnswer $answer) => MaterialityCriterion::Magnitude === $answer->criterion()),
            );

            $points = array_sum(array_map(static fn(MaterialityAssessmentAnswer $answer) => $answer->points(), $answers));
            $minPoints = array_sum(array_map(static fn(MaterialityAssessmentAnswer $answer) => $answer->minPoints(), $answers));
            $maxPoints = array_sum(array_map(static fn(MaterialityAssessmentAnswer $answer) => $answer->maxPoints(), $answers));
            $highestMagnitudeLevel = [] !== $magnitudeLevels ? max($magnitudeLevels) : null;
            $scorePercent = MaterialityAssessmentTopic::scorePercentOf($points, $minPoints, $maxPoints);
            $verdict = $rule->decide($scorePercent, $highestMagnitudeLevel);

            $scores[] = [
                'assessmentTopicId' => (string) $topic->id(),
                'topicId' => (string) $topic->topicId(),
                'topicNumber' => $topic->topicNumber(),
                'scope' => $topic->scope()->value,
                'points' => $points,
                'minPoints' => $minPoints,
                'maxPoints' => $maxPoints,
                'highestMagnitudeLevel' => $highestMagnitudeLevel,
                'scorePercent' => $scorePercent,
                'thresholdPercent' => $rule->thresholdPercent,
                'forcedByHighMagnitude' => null !== $verdict && $rule->isForcedByHighMagnitude($highestMagnitudeLevel),
                'verdict' => $verdict?->value,
            ];
        }

        $this->recordApplyAndPublish(
            new MaterialityAssessmentCompleted(
                materialityAssessmentId: $this->id,
                subjectType: $this->subjectType,
                carbonFootprintId: $this->carbonFootprintId,
                accountId: $this->accountId,
                year: $this->year,
                completedBy: $userId,
                completedAt: new \DateTimeImmutable('NOW'),
                scores: $scores,
            ),
        );
    }

    public function reopen(): void
    {
        $this->ensureNotRemoved();

        if (!$this->isCompleted()) {
            throw new MaterialityAssessmentNotCompletedException($this->id);
        }

        $this->recordApplyAndPublish(
            new MaterialityAssessmentReopened(
                materialityAssessmentId: $this->id,
            ),
        );
    }

    public function remove(): void
    {
        if ($this->removed) {
            return;
        }

        $this->recordApplyAndPublish(
            new MaterialityAssessmentRemoved(
                materialityAssessmentId: $this->id,
                subjectType: $this->subjectType,
                carbonFootprintId: $this->carbonFootprintId,
            ),
        );
    }

    private function ensureNotRemoved(): void
    {
        if ($this->removed) {
            throw new MaterialityAssessmentNotFoundException($this->id);
        }
    }

    private function ensureIsDraft(): void
    {
        if ($this->isCompleted()) {
            throw new MaterialityAssessmentAlreadyCompletedException($this->id);
        }
    }

    /** @return array<string, MaterialityAssessmentAnswer> */
    private function answersIndexedById(MaterialityTopicId $topicId): array
    {
        $indexed = [];

        foreach ($this->answersOf($topicId) as $answer) {
            $indexed[(string) $answer->id()] = $answer;
        }

        return $indexed;
    }

    private function attachTopic(MaterialityAssessmentTopic $topic): void
    {
        if (is_array($this->topics)) {
            $this->topics[] = $topic;
        } elseif ($this->topics instanceof Collection) {
            $this->topics->add($topic);
        }
    }

    private function attachAnswer(MaterialityAssessmentAnswer $answer): void
    {
        if (is_array($this->answers)) {
            $this->answers[] = $answer;
        } elseif ($this->answers instanceof Collection) {
            $this->answers->add($answer);
        }
    }

    public function applyMaterialityAssessmentStarted(MaterialityAssessmentStarted $event): void
    {
        $this->subjectType = $event->subjectType();
        $this->carbonFootprintId = $event->carbonFootprintId();
        $this->accountId = $event->accountId();
        $this->year = $event->year();
        $this->sectorId = $event->sectorId();
        $this->sectorName = $event->sectorName();
        $this->status = MaterialityAssessmentStatus::Draft;
        $this->registeredAt = $event->occurredOn();

        foreach ($event->topics() as $topic) {
            $this->attachTopic(
                new MaterialityAssessmentTopic(
                    id: $topic->assessmentTopicId ?? throw new \LogicException('Assessment topic id is missing'),
                    assessment: $this,
                    topicId: $topic->topicId,
                    topicNumber: $topic->number,
                    scope: $topic->scope,
                    direction: $topic->direction,
                    name: $topic->name,
                    description: $topic->description,
                    position: $topic->position,
                ),
            );
        }

        $initialValues = $event->initialValues();

        foreach ($event->questions() as $question) {
            $answer = new MaterialityAssessmentAnswer(
                id: $question->assessmentAnswerId ?? throw new \LogicException('Assessment answer id is missing'),
                assessment: $this,
                topicId: $question->topicId,
                questionId: $question->questionId,
                criterion: $question->criterion,
                text: $question->text,
                help: $question->help,
                answerScale: $question->answerScale,
                weight: $question->weight,
                position: $question->position,
                questionUpdatedAt: $question->questionUpdatedAt,
            );

            $initialValue = $initialValues[$question->key()] ?? null;

            if (null !== $initialValue && $answer->accepts($initialValue)) {
                $answer->answer($initialValue, $event->occurredOn());
            }

            $this->attachAnswer($answer);
        }
    }

    public function applyMaterialityAssessmentTopicAnswered(MaterialityAssessmentTopicAnswered $event): void
    {
        $answers = $this->answersIndexedById($event->topicId());

        foreach ($event->values() as $answerId => $value) {
            ($answers[(string) $answerId] ?? null)?->answer($value, $event->answeredAt());
        }

        $this->updatedAt = $event->occurredOn();
    }

    public function applyMaterialityAssessmentCompleted(MaterialityAssessmentCompleted $event): void
    {
        foreach ($event->scores() as $score) {
            $this->topic(new MaterialityAssessmentTopicId($score['assessmentTopicId']))->score(
                points: $score['points'],
                minPoints: $score['minPoints'],
                maxPoints: $score['maxPoints'],
                highestMagnitudeLevel: $score['highestMagnitudeLevel'],
                thresholdPercent: null !== $score['verdict'] ? $score['thresholdPercent'] : null,
                forcedByHighMagnitude: null !== $score['verdict'] ? $score['forcedByHighMagnitude'] : null,
                verdict: null !== $score['verdict'] ? MaterialityVerdict::from($score['verdict']) : null,
            );
        }

        $this->status = MaterialityAssessmentStatus::Completed;
        $this->completedBy = $event->completedBy();
        $this->completedAt = $event->completedAt();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyMaterialityAssessmentReopened(MaterialityAssessmentReopened $event): void
    {
        foreach ($this->topics() as $topic) {
            $topic->clearScore();
        }

        $this->status = MaterialityAssessmentStatus::Draft;
        $this->completedBy = null;
        $this->completedAt = null;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyMaterialityAssessmentRemoved(MaterialityAssessmentRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return sprintf('%s %s %d', $this->subjectType->value, $this->carbonFootprintId, $this->year);
    }
}
