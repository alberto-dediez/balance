<?php

declare(strict_types=1);

namespace App\Notifications\Application\Notification\Read;

use App\Notifications\Domain\Model\Notification\NotificationRepository;
use App\Security\Domain\Model\User\User;

class MarkAllNotificationsAsReadService
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
    ) {}

    public function __invoke(User $user): void
    {
        $notifications = $this->notificationRepository->findUnreadByRecipient($user->id(), 0);

        foreach ($notifications as $notification) {
            $notification->markAsRead();
            $this->notificationRepository->add($notification);
        }
    }
}
