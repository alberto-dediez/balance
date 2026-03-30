<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Persistence\Doctrine\Repository;

use App\Property\Domain\Model\Category\Category;
use App\Property\Domain\Model\Category\CategoryFilter;
use App\Property\Domain\Model\Category\CategoryId;
use App\Property\Domain\Model\Category\CategoryRepository;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.category', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineCategoryRepository implements CategoryRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(Category $category): void
    {
        $this->manager->persist($category);
        $this->manager->flush();
    }

    public function remove(Category $category): void
    {
        $this->manager->remove($category);
        $this->manager->flush();
    }

    public function find(CategoryId $categoryId): ?Category
    {
        $qb = $this->createQueryBuilderFromFilter(new CategoryFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('c.id', ':id'),
            )
            ->setParameter('id', (string) $categoryId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByName(string $name): ?Category
    {
        $qb = $this->createQueryBuilderFromFilter(new CategoryFilter());

        return $qb
            ->andWhere(
                $qb->expr()->like('c.name', ':name'),
            )
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findBySourceAndExternalId(ExternalSource $source, ExternalId $externalId): ?Category
    {
        if ($externalId->isEmpty()) {
            return null;
        }

        $qb = $this->createQueryBuilderFromFilter(new CategoryFilter());

        $attribute = $source->attribute();
        $sourceId = $source->id($externalId);

        return $qb
            ->andWhere(
                $qb->expr()->like("c.externalId.{$attribute}", ':sourceId'),
            )
            ->setParameter('sourceId', $sourceId)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(CategoryFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('c')
            ->from(Category::class, 'c')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('c.removed'),
                    $qb->expr()->eq('c.removed', ':removed')
                ),
            )
            ->setParameter('removed', false, Types::BOOLEAN)
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('c.name', ':name'),
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('c.registeredAt', $way),
            default => $qb->orderBy('c.name', $way),
        };

        return $qb;
    }

    /** @return Category[] */
    public function findByFilter(CategoryFilter $filter): array
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

    public function totalResultsByFilter(CategoryFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT c)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
