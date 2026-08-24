<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkFilter;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\Property\Domain\Model\Property\PropertyId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.trip_calculation_link', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineTripCalculationLinkRepository implements TripCalculationLinkRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(TripCalculationLink $tripCalculationLink): void
    {
        $this->manager->persist($tripCalculationLink);
        $this->manager->flush();
    }

    public function find(TripCalculationLinkId $tripCalculationLinkId): ?TripCalculationLink
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('t')
            ->from(TripCalculationLink::class, 't')
            ->where($qb->expr()->eq('t.id', ':id'))
            ->setParameter('id', $tripCalculationLinkId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByPropertyId(PropertyId $propertyId): ?TripCalculationLink
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('t')
            ->from(TripCalculationLink::class, 't')
            ->where($qb->expr()->eq('t.propertyId', ':propertyId'))
            ->setParameter('propertyId', (string) $propertyId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<TripCalculationLink> */
    public function findByFilter(TripCalculationLinkFilter $filter): array
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

    public function totalResultsByFilter(TripCalculationLinkFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(t.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function findByToken(#[\SensitiveParameter] string $token): ?TripCalculationLink
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('t')
            ->from(TripCalculationLink::class, 't')
            ->where($qb->expr()->eq('t.token', ':token'))
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(TripCalculationLinkFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb
            ->select('t')
            ->from(TripCalculationLink::class, 't')
            ->orderBy('t.registeredAt', 'DESC')
        ;

        if (null !== $filter->accountId) {
            $qb
                ->andWhere($qb->expr()->eq('t.accountId', ':accountId'))
                ->setParameter('accountId', (string) $filter->accountId)
            ;
        }

        if (null !== $filter->propertyId) {
            $qb
                ->andWhere($qb->expr()->eq('t.propertyId', ':propertyId'))
                ->setParameter('propertyId', (string) $filter->propertyId)
            ;
        }

        return $qb;
    }
}
