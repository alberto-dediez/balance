<?php

namespace App\Property\Infrastructure\Repository\Category;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use App\Property\Domain\Model\Category\Category;
use App\Property\Domain\Model\Category\CategoryId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Category\CategoryFilter;
use App\Property\Domain\Model\Property\ExternalSource;
use Symfony\Component\DependencyInjection\Attribute\When;
use App\Property\Domain\Model\Category\CategoryRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(id: 'repository.category', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineCategoryRepository implements CategoryRepository
{
    public function __construct(private EntityManagerInterface $manager)
    {
        
    }

    public function add(Category $category): void
    {
        $this->getEntityManager()->persist($category);
        $this->getEntityManager()->flush();
    }

    public function remove(Category $category): void
    {
        $this->getEntityManager()->remove($category);
        $this->getEntityManager()->flush();
    }

    public function find(CategoryId $categoryId): ?Category
    {
        $qb = $this->createQueryBuilderFromFilter(new CategoryFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('c.id.id', ':id')
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
                $qb->expr()->like('c.name', ':name')
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
                $qb->expr()->like("c.externalId.{$attribute}", ":sourceId")
            )
            ->setParameter('sourceId', $sourceId)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(CategoryFilter $filter): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('c')
            ->from(Category::class, 'c')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('c.removed'),
                    $qb->expr()->eq('c.removed', $qb->expr()->literal(false))
                )
            )
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('c.name', ':name')
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        $way = $filter->orderWay == 'ASC' ? 'ASC' : 'DESC';

        switch ($filter->orderBy) {
            case 'registeredAt':
                $qb->orderBy('c.registeredAt', $way);
                break;
            case 'name':
            default:
                $qb->orderBy('c.name', $way);    
                break;
        }

        return $qb;
    }

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

        return $qb->select('COUNT(DISTINCT c)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getEntityManager()
    {
        return $this->manager;
    }
}