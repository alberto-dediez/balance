<?php

namespace App\Security\Infrastructure\Persistence\Doctrine\Repository;

use App\Security\Domain\Model\RecordedEvent\RecordedEvent;
use App\Security\Domain\Model\RecordedEvent\RecordedEventFilter;
use App\Security\Domain\Model\RecordedEvent\RecordedEventId;
use App\Security\Domain\Model\RecordedEvent\RecordedEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.recordedEvents', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineRecordedEventRepository implements RecordedEventRepository
{
    private EntityRepository $repository;

    public function __construct(private EntityManagerInterface $manager)
    {
        $this->repository = $manager->getRepository(RecordedEvent::class);
    }

    public function add(RecordedEvent $event): void
    {
        $this->getEntityManager()->persist($event);
        $this->getEntityManager()->flush();
    }

    public function remove(RecordedEvent $event): void
    {
        $this->getEntityManager()->remove($event);
        $this->getEntityManager()->flush();
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
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('e')
            ->from(RecordedEvent::class, 'e')
        ;

        if (!empty($filter->eventName)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('e.eventName', ':eventName')
                )
                ->setParameter('eventName', "%{$filter->eventName}%")
            ;
        }

        if (!empty($filter->context)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('e.context', ':context')
                )
                ->setParameter('context', "%{$filter->context}%")
            ;
        }

        if (null !== $filter->userId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.userId', ':userId')
                )
                ->setParameter('userId', $filter->userId)
            ;
        }

        if (!empty($filter->aggregateRootId)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('e.aggregateRootId', ':aggregateRootId')
                )
                ->setParameter('aggregateRootId', "%{$filter->aggregateRootId}%")
            ;
        }

        $way = $filter->orderWay == 'ASC' ? 'ASC' : 'DESC';

        switch ($filter->orderBy) {
            case 'context':
                $qb->orderBy('e.context', $way);
                break;
            case 'eventName':
                $qb->orderBy('e.eventName', $way);
                break;
            case 'occurredOn':
            default:
                $qb->orderBy('e.occurredOn', $way);
                break;
        }

        return $qb;
    }

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

        return $qb->select('COUNT(DISTINCT e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getEntityManager()
    {
        return $this->manager;
    }
}
