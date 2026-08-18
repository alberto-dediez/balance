<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payee;

use App\Payments\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PayeeUnblocked extends DomainEvent
{
    protected static string $eventName = 'payeeUnblocked';

    public function __construct(
        private PayeeId $payeeId,
        private PayeeStatus $status,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function payeeId(): PayeeId
    {
        return $this->payeeId;
    }

    public function status(): PayeeStatus
    {
        return $this->status;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->payeeId();
    }

    public function payload(): array
    {
        return [
            'payeeId' => (string) $this->payeeId,
            'status' => $this->status->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new PayeeUnblocked(
            payeeId: new PayeeId($payload['payeeId']),
            status: PayeeStatus::from((string) $payload['status']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
