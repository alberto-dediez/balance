<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\AlertCheckQueue;

use App\Security\Domain\Model\Account\AccountId;

interface AlertCheckQueueRepository
{
    public function add(AlertCheckQueue $entry): void;

    public function find(AlertCheckQueueId $id): ?AlertCheckQueue;

    public function existsFor(AccountId $accountId, \DateTimeImmutable $checkDate): bool;

    /** @return array<AlertCheckQueue> */
    public function findAll(): array;

    public function remove(AlertCheckQueue $entry): void;
}
