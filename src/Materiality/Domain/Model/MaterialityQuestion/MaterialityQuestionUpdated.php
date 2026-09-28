<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityQuestion;

use App\Materiality\Domain\DomainEvent;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MaterialityQuestionUpdated extends DomainEvent
{
    protected static string $eventName = 'materialityQuestionUpdated';

    public function __construct(
        private MaterialityQuestionId $materialityQuestionId,
        private MaterialityCriterion $criterion,
        private ?MaterialityTopicId $topicId,
        private string $text,
        private string $help,
        private MaterialityAnswerScale $answerScale,
        private int $weight,
        private int $position,
        private bool $active,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function materialityQuestionId(): MaterialityQuestionId
    {
        return $this->materialityQuestionId;
    }

    public function criterion(): MaterialityCriterion
    {
        return $this->criterion;
    }

    public function topicId(): ?MaterialityTopicId
    {
        return $this->topicId;
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

    public function active(): bool
    {
        return $this->active;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->materialityQuestionId();
    }

    public function payload(): array
    {
        return [
            'materialityQuestionId' => (string) $this->materialityQuestionId,
            'criterion' => $this->criterion->value,
            'topicId' => null !== $this->topicId ? (string) $this->topicId : null,
            'text' => $this->text,
            'help' => $this->help,
            'answerScale' => $this->answerScale->value,
            'weight' => $this->weight,
            'position' => $this->position,
            'active' => $this->active,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new MaterialityQuestionUpdated(
            materialityQuestionId: new MaterialityQuestionId($payload['materialityQuestionId']),
            criterion: MaterialityCriterion::from($payload['criterion']),
            topicId: null !== $payload['topicId'] ? new MaterialityTopicId($payload['topicId']) : null,
            text: $payload['text'],
            help: $payload['help'],
            answerScale: MaterialityAnswerScale::from($payload['answerScale']),
            weight: (int) $payload['weight'],
            position: (int) $payload['position'],
            active: (bool) $payload['active'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
