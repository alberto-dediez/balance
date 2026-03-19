<?php

namespace App\Waste\Infrastructure\Persistence\Doctrine\Repository;

use App\Property\Domain\Model\Property\PropertyId;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Container\ContainerFilter;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\ContainerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.container', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineContainerRepository implements ContainerRepository
{
    public function __construct(private readonly EntityManagerInterface $manager)
    {

    }

    public function add(Container $container): void
    {
        $this->manager->persist($container);
        $this->manager->flush();
    }

    public function remove(Container $container): void
    {
        $this->manager->remove($container);
        $this->manager->flush();
    }

    public function find(ContainerId $containerId): ?Container
    {
        $qb = $this->createQueryBuilderFromFilter(new ContainerFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('c.id', ':id')
            )
            ->setParameter('id', (string) $containerId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(ContainerFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('c')
            ->from(Container::class, 'c')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('c.removed'),
                    $qb->expr()->eq('c.removed', $qb->expr()->literal(false))
                )
            )
        ;

        if ($filter->propertyId instanceof PropertyId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('c.propertyId', ':property')
                )
                ->setParameter('property', $filter->propertyId)
            ;
        }

        if ($filter->wasteId instanceof WasteId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('c.wasteId', ':wasteId')
                )
                ->setParameter('wasteId', $filter->wasteId)
            ;
        }

        if ($filter->purpose instanceof Purpose) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('c.purpose', ':purpose')
                )
                ->setParameter('purpose', $filter->purpose->value)
            ;
        }

        if ($filter->measurementMethod instanceof MeasurementMethod) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('c.measurementMethod', ':measurementMethod')
                )
                ->setParameter('measurementMethod', $filter->measurementMethod->value)
            ;
        }

        if ($filter->wasteManagerId instanceof WasteManagerId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('c.wasteManagerId', ':wasteManagerId')
                )
                ->setParameter('wasteManagerId', $filter->wasteManagerId)
            ;
        }

        if ($filter->accountId instanceof AccountId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('c.accountId', ':accountId')
                )
                ->setParameter('accountId', $filter->accountId)
            ;
        }

        $way = $filter->orderWay === 'ASC' ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            default => $qb->orderBy('c.registeredAt', $way),
        };

        return $qb;
    }

    public function findByFilter(ContainerFilter $filter): array
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

    public function totalResultsByFilter(ContainerFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return $qb->select('COUNT(DISTINCT c)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
