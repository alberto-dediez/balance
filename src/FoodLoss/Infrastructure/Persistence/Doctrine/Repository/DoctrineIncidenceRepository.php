<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\Incidence\Incidence;
use App\FoodLoss\Domain\Model\Incidence\IncidenceFilter;
use App\FoodLoss\Domain\Model\Incidence\IncidenceId;
use App\FoodLoss\Domain\Model\Incidence\IncidenceRepository;
use App\Property\Domain\Model\Property\PropertyId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.incidence', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineIncidenceRepository implements IncidenceRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(Incidence $incidence): void
    {
        $this->manager->persist($incidence);
        $this->manager->flush();
    }

    public function remove(Incidence $incidence): void
    {
        $this->manager->remove($incidence);
        $this->manager->flush();
    }

    public function find(IncidenceId $incidenceId): ?Incidence
    {
        $qb = $this->createQueryBuilderFromFilter(new IncidenceFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.id', ':id'),
            )
            ->setParameter('id', (string) $incidenceId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(IncidenceFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(Incidence::class, 'e')
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

        if (null !== $filter->accountId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.accountId', ':accountId'),
                )
                ->setParameter('accountId', (string) $filter->accountId)
            ;
        }

        if ($filter->propertyId instanceof PropertyId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.propertyId', ':propertyId'),
                )
                ->setParameter('propertyId', (string) $filter->propertyId)
            ;
        }

        if ($filter->dateFrom instanceof \DateTimeImmutable) {
            $qb
                ->andWhere(
                    $qb->expr()->gte('e.exceptionOccurredOn', ':dateFrom'),
                )
                ->setParameter('dateFrom', $filter->dateFrom, Types::DATETIME_IMMUTABLE)
            ;
        }

        if ($filter->dateTo instanceof \DateTimeImmutable) {
            $qb
                ->andWhere(
                    $qb->expr()->lte('e.exceptionOccurredOn', ':dateTo'),
                )
                ->setParameter('dateTo', $filter->dateTo, Types::DATETIME_IMMUTABLE)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('e.registeredAt', $way),
            'exceptionOccurredOn' => $qb->orderBy('e.exceptionOccurredOn', $way),
            default => $qb->orderBy('e.name', $way),
        };

        return $qb;
    }

    public function findByFilter(IncidenceFilter $filter): array
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

    public function totalResultsByFilter(IncidenceFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
