<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGas;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasFilter;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.refrigerant_gas', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineRefrigerantGasRepository implements RefrigerantGasRepository
{
    public function __construct(private EntityManagerInterface $manager) {}

    public function add(RefrigerantGas $refrigerantGas): void
    {
        $this->manager->persist($refrigerantGas);
        $this->manager->flush();
    }

    public function remove(RefrigerantGas $refrigerantGas): void
    {
        $this->manager->remove($refrigerantGas);
        $this->manager->flush();
    }

    public function find(RefrigerantGasId $refrigerantGasId): ?RefrigerantGas
    {
        $qb = $this->createQueryBuilderFromFilter(new RefrigerantGasFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.id', ':id'),
            )
            ->setParameter('id', (string) $refrigerantGasId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(RefrigerantGasFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(RefrigerantGas::class, 'e')
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

        $qb->addOrderBy('e.id', 'ASC');

        return $qb;
    }

    /** @return array<RefrigerantGas> */
    public function findByFilter(RefrigerantGasFilter $filter): array
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

    public function totalResultsByFilter(RefrigerantGasFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
