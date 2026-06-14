<?php

declare(strict_types=1);

namespace App\Notifications\Infrastructure\Symfony\Form;

use App\Notifications\Domain\Model\Notification\AudienceType;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;

class SendNotificationForm
{
    /**
     * @param NotificationChannel[] $channels
     */
    public function __construct(
        public AudienceType $audienceType = AudienceType::AllPlatform,
        public ?UserId $targetUser = null,
        public ?Account $targetAccount = null,
        public ?Role $targetRole = null,
        public array $channels = [],
        public string $subject = '',
        public string $body = '',
    ) {}
}
