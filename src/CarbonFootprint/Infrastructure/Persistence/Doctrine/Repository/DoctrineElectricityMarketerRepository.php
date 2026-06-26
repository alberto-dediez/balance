<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketer;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerFilter;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.electricity_marketer', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineElectricityMarketerRepository implements ElectricityMarketerRepository
{
    public function __construct(private EntityManagerInterface $manager) {}

    public function add(ElectricityMarketer $electricityMarketer): void
    {
        $this->manager->persist($electricityMarketer);
        $this->manager->flush();
    }

    public function remove(ElectricityMarketer $electricityMarketer): void
    {
        $this->manager->remove($electricityMarketer);
        $this->manager->flush();
    }

    public function find(ElectricityMarketerId $electricityMarketerId): ?ElectricityMarketer
    {
        $qb = $this->createQueryBuilderFromFilter(new ElectricityMarketerFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.id', ':id'),
            )
            ->setParameter('id', (string) $electricityMarketerId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneByName(string $name): ?ElectricityMarketer
    {
        $qb = $this->createQueryBuilderFromFilter(new ElectricityMarketerFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.name', ':name'),
            )
            ->setParameter('name', $name)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(ElectricityMarketerFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(ElectricityMarketer::class, 'e')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('e.removed'),
                    $qb->expr()->eq('e.removed', $qb->expr()->literal(false)),
                ),
            )
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('e.name', ':name'),
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('e.registeredAt', $way),
            'updatedAt' => $qb->orderBy('e.updatedAt', $way),
            default => $qb->orderBy('e.name', $way),
        };

        return $qb;
    }

    /** @return array<ElectricityMarketer> */
    public function findByFilter(ElectricityMarketerFilter $filter): array
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

    public function totalResultsByFilter(ElectricityMarketerFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
