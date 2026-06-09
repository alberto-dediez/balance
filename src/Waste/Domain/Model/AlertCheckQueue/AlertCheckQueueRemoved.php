<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\AlertCheckQueue;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Waste\Domain\DomainEvent;

final class AlertCheckQueueRemoved extends DomainEvent
{
    protected static string $eventName = 'alertCheckQueueRemoved';

    public function __construct(
        private readonly AlertCheckQueueId $alertCheckQueueId,
        private readonly AccountId $accountId,
        private readonly \DateTimeImmutable $checkDate,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function alertCheckQueueId(): AlertCheckQueueId
    {
        return $this->alertCheckQueueId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function checkDate(): \DateTimeImmutable
    {
        return $this->checkDate;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->accountId;
    }

    public function payload(): array
    {
        return [
            'alertCheckQueueId' => (string) $this->alertCheckQueueId,
            'accountId' => (string) $this->accountId,
            'checkDate' => $this->checkDate->format('Y-m-d'),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new AlertCheckQueueRemoved(
            alertCheckQueueId: new AlertCheckQueueId($payload['alertCheckQueueId']),
            accountId: new AccountId($payload['accountId']),
            checkDate: new \DateTimeImmutable($payload['checkDate']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
