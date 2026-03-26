<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Persistence\Doctrine\Repository;

use App\Property\Domain\Model\Brand\Brand;
use App\Property\Domain\Model\Brand\BrandFilter;
use App\Property\Domain\Model\Brand\BrandId;
use App\Property\Domain\Model\Brand\BrandRepository;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.brand', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineBrandRepository implements BrandRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(Brand $brand): void
    {
        $this->manager->persist($brand);
        $this->manager->flush();
    }

    public function remove(Brand $brand): void
    {
        $this->manager->remove($brand);
        $this->manager->flush();
    }

    public function find(BrandId $brandId): ?Brand
    {
        $qb = $this->createQueryBuilderFromFilter(new BrandFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('b.id', ':id'),
            )
            ->setParameter('id', (string) $brandId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByName(string $name): ?Brand
    {
        $qb = $this->createQueryBuilderFromFilter(new BrandFilter());

        return $qb
            ->andWhere(
                $qb->expr()->like('b.name', ':name'),
            )
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findBySourceAndExternalId(ExternalSource $source, ExternalId $externalId): ?Brand
    {
        if ($externalId->isEmpty()) {
            return null;
        }

        $qb = $this->createQueryBuilderFromFilter(new BrandFilter());

        $attribute = $source->attribute();
        $sourceId = $source->id($externalId);

        return $qb
            ->andWhere(
                $qb->expr()->like("b.externalId.{$attribute}", ':sourceId'),
            )
            ->setParameter('sourceId', $sourceId)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(BrandFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('b')
            ->from(Brand::class, 'b')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('b.removed'),
                    $qb->expr()->eq('b.removed', $qb->expr()->literal(false)),
                ),
            )
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('b.name', ':name'),
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('b.registeredAt', $way),
            default => $qb->orderBy('b.name', $way),
        };

        return $qb;
    }

    /** @return Brand[] */
    public function findByFilter(BrandFilter $filter): array
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

    public function totalResultsByFilter(BrandFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT b)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
