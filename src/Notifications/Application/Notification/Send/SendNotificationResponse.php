<?php

declare(strict_types=1);

namespace App\Notifications\Application\Notification\Send;

use App\Shared\Application\CommandBus\CommandResponse;

class SendNotificationResponse implements CommandResponse
{
    public function __construct(public int $recipientCount = 0) {}
}
