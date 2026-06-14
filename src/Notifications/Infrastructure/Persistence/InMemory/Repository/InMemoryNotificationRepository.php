<?php

declare(strict_types=1);

namespace App\Notifications\Infrastructure\Persistence\InMemory\Repository;

use App\Notifications\Domain\Model\Notification\Notification;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Notifications\Domain\Model\Notification\NotificationFilter;
use App\Notifications\Domain\Model\Notification\NotificationId;
use App\Notifications\Domain\Model\Notification\NotificationRepository;
use App\Security\Domain\Model\User\UserId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryNotificationRepository implements NotificationRepository
{
    /** @param array<string, Notification> $notifications */
    public function __construct(private array $notifications = []) {}

    public function add(Notification $notification): void
    {
        $this->notifications[(string) $notification->id()] = $notification;
    }

    public function remove(Notification $notification): void
    {
        unset($this->notifications[(string) $notification->id()]);
    }

    public function find(NotificationId $notificationId): ?Notification
    {
        $notification = $this->notifications[(string) $notificationId] ?? null;

        if (!$notification instanceof Notification || $notification->isRemoved()) {
            return null;
        }

        return $notification;
    }

    /** @return list<Notification> */
    public function findByFilter(NotificationFilter $filter): array
    {
        $result = $this->applyFilter($filter);

        if ($filter->limit > 0) {
            $result = array_slice($result, $filter->page * $filter->limit, $filter->limit);
        }

        return $result;
    }

    public function totalResultsByFilter(NotificationFilter $filter): int
    {
        return count($this->applyFilter($filter));
    }

    /** @return list<Notification> */
    public function findUnreadByRecipient(UserId $recipientId, int $limit = 5): array
    {
        return $this->findByFilter(new NotificationFilter(
            recipientId: $recipientId,
            channel: NotificationChannel::Internal,
            onlyUnread: true,
            limit: $limit,
        ));
    }

    public function countUnreadByRecipient(UserId $recipientId): int
    {
        return $this->totalResultsByFilter(new NotificationFilter(
            recipientId: $recipientId,
            channel: NotificationChannel::Internal,
            onlyUnread: true,
        ));
    }

    /**
     * @return list<Notification>
     */
    private function applyFilter(NotificationFilter $filter): array
    {
        $result = array_filter($this->notifications, function (Notification $notification) use ($filter): bool {
            if ($notification->isRemoved()) {
                return false;
            }

            if (null !== $filter->recipientId
                && (null === $notification->recipientId() || !$notification->recipientId()->equals($filter->recipientId))) {
                return false;
            }

            if (null !== $filter->accountId
                && (null === $notification->accountId() || !$notification->accountId()->equals($filter->accountId))) {
                return false;
            }

            if (null !== $filter->channel && $notification->channel() !== $filter->channel) {
                return false;
            }

            if (null !== $filter->status && $notification->status() !== $filter->status) {
                return false;
            }

            if ($filter->onlyUnread && $notification->isRead()) {
                return false;
            }

            return true;
        });

        $result = array_values($result);

        usort($result, fn(Notification $a, Notification $b): int => $b->createdAt() <=> $a->createdAt());

        return $result;
    }
}
