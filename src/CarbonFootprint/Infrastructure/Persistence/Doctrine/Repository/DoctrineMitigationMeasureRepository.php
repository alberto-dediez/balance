<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasure;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureFilter;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureId;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureRepository;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureTranslation;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryId;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.mitigation_measure', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineMitigationMeasureRepository implements MitigationMeasureRepository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private EntityManagerInterface $manager) {}

    public function add(MitigationMeasure $mitigationMeasure): void
    {
        $this->manager->persist($mitigationMeasure);
        $this->addTranslations($mitigationMeasure, MitigationMeasureTranslation::class, $this->manager);
        $this->manager->flush();
    }

    public function remove(MitigationMeasure $mitigationMeasure): void
    {
        $this->manager->remove($mitigationMeasure);
        $this->manager->flush();
    }

    public function find(MitigationMeasureId $mitigationMeasureId): ?MitigationMeasure
    {
        $qb = $this->createQueryBuilderFromFilter(new MitigationMeasureFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('m.id', ':id'),
            )
            ->setParameter('id', (string) $mitigationMeasureId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(MitigationMeasureFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('m')
            ->from(MitigationMeasure::class, 'm')
            ->leftJoin(MitigationMeasureTranslation::class, 'trans', 'WITH', 'trans.translatable = m AND trans.locale = :locale')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('m.removed'),
                    $qb->expr()->eq('m.removed', $qb->expr()->literal(false)),
                ),
            )
            ->setParameter('locale', LocaleGetter::locale())
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->like('m.name', ':name'),
                            $qb->expr()->isNull('trans.name'),
                        ),
                        $qb->expr()->like('trans.name', ':name'),
                    ),
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        if ($filter->categoryId instanceof MitigationMeasureCategoryId) {
            $qb
                ->andWhere($qb->expr()->eq('m.categoryId', ':categoryId'))
                ->setParameter('categoryId', (string) $filter->categoryId)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('m.registeredAt', $way),
            default => $qb->orderBy('m.name', $way),
        };

        $qb->addOrderBy('m.id', 'ASC');

        return $qb;
    }

    /** @return array<MitigationMeasure> */
    public function findByFilter(MitigationMeasureFilter $filter): array
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

    public function totalResultsByFilter(MitigationMeasureFilter $filter): int
    {
        return (int) $this->createQueryBuilderFromFilter($filter)
            ->select('COUNT(DISTINCT m)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
