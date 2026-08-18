<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payee;

use App\Payments\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PayeeBlocked extends DomainEvent
{
    protected static string $eventName = 'payeeBlocked';

    public function __construct(
        private PayeeId $payeeId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function payeeId(): PayeeId
    {
        return $this->payeeId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->payeeId();
    }

    public function payload(): array
    {
        return ['payeeId' => (string) $this->payeeId];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new PayeeBlocked(
            payeeId: new PayeeId($payload['payeeId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
