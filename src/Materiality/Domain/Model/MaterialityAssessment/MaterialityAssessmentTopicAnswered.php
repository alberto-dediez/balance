<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityAssessment;

use App\Materiality\Domain\DomainEvent;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MaterialityAssessmentTopicAnswered extends DomainEvent
{
    protected static string $eventName = 'materialityAssessmentTopicAnswered';

    /** @param array<string, int|null> $values */
    public function __construct(
        private MaterialityAssessmentId $materialityAssessmentId,
        private MaterialityAssessmentTopicId $assessmentTopicId,
        private MaterialityTopicId $topicId,
        private array $values,
        private \DateTimeImmutable $answeredAt,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function materialityAssessmentId(): MaterialityAssessmentId
    {
        return $this->materialityAssessmentId;
    }

    public function assessmentTopicId(): MaterialityAssessmentTopicId
    {
        return $this->assessmentTopicId;
    }

    public function topicId(): MaterialityTopicId
    {
        return $this->topicId;
    }

    /** @return array<string, int|null> */
    public function values(): array
    {
        return $this->values;
    }

    public function answeredAt(): \DateTimeImmutable
    {
        return $this->answeredAt;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->materialityAssessmentId;
    }

    public function payload(): array
    {
        return [
            'materialityAssessmentId' => (string) $this->materialityAssessmentId,
            'assessmentTopicId' => (string) $this->assessmentTopicId,
            'topicId' => (string) $this->topicId,
            'values' => $this->values,
            'answeredAt' => $this->answeredAt->format(\DateTimeInterface::ATOM),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            materialityAssessmentId: new MaterialityAssessmentId($payload['materialityAssessmentId']),
            assessmentTopicId: new MaterialityAssessmentTopicId($payload['assessmentTopicId']),
            topicId: new MaterialityTopicId($payload['topicId']),
            values: $payload['values'],
            answeredAt: new \DateTimeImmutable($payload['answeredAt']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
