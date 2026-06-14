<?php

declare(strict_types=1);

namespace App\Notifications\Infrastructure\Persistence\Doctrine\Repository;

use App\Notifications\Domain\Model\Notification\Notification;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Notifications\Domain\Model\Notification\NotificationFilter;
use App\Notifications\Domain\Model\Notification\NotificationId;
use App\Notifications\Domain\Model\Notification\NotificationRepository;
use App\Security\Domain\Model\User\UserId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.notification', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineNotificationRepository implements NotificationRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(Notification $notification): void
    {
        $this->manager->persist($notification);
        $this->manager->flush();
    }

    public function remove(Notification $notification): void
    {
        $this->manager->remove($notification);
        $this->manager->flush();
    }

    public function find(NotificationId $notificationId): ?Notification
    {
        $qb = $this->createQueryBuilderFromFilter(new NotificationFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('n.id', ':id'),
            )
            ->setParameter('id', (string) $notificationId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(NotificationFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('n')
            ->from(Notification::class, 'n')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('n.removed'),
                    $qb->expr()->eq('n.removed', ':removed'),
                ),
            )
            ->setParameter('removed', false, Types::BOOLEAN)
        ;

        if (null !== $filter->recipientId) {
            $qb
                ->andWhere($qb->expr()->eq('n.recipientId', ':recipientId'))
                ->setParameter('recipientId', (string) $filter->recipientId)
            ;
        }

        if (null !== $filter->accountId) {
            $qb
                ->andWhere($qb->expr()->eq('n.accountId', ':accountId'))
                ->setParameter('accountId', (string) $filter->accountId)
            ;
        }

        if (null !== $filter->channel) {
            $qb
                ->andWhere($qb->expr()->eq('n.channel', ':channel'))
                ->setParameter('channel', $filter->channel->value)
            ;
        }

        if (null !== $filter->status) {
            $qb
                ->andWhere($qb->expr()->eq('n.status', ':status'))
                ->setParameter('status', $filter->status->value)
            ;
        }

        if ($filter->onlyUnread) {
            $qb->andWhere($qb->expr()->isNull('n.readAt'));
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'readAt' => $qb->orderBy('n.readAt', $way),
            default => $qb->orderBy('n.createdAt', $way),
        };

        return $qb;
    }

    /** @return list<Notification> */
    public function findByFilter(NotificationFilter $filter): array
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        $query = $qb->getQuery();

        if ($filter->limit > 0) {
            $query
                ->setMaxResults($filter->limit)
                ->setFirstResult($filter->page * $filter->limit)
            ;
        }

        return (array) $query->getResult();
    }

    public function totalResultsByFilter(NotificationFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT n)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /** @return list<Notification> */
    public function findUnreadByRecipient(UserId $recipientId, int $limit = 5): array
    {
        $filter = new NotificationFilter(
            recipientId: $recipientId,
            channel: NotificationChannel::Internal,
            onlyUnread: true,
            limit: $limit,
        );
        $filter->orderWay = 'DESC';

        return $this->findByFilter($filter);
    }

    public function countUnreadByRecipient(UserId $recipientId): int
    {
        $filter = new NotificationFilter(
            recipientId: $recipientId,
            channel: NotificationChannel::Internal,
            onlyUnread: true,
        );

        return $this->totalResultsByFilter($filter);
    }
}
