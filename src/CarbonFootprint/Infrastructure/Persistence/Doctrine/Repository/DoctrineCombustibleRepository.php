<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\Combustible\Combustible;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleFilter;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleTranslation;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.combustible', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineCombustibleRepository implements CombustibleRepository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private EntityManagerInterface $manager) {}

    public function add(Combustible $combustible): void
    {
        $this->manager->persist($combustible);
        $this->addTranslations($combustible, CombustibleTranslation::class, $this->manager);
        $this->manager->flush();
    }

    public function remove(Combustible $combustible): void
    {
        $this->manager->remove($combustible);
        $this->manager->flush();
    }

    public function find(CombustibleId $combustibleId): ?Combustible
    {
        $qb = $this->createQueryBuilderFromFilter(new CombustibleFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('c.id', ':id'),
            )
            ->setParameter('id', (string) $combustibleId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(CombustibleFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('c')
            ->from(Combustible::class, 'c')
            ->leftJoin(CombustibleTranslation::class, 'trans', 'WITH', 'trans.translatable = c AND trans.locale = :locale')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('c.removed'),
                    $qb->expr()->eq('c.removed', $qb->expr()->literal(false)),
                ),
            )
            ->setParameter('locale', LocaleGetter::locale())
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->like('c.name', ':name'),
                            $qb->expr()->isNull('trans.name'),
                        ),
                        $qb->expr()->like('trans.name', ':name'),
                    ),
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('c.registeredAt', $way),
            'updatedAt' => $qb->orderBy('c.updatedAt', $way),
            default => $qb->orderBy('c.name', $way),
        };

        return $qb;
    }

    /** @return array<Combustible> */
    public function findByFilter(CombustibleFilter $filter): array
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

    public function totalResultsByFilter(CombustibleFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT c)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->manager;
    }
}
