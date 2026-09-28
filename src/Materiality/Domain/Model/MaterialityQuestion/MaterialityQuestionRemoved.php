<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityQuestion;

use App\Materiality\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MaterialityQuestionRemoved extends DomainEvent
{
    protected static string $eventName = 'materialityQuestionRemoved';

    public function __construct(
        private MaterialityQuestionId $materialityQuestionId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function materialityQuestionId(): MaterialityQuestionId
    {
        return $this->materialityQuestionId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->materialityQuestionId();
    }

    public function payload(): array
    {
        return [
            'materialityQuestionId' => (string) $this->materialityQuestionId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new MaterialityQuestionRemoved(
            materialityQuestionId: new MaterialityQuestionId($payload['materialityQuestionId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
