<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSector;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorFilter;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorId;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepository;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorTranslation;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.economic_sector', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineEconomicSectorRepository implements EconomicSectorRepository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private EntityManagerInterface $manager) {}

    public function add(EconomicSector $economicSector): void
    {
        $this->manager->persist($economicSector);
        $this->addTranslations($economicSector, EconomicSectorTranslation::class, $this->manager);
        $this->manager->flush();
    }

    public function remove(EconomicSector $economicSector): void
    {
        $this->manager->remove($economicSector);
        $this->manager->flush();
    }

    public function find(EconomicSectorId $economicSectorId): ?EconomicSector
    {
        $qb = $this->createQueryBuilderFromFilter(new EconomicSectorFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('es.id', ':id'),
            )
            ->setParameter('id', (string) $economicSectorId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByClassificationCode(string $code): ?EconomicSector
    {
        $qb = $this->createQueryBuilderFromFilter(new EconomicSectorFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('es.classificationCode', ':code'),
            )
            ->setParameter('code', $code)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(EconomicSectorFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('es')
            ->from(EconomicSector::class, 'es')
            ->leftJoin(EconomicSectorTranslation::class, 'trans', 'WITH', 'trans.translatable = es AND trans.locale = :locale')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('es.removed'),
                    $qb->expr()->eq('es.removed', $qb->expr()->literal(false)),
                ),
            )
            ->setParameter('locale', LocaleGetter::locale())
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->like('es.name', ':name'),
                            $qb->expr()->isNull('trans.name'),
                        ),
                        $qb->expr()->like('trans.name', ':name'),
                    ),
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        if (!empty($filter->classificationCode)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('es.classificationCode', ':code'),
                )
                ->setParameter('code', mb_strtoupper(trim($filter->classificationCode)) . '%')
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('es.registeredAt', $way),
            'updatedAt' => $qb->orderBy('es.updatedAt', $way),
            'classificationCode' => $qb->orderBy('es.classificationCode', $way),
            default => $qb->orderBy('es.name', $way),
        };

        $qb->addOrderBy('es.id', 'ASC');

        return $qb;
    }

    /** @return array<EconomicSector> */
    public function findByFilter(EconomicSectorFilter $filter): array
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

    public function totalResultsByFilter(EconomicSectorFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT es)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->manager;
    }
}
