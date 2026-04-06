<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPoint;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointFilter;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.criticalPoint', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineCriticalPointRepository implements CriticalPointRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(CriticalPoint $criticalPoint): void
    {
        $this->manager->persist($criticalPoint);
        $this->manager->flush();
    }

    public function remove(CriticalPoint $criticalPoint): void
    {
        $this->manager->remove($criticalPoint);
        $this->manager->flush();
    }

    public function find(CriticalPointId $criticalPointId): ?CriticalPoint
    {
        $qb = $this->createQueryBuilderFromFilter(new CriticalPointFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.id', ':id'),
            )
            ->setParameter('id', (string) $criticalPointId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(CriticalPointFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(CriticalPoint::class, 'e')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('e.removed'),
                    $qb->expr()->eq('e.removed', ':removed'),
                ),
            )
            ->setParameter('removed', false, Types::BOOLEAN)
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('e.name', ':name'),
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        if ($filter->accountId instanceof AccountId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.accountId', ':accountId'),
                )
                ->setParameter('accountId', $filter->accountId)
            ;
        }

        if (!empty($filter->propertyId)) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.propertyId', ':propertyId'),
                )
                ->setParameter('propertyId', $filter->propertyId)
            ;
        }

        if (null !== $filter->serviceCategoryId) {
            $qb
                ->join('e.serviceCategories', 'sc')
                ->andWhere(
                    $qb->expr()->eq('sc.serviceCategoryId', ':serviceCategoryId'),
                )
                ->setParameter('serviceCategoryId', (string) $filter->serviceCategoryId)
            ;
        }

        if (null !== $filter->actionId) {
            $qb->join('e.actions', 'a')
               ->andWhere('a.actionId = :actionId')
               ->setParameter('actionId', (string) $filter->actionId);
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('e.registeredAt', $way),
            default => $qb->orderBy('e.name', $way),
        };

        return $qb;
    }

    /** @return array<CriticalPoint> */
    public function findByFilter(CriticalPointFilter $filter): array
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

    public function totalResultsByFilter(CriticalPointFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
