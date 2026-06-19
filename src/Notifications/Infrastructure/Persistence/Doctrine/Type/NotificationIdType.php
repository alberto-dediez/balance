<?php

declare(strict_types=1);

namespace App\Notifications\Infrastructure\Persistence\Doctrine\Type;

use App\Notifications\Domain\Model\Notification\NotificationId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class NotificationIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'notification_id';
    }

    /** @return class-string<AggregateRootId> */
    public function getClassName(): string
    {
        return NotificationId::class;
    }
}
