<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityAssessment;

use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityAnswerScale;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityCriterion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;

final readonly class MaterialityQuestionSnapshot
{
    public function __construct(
        public MaterialityQuestionId $questionId,
        public MaterialityTopicId $topicId,
        public MaterialityCriterion $criterion,
        public string $text,
        public string $help,
        public MaterialityAnswerScale $answerScale,
        public int $weight,
        public int $position,
        public \DateTimeImmutable $questionUpdatedAt,
        public ?MaterialityAssessmentAnswerId $assessmentAnswerId = null,
    ) {}

    public static function fromQuestion(MaterialityQuestion $question, MaterialityTopicId $topicId): self
    {
        return new self(
            questionId: $question->id(),
            topicId: $topicId,
            criterion: $question->criterion(),
            text: $question->defaultText(),
            help: $question->defaultHelp(),
            answerScale: $question->answerScale(),
            weight: $question->weight(),
            position: $question->position(),
            questionUpdatedAt: $question->updatedAt() ?? $question->registeredAt(),
        );
    }

    public function withAssessmentAnswerId(MaterialityAssessmentAnswerId $assessmentAnswerId): self
    {
        return new self(
            questionId: $this->questionId,
            topicId: $this->topicId,
            criterion: $this->criterion,
            text: $this->text,
            help: $this->help,
            answerScale: $this->answerScale,
            weight: $this->weight,
            position: $this->position,
            questionUpdatedAt: $this->questionUpdatedAt,
            assessmentAnswerId: $assessmentAnswerId,
        );
    }

    public function key(): string
    {
        return sprintf('%s|%s', (string) $this->topicId, (string) $this->questionId);
    }

    /** @return array<string, mixed> */
    public function toPrimitives(): array
    {
        return [
            'questionId' => (string) $this->questionId,
            'topicId' => (string) $this->topicId,
            'criterion' => $this->criterion->value,
            'text' => $this->text,
            'help' => $this->help,
            'answerScale' => $this->answerScale->value,
            'weight' => $this->weight,
            'position' => $this->position,
            'questionUpdatedAt' => $this->questionUpdatedAt->format(\DateTimeInterface::ATOM),
            'assessmentAnswerId' => null !== $this->assessmentAnswerId ? (string) $this->assessmentAnswerId : null,
        ];
    }

    /** @param array<string, mixed> $primitives */
    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            questionId: new MaterialityQuestionId($primitives['questionId']),
            topicId: new MaterialityTopicId($primitives['topicId']),
            criterion: MaterialityCriterion::from($primitives['criterion']),
            text: $primitives['text'],
            help: $primitives['help'],
            answerScale: MaterialityAnswerScale::from($primitives['answerScale']),
            weight: $primitives['weight'],
            position: $primitives['position'],
            questionUpdatedAt: new \DateTimeImmutable($primitives['questionUpdatedAt']),
            assessmentAnswerId: null !== ($primitives['assessmentAnswerId'] ?? null) ? new MaterialityAssessmentAnswerId($primitives['assessmentAnswerId']) : null,
        );
    }
}
