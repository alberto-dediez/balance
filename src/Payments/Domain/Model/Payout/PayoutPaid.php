<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payout;

use App\Library\Domain\Model\Media\MediaId;
use App\Payments\Domain\DomainEvent;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PayoutPaid extends DomainEvent
{
    protected static string $eventName = 'payoutPaid';

    public function __construct(
        private PayoutId $payoutId,
        private string $reference,
        private ?MediaId $evidence,
        private UserId $resolvedBy,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function payoutId(): PayoutId
    {
        return $this->payoutId;
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function evidence(): ?MediaId
    {
        return $this->evidence;
    }

    public function resolvedBy(): UserId
    {
        return $this->resolvedBy;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->payoutId();
    }

    public function payload(): array
    {
        return [
            'payoutId' => (string) $this->payoutId,
            'reference' => $this->reference,
            'evidence' => null !== $this->evidence ? (string) $this->evidence : null,
            'resolvedBy' => (string) $this->resolvedBy,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new PayoutPaid(
            payoutId: new PayoutId($payload['payoutId']),
            reference: (string) $payload['reference'],
            evidence: null !== $payload['evidence'] ? new MediaId($payload['evidence']) : null,
            resolvedBy: new UserId($payload['resolvedBy']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
