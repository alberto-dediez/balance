<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OperatorContribution;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class OperatorContributionUserLinked extends DomainEvent
{
    protected static string $eventName = 'operatorContributionUserLinked';

    public function __construct(
        private OperatorContributionId $contributionId,
        private UserId $userId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function contributionId(): OperatorContributionId
    {
        return $this->contributionId;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->contributionId();
    }

    public function payload(): array
    {
        return [
            'contributionId' => (string) $this->contributionId,
            'userId' => (string) $this->userId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            contributionId: new OperatorContributionId($payload['contributionId']),
            userId: new UserId($payload['userId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
