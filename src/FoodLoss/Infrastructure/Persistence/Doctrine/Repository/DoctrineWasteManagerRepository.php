<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\WasteManager\WasteManager;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerFilter;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerId;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.waste_manager', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineWasteManagerRepository implements WasteManagerRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(WasteManager $wasteManager): void
    {
        $this->manager->persist($wasteManager);
        $this->manager->flush();
    }

    public function remove(WasteManager $wasteManager): void
    {
        $this->manager->remove($wasteManager);
        $this->manager->flush();
    }

    public function find(WasteManagerId $wasteManagerId): ?WasteManager
    {
        $qb = $this->createQueryBuilderFromFilter(new WasteManagerFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.id', ':id'),
            )
            ->setParameter('id', (string) $wasteManagerId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(WasteManagerFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(WasteManager::class, 'e')
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

        if (null !== $filter->accountId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.accountId', ':accountId'),
                )
                ->setParameter('accountId', (string) $filter->accountId)
            ;
        }

        if (null !== $filter->animalFeedAuthorized) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.animalFeedAuthorized', ':animalFeedAuthorized'),
                )
                ->setParameter('animalFeedAuthorized', $filter->animalFeedAuthorized, Types::BOOLEAN)
            ;
        }

        if (null !== $filter->compostAuthorized) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.compostAuthorized', ':compostAuthorized'),
                )
                ->setParameter('compostAuthorized', $filter->compostAuthorized, Types::BOOLEAN)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('e.registeredAt', $way),
            default => $qb->orderBy('e.name', $way),
        };

        return $qb;
    }

    /** @return array<WasteManager> */
    public function findByFilter(WasteManagerFilter $filter): array
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

    public function totalResultsByFilter(WasteManagerFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
