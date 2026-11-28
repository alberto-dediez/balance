<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Contest;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ContestResolved extends DomainEvent
{
    protected static string $eventName = 'contestResolved';

    public function __construct(
        private readonly ContestId $contestId,
        private readonly ?UserId $drawnBy,
        private readonly ?int $drawCandidates,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function contestId(): ContestId
    {
        return $this->contestId;
    }

    public function drawnBy(): ?UserId
    {
        return $this->drawnBy;
    }

    public function drawCandidates(): ?int
    {
        return $this->drawCandidates;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->contestId;
    }

    public function payload(): array
    {
        return [
            'contestId' => (string) $this->contestId,
            'drawnBy' => $this->drawnBy instanceof UserId ? (string) $this->drawnBy : null,
            'drawCandidates' => $this->drawCandidates,
        ];
    }

    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        return new self(
            contestId: new ContestId($payload['contestId']),
            drawnBy: null !== $payload['drawnBy'] ? new UserId($payload['drawnBy']) : null,
            drawCandidates: $payload['drawCandidates'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
