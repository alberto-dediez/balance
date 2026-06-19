<?php

declare(strict_types=1);

namespace App\Notifications\Application\Notification\Unread;

use App\Notifications\Domain\Model\Notification\Notification;
use App\Notifications\Domain\Model\Notification\NotificationRepository;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;

class UnreadNotificationsProvider
{
    public function __construct(
        private readonly NotificationRepository $repository,
        private readonly LoggedUser $loggedUser,
    ) {}

    public function count(): int
    {
        $user = $this->loggedUser->getLoggedUserOrNull();

        if (!$user instanceof User) {
            return 0;
        }

        return $this->repository->countUnreadByRecipient($user->id());
    }

    /**
     * @return list<Notification>
     */
    public function latest(int $limit = 5): array
    {
        $user = $this->loggedUser->getLoggedUserOrNull();

        if (!$user instanceof User) {
            return [];
        }

        return $this->repository->findUnreadByRecipient($user->id(), $limit);
    }
}
