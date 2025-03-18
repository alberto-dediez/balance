<?php

namespace App\Waste\Infrastructure\Repository\Measurement;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use App\Waste\Domain\Model\Property\Property;
use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\Measurement\MeasurementFilter;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;

#[AsAlias(id: 'repository.measurement', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineMeasurementRepository implements MeasurementRepository
{
    public function __construct(private EntityManagerInterface $manager)
    {
        
    }

    public function add(Measurement $measurement): void
    {
        $this->getEntityManager()->persist($measurement);
        $this->getEntityManager()->flush();
    }

    public function remove(Measurement $measurement): void
    {
        $this->getEntityManager()->remove($measurement);
        $this->getEntityManager()->flush();
    }

    public function find(MeasurementId $measurementId): ?Measurement
    {
        $qb = $this->createQueryBuilderFromFilter(new MeasurementFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('m.id.id', ':id')
            )
            ->setParameter('id', (string) $measurementId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(MeasurementFilter $filter): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('m')
            ->from(Measurement::class, 'm')
            ->leftJoin(Container::class, 'c', 'WITH', 'c.id.id = m.containerId')
        ;

        if (null !== $filter->containerId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('m.containerId', ':container')
                )
                ->setParameter('container', (string) $filter->containerId)
            ;
        }

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
                    $qb->expr()->eq('m.wasteId', ':wasteId')
                )
                ->setParameter('wasteId', (string) $filter->wasteId)
            ;
        }

        if (null !== $filter->purpose) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('m.purpose', ':purpose')
                )
                ->setParameter('purpose', $filter->purpose->value)
            ;
        }

        if (null !== $filter->measurementMethod) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('m.measurementMethod', ':measurementMethod')
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
                $qb->orderBy('m.registeredAt', $way);
                break;
        }

        return $qb;
    }

    public function findByFilter(MeasurementFilter $filter): array
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (array) $qb
            ->getQuery()
            ->setMaxResults($filter->limit)
            ->setFirstResult($filter->page * $filter->limit)
            ->getResult()
        ;
    }

    public function totalResultsByFilter(MeasurementFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return $qb->select('COUNT(DISTINCT m)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getEntityManager()
    {
        return $this->manager;
    }
}