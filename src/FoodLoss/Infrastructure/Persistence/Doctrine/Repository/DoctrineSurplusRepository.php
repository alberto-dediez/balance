<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\Surplus\Surplus;
use App\FoodLoss\Domain\Model\Surplus\SurplusDestination;
use App\FoodLoss\Domain\Model\Surplus\SurplusFilter;
use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.surplus', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineSurplusRepository implements SurplusRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(Surplus $surplus): void
    {
        $this->manager->persist($surplus);
        $this->manager->flush();
    }

    public function remove(Surplus $surplus): void
    {
        $this->manager->remove($surplus);
        $this->manager->flush();
    }

    public function find(SurplusId $surplusId): ?Surplus
    {
        $qb = $this->createQueryBuilderFromFilter(new SurplusFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.id', ':id'),
            )
            ->setParameter('id', (string) $surplusId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(SurplusFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(Surplus::class, 'e')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('e.removed'),
                    $qb->expr()->eq('e.removed', ':removed'),
                ),
            )
            ->setParameter('removed', false, Types::BOOLEAN)
        ;

        if ($filter->accountId instanceof AccountId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.accountId', ':accountId'),
                )
                ->setParameter('accountId', (string) $filter->accountId)
            ;
        }

        if ($filter->dateFrom instanceof \DateTimeImmutable) {
            $qb
                ->andWhere(
                    $qb->expr()->gte('e.date', ':dateFrom'),
                )
                ->setParameter('dateFrom', $filter->dateFrom, Types::DATETIME_IMMUTABLE)
            ;
        }

        if ($filter->dateTo instanceof \DateTimeImmutable) {
            $qb
                ->andWhere(
                    $qb->expr()->lte('e.date', ':dateTo'),
                )
                ->setParameter('dateTo', $filter->dateTo, Types::DATETIME_IMMUTABLE)
            ;
        }

        if (null !== $filter->foodType) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.foodType', ':foodType'),
                )
                ->setParameter('foodType', $filter->foodType->value)
            ;
        }

        if ($filter->destination instanceof SurplusDestination) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.destination', ':destination'),
                )
                ->setParameter('destination', $filter->destination->value)
            ;
        }

        if ($filter->serviceCategoryId instanceof ServiceCategoryId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.serviceCategoryId', ':serviceCategoryId'),
                )
                ->setParameter('serviceCategoryId', (string) $filter->serviceCategoryId)
            ;
        }

        if ($filter->criticalPointId instanceof CriticalPointId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.criticalPointId', ':criticalPointId'),
                )
                ->setParameter('criticalPointId', (string) $filter->criticalPointId)
            ;
        }

        if ($filter->surplusCauseId instanceof SurplusCauseId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.surplusCauseId', ':surplusCauseId'),
                )
                ->setParameter('surplusCauseId', (string) $filter->surplusCauseId)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'date' => $qb->orderBy('e.date', $way),
            'registeredAt' => $qb->orderBy('e.registeredAt', $way),
            default => $qb->orderBy('e.date', $way),
        };

        return $qb;
    }

    /** @return array<Surplus> */
    public function findByFilter(SurplusFilter $filter): array
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

    public function totalResultsByFilter(SurplusFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
