<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Domain\Model\AlertCheckQueue;

use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueue;
use App\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueueId;

class AlertCheckQueueMother
{
    public static function pendingEntry(): AlertCheckQueue
    {
        return self::from(
            accountId: new AccountId('account-id-test'),
            checkDate: new \DateTimeImmutable('2024-01-15'),
        );
    }

    public static function from(
        ?AlertCheckQueueId $id = null,
        ?AccountId $accountId = null,
        ?\DateTimeImmutable $checkDate = null,
    ): AlertCheckQueue {
        $entry = new AlertCheckQueue(id: $id ?? AlertCheckQueueId::generate(), accountId: AccountId::generate());
        $entry->register(
            accountId: $accountId ?? new AccountId('account-id-test'),
            checkDate: $checkDate ?? new \DateTimeImmutable('2024-01-15'),
        );

        return $entry;
    }

    public static function forAccountAndDate(AccountId $accountId, \DateTimeImmutable $checkDate): AlertCheckQueue
    {
        return self::from(accountId: $accountId, checkDate: $checkDate);
    }

    public static function random(): AlertCheckQueue
    {
        return self::from(
            accountId: AccountId::generate(),
            checkDate: new \DateTimeImmutable(sprintf('-%d days', random_int(1, 365))),
        );
    }
}
