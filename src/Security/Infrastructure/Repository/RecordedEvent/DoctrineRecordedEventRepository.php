<?php

namespace App\Security\Infrastructure\Repository\RecordedEvent;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Security\Domain\Model\RecordedEvent\RecordedEvent;
use App\Security\Domain\Model\RecordedEvent\RecordedEventId;
use App\Security\Domain\Model\RecordedEvent\RecordedEventFilter;
use App\Security\Domain\Model\RecordedEvent\RecordedEventRepository;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

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

    public function add(RecordedEvent $account): void
    {
        $this->getEntityManager()->persist($account);
        $this->getEntityManager()->flush();
    }

    public function remove(RecordedEvent $account): void
    {
        $this->getEntityManager()->remove($account);
        $this->getEntityManager()->flush();
    }

    public function find(RecordedEventId $accountId): ?RecordedEvent
    {
        $qb = $this->createQueryBuilderFromFilter(new RecordedEventFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.id', ':id')
            )
            ->setParameter('id', $accountId)
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