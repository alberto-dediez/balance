<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\AlertCheckQueue;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;

class AlertCheckQueue
{
    use AggregateRoot;

    public function __construct(
        private AlertCheckQueueId $id,
        private AccountId $accountId,
        private \DateTimeImmutable $checkDate = new \DateTimeImmutable(),
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
    ) {}

    public function id(): AlertCheckQueueId
    {
        return $this->id;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function checkDate(): \DateTimeImmutable
    {
        return $this->checkDate;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function register(AccountId $accountId, \DateTimeImmutable $checkDate): void
    {
        $this->recordApplyAndPublish(
            new AlertCheckQueueRegistered(
                alertCheckQueueId: $this->id,
                accountId: $accountId,
                checkDate: $checkDate,
            ),
        );
    }

    public function remove(): void
    {
        assert(null !== $this->accountId);

        $this->recordApplyAndPublish(
            new AlertCheckQueueRemoved(
                alertCheckQueueId: $this->id,
                accountId: $this->accountId,
                checkDate: $this->checkDate,
            ),
        );
    }

    public function applyAlertCheckQueueRegistered(AlertCheckQueueRegistered $event): void
    {
        $this->accountId = $event->accountId();
        $this->checkDate = $event->checkDate();
    }

    public function applyAlertCheckQueueRemoved(AlertCheckQueueRemoved $event): void {}
}
