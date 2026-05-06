<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\SurplusCause\SurplusCause;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseFilter;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepository;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseTranslation;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.surplus.cause', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineSurplusCauseRepository implements SurplusCauseRepository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private EntityManagerInterface $manager) {}

    public function add(SurplusCause $surplusCause): void
    {
        $this->manager->persist($surplusCause);

        $this->addTranslations($surplusCause, SurplusCauseTranslation::class, $this->manager);

        $this->manager->flush();
    }

    public function remove(SurplusCause $surplusCause): void
    {
        $this->manager->remove($surplusCause);
        $this->manager->flush();
    }

    public function find(SurplusCauseId $surplusCauseId): ?SurplusCause
    {
        $qb = $this->createQueryBuilderFromFilter(new SurplusCauseFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('s.id', ':id'),
            )
            ->setParameter('id', (string) $surplusCauseId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(SurplusCauseFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('s')
            ->from(SurplusCause::class, 's')
            ->leftJoin(SurplusCauseTranslation::class, 'trans', 'WITH', 'trans.translatable = s AND trans.locale = :locale')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('s.removed'),
                    $qb->expr()->eq('s.removed', $qb->expr()->literal(false)),
                ),
            )
            ->setParameter('locale', LocaleGetter::locale())
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->like('s.name', ':name'),
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
            'registeredAt' => $qb->orderBy('s.registeredAt', $way),
            'updatedAt' => $qb->orderBy('s.updatedAt', $way),
            default => $qb->orderBy('s.name', $way),
        };

        return $qb;
    }

    /** @return array<SurplusCause> */
    public function findByFilter(SurplusCauseFilter $filter): array
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

    public function totalResultsByFilter(SurplusCauseFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT s)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
