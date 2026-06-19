<?php

declare(strict_types=1);

namespace App\Notifications\Domain\Model\Notification;

use App\Security\Domain\Model\User\UserId;

interface NotificationRepository
{
    public function add(Notification $notification): void;

    public function remove(Notification $notification): void;

    public function find(NotificationId $notificationId): ?Notification;

    /** @return list<Notification> */
    public function findByFilter(NotificationFilter $filter): array;

    public function totalResultsByFilter(NotificationFilter $filter): int;

    /** @return list<Notification> */
    public function findUnreadByRecipient(UserId $recipientId, int $limit = 5): array;

    public function countUnreadByRecipient(UserId $recipientId): int;
}
