<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Surplus;

use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\Reason\ReasonId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class SurplusTransformationAssessed extends DomainEvent
{
    protected static string $eventName = 'surplusTransformationAssessed';

    public function __construct(
        private readonly SurplusId $surplusId,
        private readonly bool $canBeTransformed,
        private readonly ?ReasonId $reasonId,
        private readonly ?TransformationProduct $product = null,
        private readonly ?TransformationChannel $channel = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function surplusId(): SurplusId
    {
        return $this->surplusId;
    }

    public function canBeTransformed(): bool
    {
        return $this->canBeTransformed;
    }

    public function reasonId(): ?ReasonId
    {
        return $this->reasonId;
    }

    public function product(): ?TransformationProduct
    {
        return $this->product;
    }

    public function channel(): ?TransformationChannel
    {
        return $this->channel;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->surplusId;
    }

    public function payload(): array
    {
        return [
            'surplusId' => (string) $this->surplusId,
            'canBeTransformed' => $this->canBeTransformed,
            'reasonId' => null !== $this->reasonId ? (string) $this->reasonId : null,
            'product' => $this->product?->value,
            'channel' => $this->channel?->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new SurplusTransformationAssessed(
            surplusId: new SurplusId($payload['surplusId']),
            canBeTransformed: (bool) $payload['canBeTransformed'],
            reasonId: isset($payload['reasonId']) ? new ReasonId((string) $payload['reasonId']) : null,
            product: isset($payload['product']) ? TransformationProduct::from($payload['product']) : null,
            channel: isset($payload['channel']) ? TransformationChannel::from($payload['channel']) : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
