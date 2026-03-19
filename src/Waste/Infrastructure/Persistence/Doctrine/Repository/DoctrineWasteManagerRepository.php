<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Persistence\Doctrine\Repository;

use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\WasteManager\WasteManager;
use App\Waste\Domain\Model\WasteManager\WasteManagerFilter;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use App\Waste\Domain\Model\WasteManager\WasteManagerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.wasteManager', public: true)]
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
                $qb->expr()->eq('wm.id', ':id'),
            )
            ->setParameter('id', (string) $wasteManagerId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(WasteManagerFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('wm')
            ->from(WasteManager::class, 'wm')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('wm.removed'),
                    $qb->expr()->eq('wm.removed', $qb->expr()->literal(false)),
                ),
            )
        ;

        if (null !== $filter->name) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('wm.name', ':name'),
                )
                ->setParameter('name', $filter->name)
            ;
        }

        if (null !== $filter->isPublic) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('wm.isPublic', ':isPublic'),
                )
                ->setParameter('isPublic', $filter->isPublic)
            ;
        }

        if ($filter->accountId instanceof AccountId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('wm.accountId', ':accountId'),
                )
                ->setParameter('accountId', $filter->accountId)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            default => $qb->orderBy('wm.registeredAt', $way),
        };

        return $qb;
    }

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

        return $qb->select('COUNT(DISTINCT wm)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
