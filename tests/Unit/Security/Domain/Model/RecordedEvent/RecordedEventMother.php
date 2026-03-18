<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\RecordedEvent;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRemoved;
use App\Security\Domain\Model\RecordedEvent\RecordedEvent;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRemoved;
use App\Shared\Domain\Event\DomainEvent;

class RecordedEventMother
{
    public static function from(UserId $userId, DomainEvent $event): RecordedEvent
    {
        return new RecordedEvent(
            userId: $userId,
            event: $event,
        );
    }

    public static function fromUser(UserId $userId): RecordedEvent
    {
        return self::from(
            userId: $userId,
            event: new AccountRemoved(
                AccountId::generate(),
            ),
        );
    }

    public static function random(): RecordedEvent
    {
        return self::from(
            userId: UserId::generate(),
            event: new UserRemoved(
                userId: UserId::generate(),
            ),
        );
    }
}
