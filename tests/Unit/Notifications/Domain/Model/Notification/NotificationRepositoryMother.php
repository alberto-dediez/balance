<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Domain\Model\Notification;

use App\Notifications\Infrastructure\Persistence\InMemory\Repository\InMemoryNotificationRepository;

class NotificationRepositoryMother
{
    public static function inMemory(): InMemoryNotificationRepository
    {
        return new InMemoryNotificationRepository();
    }
}
