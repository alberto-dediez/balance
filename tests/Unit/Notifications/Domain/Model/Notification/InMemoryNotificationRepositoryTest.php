<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Domain\Model\Notification;

use App\Notifications\Domain\Model\Notification\NotificationRepository;

class InMemoryNotificationRepositoryTest extends NotificationRepositoryTest
{
    protected function createSUT(): NotificationRepository
    {
        return NotificationRepositoryMother::inMemory();
    }
}
