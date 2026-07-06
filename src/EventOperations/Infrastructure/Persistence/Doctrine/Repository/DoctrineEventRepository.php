<?php

declare(strict_types=1);

namespace App\EventOperations\Infrastructure\Persistence\Doctrine\Repository;

use App\EventOperations\Domain\Model\Event\Event;
use App\EventOperations\Domain\Model\Event\EventFilter;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\EventOperations\Domain\Model\Event\EventSector;
use App\EventOperations\Domain\Model\Event\EventSize;
use App\EventOperations\Domain\Model\Event\EventTypology;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.event', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineEventRepository implements EventRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(Event $event): void
    {
        $this->manager->persist($event);
        $this->manager->flush();
    }

    public function remove(Event $event): void
    {
        $this->manager->remove($event);
        $this->manager->flush();
    }

    public function find(EventId $eventId): ?Event
    {
        $qb = $this->createQueryBuilderFromFilter(new EventFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.id', ':id'),
            )
            ->setParameter('id', (string) $eventId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(EventFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(Event::class, 'e')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('e.removed'),
                    $qb->expr()->eq('e.removed', ':removed'),
                ),
            )
            ->setParameter('removed', false, Types::BOOLEAN)
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('e.name', ':name'),
                )
                ->setParameter('name', "%{$filter->name}%")
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

        if ($filter->location instanceof DestinationId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.location', ':location'),
                )
                ->setParameter('location', $filter->location)
            ;
        }

        if ($filter->propertyId instanceof PropertyId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.propertyId', ':propertyId'),
                )
                ->setParameter('propertyId', $filter->propertyId)
            ;
        }

        if ($filter->companyId instanceof CompanyId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.companyId', ':companyId'),
                )
                ->setParameter('companyId', $filter->companyId)
            ;
        }

        if ($filter->size instanceof EventSize) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.size', ':size'),
                )
                ->setParameter('size', $filter->size)
            ;
        }

        if ($filter->sector instanceof EventSector) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.sector', ':sector'),
                )
                ->setParameter('sector', $filter->sector)
            ;
        }

        if ($filter->typology instanceof EventTypology) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.typology', ':typology'),
                )
                ->setParameter('typology', $filter->typology)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('e.registeredAt', $way),
            default => $qb->orderBy('e.name', $way),
        };

        return $qb;
    }

    /** @return array<Event> */
    public function findByFilter(EventFilter $filter): array
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

    public function totalResultsByFilter(EventFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
