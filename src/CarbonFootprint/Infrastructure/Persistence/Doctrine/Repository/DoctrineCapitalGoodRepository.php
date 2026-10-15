<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGood;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodFilter;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepository;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodTranslation;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.capital_good', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineCapitalGoodRepository implements CapitalGoodRepository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private EntityManagerInterface $manager) {}

    public function add(CapitalGood $capitalGood): void
    {
        $this->manager->persist($capitalGood);
        $this->addTranslations($capitalGood, CapitalGoodTranslation::class, $this->manager);
        $this->manager->flush();
    }

    public function remove(CapitalGood $capitalGood): void
    {
        $this->manager->remove($capitalGood);
        $this->manager->flush();
    }

    public function find(CapitalGoodId $capitalGoodId): ?CapitalGood
    {
        $qb = $this->createQueryBuilderFromFilter(new CapitalGoodFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('cg.id', ':id'),
            )
            ->setParameter('id', (string) $capitalGoodId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(CapitalGoodFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('cg')
            ->from(CapitalGood::class, 'cg')
            ->leftJoin(CapitalGoodTranslation::class, 'trans', 'WITH', 'trans.translatable = cg AND trans.locale = :locale')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('cg.removed'),
                    $qb->expr()->eq('cg.removed', $qb->expr()->literal(false)),
                ),
            )
            ->setParameter('locale', LocaleGetter::locale())
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->like('cg.name', ':name'),
                            $qb->expr()->isNull('trans.name'),
                        ),
                        $qb->expr()->like('trans.name', ':name'),
                    ),
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        if (null !== $filter->category) {
            $qb
                ->andWhere($qb->expr()->eq('cg.category', ':category'))
                ->setParameter('category', $filter->category->value)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('cg.registeredAt', $way),
            'updatedAt' => $qb->orderBy('cg.updatedAt', $way),
            default => $qb->orderBy('cg.name', $way),
        };

        $qb->addOrderBy('cg.id', 'ASC');

        return $qb;
    }

    /** @return array<CapitalGood> */
    public function findByFilter(CapitalGoodFilter $filter): array
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        $query = $qb->getQuery();

        if ($filter->limit > 0) {
            $query
                ->setMaxResults($filter->limit)
                ->setFirstResult($filter->page * $filter->limit)
            ;
        }

        return $query->getResult();
    }

    public function totalResultsByFilter(CapitalGoodFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT cg)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->manager;
    }
}
