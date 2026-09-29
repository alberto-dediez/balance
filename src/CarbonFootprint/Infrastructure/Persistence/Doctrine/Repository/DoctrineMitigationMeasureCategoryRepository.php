<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategory;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryFilter;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryId;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryRepository;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryTranslation;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.mitigation_measure_category', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineMitigationMeasureCategoryRepository implements MitigationMeasureCategoryRepository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private EntityManagerInterface $manager) {}

    public function add(MitigationMeasureCategory $mitigationMeasureCategory): void
    {
        $this->manager->persist($mitigationMeasureCategory);
        $this->addTranslations($mitigationMeasureCategory, MitigationMeasureCategoryTranslation::class, $this->manager);
        $this->manager->flush();
    }

    public function remove(MitigationMeasureCategory $mitigationMeasureCategory): void
    {
        $this->manager->remove($mitigationMeasureCategory);
        $this->manager->flush();
    }

    public function find(MitigationMeasureCategoryId $mitigationMeasureCategoryId): ?MitigationMeasureCategory
    {
        $qb = $this->createQueryBuilderFromFilter(new MitigationMeasureCategoryFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('c.id', ':id'),
            )
            ->setParameter('id', (string) $mitigationMeasureCategoryId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(MitigationMeasureCategoryFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('c')
            ->from(MitigationMeasureCategory::class, 'c')
            ->leftJoin(MitigationMeasureCategoryTranslation::class, 'trans', 'WITH', 'trans.translatable = c AND trans.locale = :locale')
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
            default => $qb->orderBy('c.name', $way),
        };

        $qb->addOrderBy('c.id', 'ASC');

        return $qb;
    }

    /** @return array<MitigationMeasureCategory> */
    public function findByFilter(MitigationMeasureCategoryFilter $filter): array
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

    public function totalResultsByFilter(MitigationMeasureCategoryFilter $filter): int
    {
        return (int) $this->createQueryBuilderFromFilter($filter)
            ->select('COUNT(DISTINCT c)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
