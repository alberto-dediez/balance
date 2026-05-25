<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Surplus;

use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\Reason\ReasonId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class SurplusHumanDonationAssessed extends DomainEvent
{
    protected static string $eventName = 'surplusHumanDonationAssessed';

    public function __construct(
        private readonly SurplusId $surplusId,
        private readonly bool $canBeHumanDonated,
        private readonly ?ReasonId $reasonId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function surplusId(): SurplusId
    {
        return $this->surplusId;
    }

    public function canBeHumanDonated(): bool
    {
        return $this->canBeHumanDonated;
    }

    public function reasonId(): ?ReasonId
    {
        return $this->reasonId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->surplusId;
    }

    public function payload(): array
    {
        return [
            'surplusId' => (string) $this->surplusId,
            'canBeHumanDonated' => $this->canBeHumanDonated,
            'reasonId' => null !== $this->reasonId ? (string) $this->reasonId : null,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new SurplusHumanDonationAssessed(
            surplusId: new SurplusId($payload['surplusId']),
            canBeHumanDonated: (bool) $payload['canBeHumanDonated'],
            reasonId: isset($payload['reasonId']) ? new ReasonId((string) $payload['reasonId']) : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
