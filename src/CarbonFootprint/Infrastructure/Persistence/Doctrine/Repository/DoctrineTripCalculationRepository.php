<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculation;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationFilter;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationId;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepository;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.trip_calculation', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineTripCalculationRepository implements TripCalculationRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(TripCalculation $tripCalculation): void
    {
        $this->manager->persist($tripCalculation);
        $this->manager->flush();
    }

    public function find(TripCalculationId $tripCalculationId): ?TripCalculation
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('t')
            ->from(TripCalculation::class, 't')
            ->where($qb->expr()->eq('t.id', ':id'))
            ->setParameter('id', $tripCalculationId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<TripCalculation> */
    public function findByFilter(TripCalculationFilter $filter): array
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        $query = $qb->getQuery();

        if ($filter->limit > 0) {
            $query
                ->setMaxResults($filter->limit)
                ->setFirstResult($filter->page * $filter->limit)
            ;
        }

        return $query->getResult();
    }

    public function totalResultsByFilter(TripCalculationFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT t)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /** @return list<TripCalculation> */
    public function findByLinkId(TripCalculationLinkId $tripCalculationLinkId): array
    {
        return $this->findByFilter(new TripCalculationFilter(tripCalculationLinkId: $tripCalculationLinkId, limit: 0));
    }

    private function createQueryBuilderFromFilter(TripCalculationFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('t', 'l')
            ->from(TripCalculation::class, 't')
            ->leftJoin('t.legs', 'l')
        ;

        if (null !== $filter->tripCalculationLinkId) {
            $qb
                ->andWhere($qb->expr()->eq('t.tripCalculationLinkId', ':tripCalculationLinkId'))
                ->setParameter('tripCalculationLinkId', (string) $filter->tripCalculationLinkId)
            ;
        }

        if (null !== $filter->from) {
            $qb
                ->andWhere($qb->expr()->gte('t.registeredAt', ':from'))
                ->setParameter('from', $filter->from)
            ;
        }

        if (null !== $filter->to) {
            $qb
                ->andWhere($qb->expr()->lte('t.registeredAt', ':to'))
                ->setParameter('to', $filter->to)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        $qb
            ->orderBy('t.registeredAt', $way)
            ->addOrderBy('t.id', 'ASC')
            ->addOrderBy('l.position', 'ASC')
        ;

        return $qb;
    }
}
