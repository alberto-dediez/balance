<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Surplus;

use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\Action\ActionId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class SurplusPreventionAssessed extends DomainEvent
{
    protected static string $eventName = 'surplusPreventionAssessed';

    public function __construct(
        private readonly SurplusId $surplusId,
        private readonly bool $canBePrevented,
        private readonly ?ActionId $actionId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function surplusId(): SurplusId
    {
        return $this->surplusId;
    }

    public function canBePrevented(): bool
    {
        return $this->canBePrevented;
    }

    public function actionId(): ?ActionId
    {
        return $this->actionId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->surplusId;
    }

    public function payload(): array
    {
        return [
            'surplusId' => (string) $this->surplusId,
            'canBePrevented' => $this->canBePrevented,
            'actionId' => null !== $this->actionId ? (string) $this->actionId : null,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new SurplusPreventionAssessed(
            surplusId: new SurplusId($payload['surplusId']),
            canBePrevented: (bool) $payload['canBePrevented'],
            actionId: isset($payload['actionId']) ? new ActionId((string) $payload['actionId']) : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
