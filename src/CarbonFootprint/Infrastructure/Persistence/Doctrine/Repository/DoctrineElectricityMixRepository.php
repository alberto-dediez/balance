<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMix;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixFilter;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepository;
use App\Property\Domain\Model\Country\CountryId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.electricity_mix', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineElectricityMixRepository implements ElectricityMixRepository
{
    public function __construct(private EntityManagerInterface $manager) {}

    public function add(ElectricityMix $electricityMix): void
    {
        $this->manager->persist($electricityMix);
        $this->manager->flush();
    }

    public function remove(ElectricityMix $electricityMix): void
    {
        $this->manager->remove($electricityMix);
        $this->manager->flush();
    }

    public function find(ElectricityMixId $electricityMixId): ?ElectricityMix
    {
        $qb = $this->createQueryBuilderFromFilter(new ElectricityMixFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.id', ':id'),
            )
            ->setParameter('id', (string) $electricityMixId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByCountryId(CountryId $countryId): ?ElectricityMix
    {
        $qb = $this->createQueryBuilderFromFilter(new ElectricityMixFilter(countryId: $countryId));

        return $qb
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(ElectricityMixFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(ElectricityMix::class, 'e')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('e.removed'),
                    $qb->expr()->eq('e.removed', $qb->expr()->literal(false)),
                ),
            )
        ;

        if (null !== $filter->countryId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.countryId', ':countryId'),
                )
                ->setParameter('countryId', (string) $filter->countryId)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'updatedAt' => $qb->orderBy('e.updatedAt', $way),
            default => $qb->orderBy('e.registeredAt', $way),
        };

        $qb->addOrderBy('e.id', 'ASC');

        return $qb;
    }

    /** @return array<ElectricityMix> */
    public function findByFilter(ElectricityMixFilter $filter): array
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

    public function totalResultsByFilter(ElectricityMixFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
