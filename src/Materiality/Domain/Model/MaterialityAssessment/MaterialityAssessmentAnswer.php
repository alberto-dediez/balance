<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityAssessment;

use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityAnswerScale;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityCriterion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;

class MaterialityAssessmentAnswer
{
    public function __construct(
        private MaterialityAssessmentAnswerId $id,
        private MaterialityAssessment $assessment,
        private MaterialityTopicId $topicId,
        private MaterialityQuestionId $questionId,
        private MaterialityCriterion $criterion,
        private string $text,
        private string $help,
        private MaterialityAnswerScale $answerScale,
        private int $weight,
        private int $position,
        private \DateTimeImmutable $questionUpdatedAt,
        private ?int $value = null,
        private ?\DateTimeImmutable $answeredAt = null,
    ) {}

    public function id(): MaterialityAssessmentAnswerId
    {
        return $this->id;
    }

    public function assessment(): MaterialityAssessment
    {
        return $this->assessment;
    }

    public function topicId(): MaterialityTopicId
    {
        return $this->topicId;
    }

    public function questionId(): MaterialityQuestionId
    {
        return $this->questionId;
    }

    public function criterion(): MaterialityCriterion
    {
        return $this->criterion;
    }

    public function text(): string
    {
        return $this->text;
    }

    public function help(): string
    {
        return $this->help;
    }

    public function answerScale(): MaterialityAnswerScale
    {
        return $this->answerScale;
    }

    public function weight(): int
    {
        return $this->weight;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function questionUpdatedAt(): \DateTimeImmutable
    {
        return $this->questionUpdatedAt;
    }

    public function value(): ?int
    {
        return $this->value;
    }

    public function answeredAt(): ?\DateTimeImmutable
    {
        return $this->answeredAt;
    }

    public function isAnswered(): bool
    {
        return null !== $this->value;
    }

    public function accepts(?int $value): bool
    {
        return null === $value || in_array($value, $this->answerScale->values(), true);
    }

    public function answer(?int $value, \DateTimeImmutable $at): void
    {
        if (!$this->accepts($value)) {
            throw InvalidMaterialityAnswerValueException::outOfScale((int) $value, (string) $this->id);
        }

        $this->value = $value;
        $this->answeredAt = null !== $value ? $at : null;
    }

    public function points(): int
    {
        return ($this->value ?? 0) * $this->weight;
    }

    public function minPoints(): int
    {
        return min($this->answerScale->values()) * $this->weight;
    }

    public function maxPoints(): int
    {
        return $this->answerScale->max() * $this->weight;
    }
}
