<?php

declare(strict_types=1);

namespace App\Notifications\Application\Notification\Send;

use App\Notifications\Domain\Model\Notification\AudienceType;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\User\Role;
use App\Shared\Application\CommandBus\Command;

class SendNotificationRequest implements Command
{
    /**
     * @param NotificationChannel[] $channels
     */
    public function __construct(
        public AudienceType $audienceType = AudienceType::AllPlatform,
        public ?string $targetUserId = null,
        public ?string $targetAccountId = null,
        public ?Role $targetRole = null,
        public array $channels = [],
        public string $subject = '',
        public string $body = '',
    ) {}
}
