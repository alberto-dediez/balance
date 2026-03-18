<?php

namespace App\Security\Infrastructure\Persistence\Doctrine\Repository;

use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\RecordedEvent\RecordedEvent;
use App\Security\Domain\Model\RecordedEvent\RecordedEventFilter;
use App\Security\Domain\Model\RecordedEvent\RecordedEventId;
use App\Security\Domain\Model\RecordedEvent\RecordedEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.recordedEvents', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineRecordedEventRepository implements RecordedEventRepository
{
    public function __construct(private readonly EntityManagerInterface $manager)
    {

    }

    public function add(RecordedEvent $event): void
    {
        $this->manager->persist($event);
        $this->manager->flush();
    }

    public function remove(RecordedEvent $event): void
    {
        $this->manager->remove($event);
        $this->manager->flush();
    }

    public function find(RecordedEventId $eventId): ?RecordedEvent
    {
        $qb = $this->createQueryBuilderFromFilter(new RecordedEventFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.id', ':id')
            )
            ->setParameter('id', $eventId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(RecordedEventFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(RecordedEvent::class, 'e')
        ;

        if (!in_array($filter->eventName, [null, '', '0'], true)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('e.eventName', ':eventName')
                )
                ->setParameter('eventName', "%{$filter->eventName}%")
            ;
        }

        if (!in_array($filter->context, [null, '', '0'], true)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('e.context', ':context')
                )
                ->setParameter('context', "%{$filter->context}%")
            ;
        }

        if ($filter->userId instanceof UserId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.userId', ':userId')
                )
                ->setParameter('userId', $filter->userId)
            ;
        }

        if (!in_array($filter->aggregateRootId, [null, '', '0'], true)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('e.aggregateRootId', ':aggregateRootId')
                )
                ->setParameter('aggregateRootId', "%{$filter->aggregateRootId}%")
            ;
        }

        $way = $filter->orderWay === 'ASC' ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'context' => $qb->orderBy('e.context', $way),
            'eventName' => $qb->orderBy('e.eventName', $way),
            default => $qb->orderBy('e.occurredOn', $way),
        };

        return $qb;
    }

    /**
     * @return list<RecordedEvent>
     */
    public function findByFilter(RecordedEventFilter $filter): array
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

    public function totalResultsByFilter(RecordedEventFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        $count = $qb->select('COUNT(DISTINCT e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return intval($count);
    }
}
