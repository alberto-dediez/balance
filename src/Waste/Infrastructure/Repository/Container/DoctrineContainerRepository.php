<?php

namespace App\Waste\Infrastructure\Repository\Container;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use App\Waste\Domain\Model\Property\Property;
use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\ContainerFilter;
use App\Waste\Domain\Model\Container\ContainerRepository;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(id: 'repository.container', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineContainerRepository implements ContainerRepository
{
    public function __construct(private EntityManagerInterface $manager)
    {
        
    }

    public function add(Container $container): void
    {
        $this->getEntityManager()->persist($container);
        $this->getEntityManager()->flush();
    }

    public function remove(Container $container): void
    {
        $this->getEntityManager()->remove($container);
        $this->getEntityManager()->flush();
    }

    public function find(ContainerId $containerId): ?Container
    {
        $qb = $this->createQueryBuilderFromFilter(new ContainerFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('c.id.id', ':id')
            )
            ->setParameter('id', (string) $containerId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(ContainerFilter $filter): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('c')
            ->from(Container::class, 'c')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('c.removed'),
                    $qb->expr()->eq('c.removed', $qb->expr()->literal(false))
                )
            )
        ;

        if (null !== $filter->propertyId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('c.propertyId', ':property')
                )
                ->setParameter('property', (string) $filter->propertyId)
            ;
        }

        if (null !== $filter->wasteId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('c.wasteId', ':wasteId')
                )
                ->setParameter('wasteId', (string) $filter->wasteId)
            ;
        }

        if (null !== $filter->purpose) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('c.purpose', ':purpose')
                )
                ->setParameter('purpose', $filter->purpose->value)
            ;
        }

        if (null !== $filter->measurementMethod) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('c.measurementMethod', ':measurementMethod')
                )
                ->setParameter('measurementMethod', $filter->measurementMethod->value)
            ;
        }

        if (null !== $filter->account) {
            $qb
                ->leftJoin(Property::class, 'p', 'WITH', 'p.id.id = c.propertyId')
                ->andWhere(
                    $qb->expr()->eq('p.accountId', ':accountId')
                )
                ->setParameter('accountId', (string) $filter->account->id())
            ;
        }

        $way = $filter->orderWay == 'ASC' ? 'ASC' : 'DESC';

        switch ($filter->orderBy) {
            case 'registeredAt':
            default:
                $qb->orderBy('c.registeredAt', $way);
                break;
        }

        return $qb;
    }

    public function findByFilter(ContainerFilter $filter): array
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (array) $qb
            ->getQuery()
            ->setMaxResults($filter->limit)
            ->setFirstResult($filter->page * $filter->limit)
            ->getResult()
        ;
    }

    public function totalResultsByFilter(ContainerFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return $qb->select('COUNT(DISTINCT c)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getEntityManager()
    {
        return $this->manager;
    }
}