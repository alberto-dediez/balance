<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityAssessment;

use App\Materiality\Domain\DomainEvent;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MaterialityAssessmentCompleted extends DomainEvent
{
    protected static string $eventName = 'materialityAssessmentCompleted';

    /** @param list<array{assessmentTopicId: string, topicId: string, topicNumber: int|null, points: int, minPoints: int, maxPoints: int, highestMagnitudeLevel: int|null}> $scores */
    public function __construct(
        private MaterialityAssessmentId $materialityAssessmentId,
        private MaterialityAssessmentSubjectType $subjectType,
        private string $carbonFootprintId,
        private AccountId $accountId,
        private int $year,
        private UserId $completedBy,
        private \DateTimeImmutable $completedAt,
        private array $scores,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function materialityAssessmentId(): MaterialityAssessmentId
    {
        return $this->materialityAssessmentId;
    }

    public function subjectType(): MaterialityAssessmentSubjectType
    {
        return $this->subjectType;
    }

    public function carbonFootprintId(): string
    {
        return $this->carbonFootprintId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function year(): int
    {
        return $this->year;
    }

    public function completedBy(): UserId
    {
        return $this->completedBy;
    }

    public function completedAt(): \DateTimeImmutable
    {
        return $this->completedAt;
    }

    /** @return list<array{assessmentTopicId: string, topicId: string, topicNumber: int|null, points: int, minPoints: int, maxPoints: int, highestMagnitudeLevel: int|null}> */
    public function scores(): array
    {
        return $this->scores;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->materialityAssessmentId;
    }

    public function payload(): array
    {
        return [
            'materialityAssessmentId' => (string) $this->materialityAssessmentId,
            'subjectType' => $this->subjectType->value,
            'carbonFootprintId' => $this->carbonFootprintId,
            'accountId' => (string) $this->accountId,
            'year' => $this->year,
            'completedBy' => (string) $this->completedBy,
            'completedAt' => $this->completedAt->format(\DateTimeInterface::ATOM),
            'scores' => $this->scores,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            materialityAssessmentId: new MaterialityAssessmentId($payload['materialityAssessmentId']),
            subjectType: MaterialityAssessmentSubjectType::from($payload['subjectType']),
            carbonFootprintId: $payload['carbonFootprintId'],
            accountId: new AccountId($payload['accountId']),
            year: $payload['year'],
            completedBy: new UserId($payload['completedBy']),
            completedAt: new \DateTimeImmutable($payload['completedAt']),
            scores: $payload['scores'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
