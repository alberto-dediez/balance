<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintActivity;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintFilter;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.event_carbon_footprint', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineEventCarbonFootprintRepository implements EventCarbonFootprintRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(EventCarbonFootprint $eventCarbonFootprint): void
    {
        $this->manager->persist($eventCarbonFootprint);
        $this->manager->flush();
    }

    public function remove(EventCarbonFootprint $eventCarbonFootprint): void
    {
        $this->manager->remove($eventCarbonFootprint);
        $this->manager->flush();
    }

    public function find(EventCarbonFootprintId $eventCarbonFootprintId): ?EventCarbonFootprint
    {
        $qb = $this->createQueryBuilderFromFilter(new EventCarbonFootprintFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.id', ':id'),
            )
            ->setParameter('id', (string) $eventCarbonFootprintId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByEventId(EventId $eventId): ?EventCarbonFootprint
    {
        $qb = $this->createQueryBuilderFromFilter(new EventCarbonFootprintFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.eventId', ':eventId'),
            )
            ->setParameter('eventId', (string) $eventId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByActivityId(ActivityId $activityId): ?EventCarbonFootprint
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('e')
            ->from(EventCarbonFootprint::class, 'e')
            ->join(EventCarbonFootprintActivity::class, 'a', 'WITH', 'a.eventCarbonFootprint = e')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('e.removed'),
                    $qb->expr()->eq('e.removed', ':removed'),
                ),
            )
            ->andWhere($qb->expr()->eq('a.activityId', ':activityId'))
            ->setParameter('removed', false, Types::BOOLEAN)
            ->setParameter('activityId', (string) $activityId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(EventCarbonFootprintFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(EventCarbonFootprint::class, 'e')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('e.removed'),
                    $qb->expr()->eq('e.removed', ':removed'),
                ),
            )
            ->setParameter('removed', false, Types::BOOLEAN)
        ;

        if ($filter->eventId instanceof EventId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.eventId', ':eventId'),
                )
                ->setParameter('eventId', $filter->eventId)
            ;
        }

        if ($filter->accountId instanceof AccountId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.accountId', ':accountId'),
                )
                ->setParameter('accountId', $filter->accountId)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'updatedAt' => $qb->orderBy('e.updatedAt', $way),
            default => $qb->orderBy('e.registeredAt', $way),
        };

        return $qb;
    }

    /** @return array<EventCarbonFootprint> */
    public function findByFilter(EventCarbonFootprintFilter $filter): array
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

    public function totalResultsByFilter(EventCarbonFootprintFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
