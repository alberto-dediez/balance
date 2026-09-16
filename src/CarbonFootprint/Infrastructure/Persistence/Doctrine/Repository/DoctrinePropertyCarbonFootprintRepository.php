<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintActivity;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintFilter;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.property_carbon_footprint', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrinePropertyCarbonFootprintRepository implements PropertyCarbonFootprintRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(PropertyCarbonFootprint $propertyCarbonFootprint): void
    {
        $this->manager->persist($propertyCarbonFootprint);
        $this->manager->flush();
    }

    public function remove(PropertyCarbonFootprint $propertyCarbonFootprint): void
    {
        $this->manager->remove($propertyCarbonFootprint);
        $this->manager->flush();
    }

    public function find(PropertyCarbonFootprintId $propertyCarbonFootprintId): ?PropertyCarbonFootprint
    {
        $qb = $this->createQueryBuilderFromFilter(new PropertyCarbonFootprintFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.id', ':id'),
            )
            ->setParameter('id', (string) $propertyCarbonFootprintId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByPropertyIdAndYear(PropertyId $propertyId, int $year): ?PropertyCarbonFootprint
    {
        $qb = $this->createQueryBuilderFromFilter(new PropertyCarbonFootprintFilter());

        return $qb
            ->andWhere($qb->expr()->eq('e.propertyId', ':propertyId'))
            ->andWhere($qb->expr()->eq('e.year', ':year'))
            ->setParameter('propertyId', (string) $propertyId)
            ->setParameter('year', $year)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /** @return array<PropertyCarbonFootprint> */
    public function findByPropertyId(PropertyId $propertyId): array
    {
        $qb = $this->createQueryBuilderFromFilter(new PropertyCarbonFootprintFilter());

        return (array) $qb
            ->andWhere($qb->expr()->eq('e.propertyId', ':propertyId'))
            ->setParameter('propertyId', (string) $propertyId)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByActivityId(ActivityId $activityId): ?PropertyCarbonFootprint
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('e')
            ->from(PropertyCarbonFootprint::class, 'e')
            ->join(PropertyCarbonFootprintActivity::class, 'a', 'WITH', 'a.propertyCarbonFootprint = e')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('e.removed'),
                    $qb->expr()->eq('e.removed', ':removed'),
                ),
            )
            ->andWhere($qb->expr()->eq('a.activityId', ':activityId'))
            ->setParameter('removed', false, Types::BOOLEAN)
            ->setParameter('activityId', (string) $activityId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(PropertyCarbonFootprintFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(PropertyCarbonFootprint::class, 'e')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('e.removed'),
                    $qb->expr()->eq('e.removed', ':removed'),
                ),
            )
            ->setParameter('removed', false, Types::BOOLEAN)
        ;

        if ($filter->propertyId instanceof PropertyId) {
            $qb
                ->andWhere($qb->expr()->eq('e.propertyId', ':filterPropertyId'))
                ->setParameter('filterPropertyId', $filter->propertyId)
            ;
        }

        if (null !== $filter->year) {
            $qb
                ->andWhere($qb->expr()->eq('e.year', ':filterYear'))
                ->setParameter('filterYear', $filter->year)
            ;
        }

        if ($filter->status instanceof OrganizationalCarbonFootprintStatus) {
            $qb
                ->andWhere($qb->expr()->eq('e.status', ':filterStatus'))
                ->setParameter('filterStatus', $filter->status->value)
            ;
        }

        if ($filter->accountId instanceof AccountId) {
            $qb
                ->andWhere($qb->expr()->eq('e.accountId', ':accountId'))
                ->setParameter('accountId', $filter->accountId)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'year' => $qb->orderBy('e.year', $way),
            default => $qb->orderBy('e.registeredAt', $way),
        };

        $qb->addOrderBy('e.id', 'ASC');

        return $qb;
    }

    /** @return array<PropertyCarbonFootprint> */
    public function findByFilter(PropertyCarbonFootprintFilter $filter): array
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

    public function totalResultsByFilter(PropertyCarbonFootprintFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
