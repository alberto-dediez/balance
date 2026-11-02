<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityAssessment;

use App\Materiality\Domain\DomainEvent;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MaterialityAssessmentStarted extends DomainEvent
{
    protected static string $eventName = 'materialityAssessmentStarted';

    /**
     * @param list<MaterialityTopicSnapshot>    $topics
     * @param list<MaterialityQuestionSnapshot> $questions
     * @param array<string, int>                $initialValues
     */
    public function __construct(
        private MaterialityAssessmentId $materialityAssessmentId,
        private MaterialityAssessmentSubjectType $subjectType,
        private string $carbonFootprintId,
        private AccountId $accountId,
        private int $year,
        private ?MaterialitySectorId $sectorId,
        private ?string $sectorName,
        private array $topics,
        private array $questions,
        private array $initialValues = [],
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

    public function sectorId(): ?MaterialitySectorId
    {
        return $this->sectorId;
    }

    public function sectorName(): ?string
    {
        return $this->sectorName;
    }

    /** @return list<MaterialityTopicSnapshot> */
    public function topics(): array
    {
        return $this->topics;
    }

    /** @return list<MaterialityQuestionSnapshot> */
    public function questions(): array
    {
        return $this->questions;
    }

    /** @return array<string, int> */
    public function initialValues(): array
    {
        return $this->initialValues;
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
            'sectorId' => null !== $this->sectorId ? (string) $this->sectorId : null,
            'sectorName' => $this->sectorName,
            'topics' => array_map(static fn(MaterialityTopicSnapshot $topic) => $topic->toPrimitives(), $this->topics),
            'questions' => array_map(static fn(MaterialityQuestionSnapshot $question) => $question->toPrimitives(), $this->questions),
            'initialValues' => $this->initialValues,
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
            sectorId: null !== $payload['sectorId'] ? new MaterialitySectorId($payload['sectorId']) : null,
            sectorName: $payload['sectorName'],
            topics: array_values(array_map(static fn(array $topic) => MaterialityTopicSnapshot::fromPrimitives($topic), $payload['topics'])),
            questions: array_values(array_map(static fn(array $question) => MaterialityQuestionSnapshot::fromPrimitives($question), $payload['questions'])),
            initialValues: $payload['initialValues'] ?? [],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
