<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class OrganizationalEmployeeParticipationAggregated extends DomainEvent
{
    protected static string $eventName = 'organizationalEmployeeParticipationAggregated';

    public function __construct(
        private OrganizationalEmployeeParticipationId $participationId,
        private int $answeredCount,
        private ?float $marginOfError,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function participationId(): OrganizationalEmployeeParticipationId
    {
        return $this->participationId;
    }

    public function answeredCount(): int
    {
        return $this->answeredCount;
    }

    public function marginOfError(): ?float
    {
        return $this->marginOfError;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->participationId;
    }

    public function payload(): array
    {
        return [
            'participationId' => (string) $this->participationId,
            'answeredCount' => $this->answeredCount,
            'marginOfError' => $this->marginOfError,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            participationId: new OrganizationalEmployeeParticipationId($payload['participationId']),
            answeredCount: (int) $payload['answeredCount'],
            marginOfError: isset($payload['marginOfError']) ? (float) $payload['marginOfError'] : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
