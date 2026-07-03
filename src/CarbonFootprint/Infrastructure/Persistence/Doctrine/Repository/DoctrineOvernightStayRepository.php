<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStay;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayFilter;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayTranslation;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.overnight_stay', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineOvernightStayRepository implements OvernightStayRepository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private EntityManagerInterface $manager) {}

    public function add(OvernightStay $overnightStay): void
    {
        $this->manager->persist($overnightStay);
        $this->addTranslations($overnightStay, OvernightStayTranslation::class, $this->manager);
        $this->manager->flush();
    }

    public function remove(OvernightStay $overnightStay): void
    {
        $this->manager->remove($overnightStay);
        $this->manager->flush();
    }

    public function find(OvernightStayId $overnightStayId): ?OvernightStay
    {
        $qb = $this->createQueryBuilderFromFilter(new OvernightStayFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('o.id', ':id'),
            )
            ->setParameter('id', (string) $overnightStayId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(OvernightStayFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('o')
            ->from(OvernightStay::class, 'o')
            ->leftJoin(OvernightStayTranslation::class, 'trans', 'WITH', 'trans.translatable = o AND trans.locale = :locale')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('o.removed'),
                    $qb->expr()->eq('o.removed', $qb->expr()->literal(false)),
                ),
            )
            ->setParameter('locale', LocaleGetter::locale())
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->like('o.name', ':name'),
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
                ->andWhere($qb->expr()->eq('o.category', ':category'))
                ->setParameter('category', $filter->category->value)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('o.registeredAt', $way),
            'updatedAt' => $qb->orderBy('o.updatedAt', $way),
            default => $qb->orderBy('o.name', $way),
        };

        $qb->addOrderBy('o.id', 'ASC');

        return $qb;
    }

    /** @return array<OvernightStay> */
    public function findByFilter(OvernightStayFilter $filter): array
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

    public function totalResultsByFilter(OvernightStayFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT o)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->manager;
    }
}
