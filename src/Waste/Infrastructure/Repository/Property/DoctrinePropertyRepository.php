<?php

namespace App\Waste\Infrastructure\Repository\Property;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use App\Waste\Domain\Model\Property\Property;
use App\Waste\Domain\Model\Property\PropertyId;
use App\Waste\Domain\Model\Property\PropertyFilter;
use App\Waste\Domain\Model\Property\PropertyRepository;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(id: 'repository.property', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrinePropertyRepository implements PropertyRepository
{
    public function __construct(private EntityManagerInterface $manager)
    {
        
    }

    public function add(Property $property): void
    {
        $this->getEntityManager()->persist($property);
        $this->getEntityManager()->flush();
    }

    public function remove(Property $property): void
    {
        $this->getEntityManager()->remove($property);
        $this->getEntityManager()->flush();
    }

    public function find(PropertyId $propertyId): ?Property
    {
        $qb = $this->createQueryBuilderFromFilter(new PropertyFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('p.id.id', ':id')
            )
            ->setParameter('id', (string) $propertyId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByName(string $name): ?Property
    {
        $qb = $this->createQueryBuilderFromFilter(new PropertyFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('p.name', ':name')
            )
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(PropertyFilter $filter): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('p')
            ->from(Property::class, 'p')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('p.removed'),
                    $qb->expr()->eq('p.removed', $qb->expr()->literal(false))
                )
            )
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('p.name', ':name')
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        if (null !== $filter->accountId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('p.accountId', ':accountId')
                )
                ->setParameter('accountId', (string) $filter->accountId)
            ;
        }

        $way = $filter->orderWay == 'ASC' ? 'ASC' : 'DESC';

        switch ($filter->orderBy) {
            case 'registeredAt':
                $qb->orderBy('p.registeredAt', $way);
                break;
            case 'name':
            default:
                $qb->orderBy('p.name', $way);        
                break;
        }

        return $qb;
    }

    public function findByFilter(PropertyFilter $filter): array
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (array) $qb
            ->getQuery()
            ->setMaxResults($filter->limit)
            ->setFirstResult($filter->page * $filter->limit)
            ->getResult()
        ;
    }

    public function totalResultsByFilter(PropertyFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return $qb->select('COUNT(DISTINCT p)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getEntityManager()
    {
        return $this->manager;
    }
}