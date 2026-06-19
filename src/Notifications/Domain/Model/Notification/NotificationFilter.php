<?php

declare(strict_types=1);

namespace App\Notifications\Domain\Model\Notification;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\Filter;

class NotificationFilter
{
    use Filter;

    public function __construct(
        public ?UserId $recipientId = null,
        public ?AccountId $accountId = null,
        public ?NotificationChannel $channel = null,
        public ?NotificationStatus $status = null,
        public bool $onlyUnread = false,
        int $limit = 10,
    ) {
        $this->limit = $limit;
        $this->orderWay = 'DESC';
    }
}
