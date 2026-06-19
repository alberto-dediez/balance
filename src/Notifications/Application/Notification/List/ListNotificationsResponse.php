<?php

declare(strict_types=1);

namespace App\Notifications\Application\Notification\List;

use App\Notifications\Domain\Model\Notification\Notification;

class ListNotificationsResponse
{
    /** @param array<Notification> $notifications */
    public function __construct(public array $notifications = [], public int $totalResults = 0) {}
}
