<?php

declare(strict_types=1);

namespace App\Bioscore\Infrastructure\Persistence\Doctrine\Repository;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItem;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemFilter;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemId;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemRepository;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreMappingStatus;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.bioscore_catalog_item', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineBioscoreCatalogItemRepository implements BioscoreCatalogItemRepository
{
    public function __construct(private EntityManagerInterface $manager) {}

    public function add(BioscoreCatalogItem $item): void
    {
        $this->manager->persist($item);
        $this->manager->flush();
    }

    public function find(BioscoreCatalogItemId $id): ?BioscoreCatalogItem
    {
        $qb = $this->createQueryBuilderFromFilter(new BioscoreCatalogItemFilter());

        return $qb
            ->andWhere($qb->expr()->eq('i.id', ':id'))
            ->setParameter('id', (string) $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByCatalogAndBioscoreId(BioscoreCatalog $catalog, int $bioscoreId): ?BioscoreCatalogItem
    {
        $qb = $this->createQueryBuilderFromFilter(new BioscoreCatalogItemFilter(catalog: $catalog));

        return $qb
            ->andWhere($qb->expr()->eq('i.bioscoreId', ':bioscoreId'))
            ->setParameter('bioscoreId', $bioscoreId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /** @return array<BioscoreCatalogItem> */
    public function findByFilter(BioscoreCatalogItemFilter $filter): array
    {
        $query = $this->createQueryBuilderFromFilter($filter)->getQuery();

        if ($filter->limit > 0) {
            $query
                ->setMaxResults($filter->limit)
                ->setFirstResult($filter->page * $filter->limit)
            ;
        }

        return $query->getResult();
    }

    public function totalResultsByFilter(BioscoreCatalogItemFilter $filter): int
    {
        return (int) $this->createQueryBuilderFromFilter($filter)
            ->select('COUNT(DISTINCT i)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function countPending(BioscoreCatalog $catalog): int
    {
        return $this->totalResultsByFilter(new BioscoreCatalogItemFilter(
            catalog: $catalog,
            status: BioscoreMappingStatus::Unmapped,
            withdrawn: false,
        ));
    }

    private function createQueryBuilderFromFilter(BioscoreCatalogItemFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('i')->from(BioscoreCatalogItem::class, 'i');

        if (null !== $filter->catalog) {
            $qb
                ->andWhere($qb->expr()->eq('i.catalog', ':catalog'))
                ->setParameter('catalog', $filter->catalog->value)
            ;
        }

        if (null !== $filter->status) {
            $qb
                ->andWhere($qb->expr()->eq('i.status', ':status'))
                ->setParameter('status', $filter->status->value)
            ;
        }

        if (null !== $filter->withdrawn) {
            $qb
                ->andWhere($qb->expr()->eq('i.withdrawn', ':withdrawn'))
                ->setParameter('withdrawn', $filter->withdrawn)
            ;
        }

        if (null !== $filter->name && '' !== $filter->name) {
            $qb
                ->andWhere($qb->expr()->like('i.name', ':name'))
                ->setParameter('name', '%' . $filter->name . '%')
            ;
        }

        if (null !== $filter->targetId && '' !== $filter->targetId) {
            $qb
                ->andWhere($qb->expr()->eq('i.targetId', ':targetId'))
                ->setParameter('targetId', $filter->targetId)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'status' => $qb->orderBy('i.status', $way),
            'lastSeenAt' => $qb->orderBy('i.lastSeenAt', $way),
            'firstSeenAt' => $qb->orderBy('i.firstSeenAt', $way),
            default => $qb->orderBy('i.name', $way),
        };

        $qb->addOrderBy('i.id', 'ASC');

        return $qb;
    }
}
