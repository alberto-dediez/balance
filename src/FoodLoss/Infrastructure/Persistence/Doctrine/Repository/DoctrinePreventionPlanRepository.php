<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanFilter;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.prevention_plan', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrinePreventionPlanRepository implements PreventionPlanRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(PreventionPlan $preventionPlan): void
    {
        $this->manager->persist($preventionPlan);
        $this->manager->flush();
    }

    public function remove(PreventionPlan $preventionPlan): void
    {
        $this->manager->remove($preventionPlan);
        $this->manager->flush();
    }

    public function find(PreventionPlanId $preventionPlanId): ?PreventionPlan
    {
        $qb = $this->createQueryBuilderFromFilter(new PreventionPlanFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.id', ':id'),
            )
            ->setParameter('id', (string) $preventionPlanId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(PreventionPlanFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(PreventionPlan::class, 'e')
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

        if ($filter->companyId instanceof CompanyId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.companyId', ':companyId'),
                )
                ->setParameter('companyId', (string) $filter->companyId)
            ;
        }

        if ($filter->accountId instanceof AccountId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.accountId', ':accountId'),
                )
                ->setParameter('accountId', (string) $filter->accountId)
            ;
        }

        if ($filter->propertyId instanceof PropertyId) {
            $qb
                ->leftJoin('e.properties', 'p')
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->eq('p.propertyId', ':propertyId'),
                        $qb->expr()->isNull('p.propertyId'),
                    ),
                )
                ->setParameter('propertyId', (string) $filter->propertyId)
            ;
        }

        if ($filter->criticalPointId instanceof CriticalPointId) {
            $qb
                ->join('e.versions', 'v')
                ->join('v.proposedActions', 'pa')
                ->andWhere($qb->expr()->eq('pa.criticalPointId', ':criticalPointId'))
                ->setParameter('criticalPointId', (string) $filter->criticalPointId)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('e.registeredAt', $way),
            'updatedAt' => $qb->orderBy('e.updatedAt', $way),
            'name' => $qb->orderBy('e.name', $way),
            default => $qb->orderBy('e.name', $way),
        };

        return $qb;
    }

    /** @return array<PreventionPlan> */
    public function findByFilter(PreventionPlanFilter $filter): array
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

    public function totalResultsByFilter(PreventionPlanFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
