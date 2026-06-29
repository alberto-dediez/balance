<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransport;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportFilter;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportTranslation;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.road_transport', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineRoadTransportRepository implements RoadTransportRepository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private EntityManagerInterface $manager) {}

    public function add(RoadTransport $roadTransport): void
    {
        $this->manager->persist($roadTransport);
        $this->addTranslations($roadTransport, RoadTransportTranslation::class, $this->manager);
        $this->manager->flush();
    }

    public function remove(RoadTransport $roadTransport): void
    {
        $this->manager->remove($roadTransport);
        $this->manager->flush();
    }

    public function find(RoadTransportId $roadTransportId): ?RoadTransport
    {
        $qb = $this->createQueryBuilderFromFilter(new RoadTransportFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('r.id', ':id'),
            )
            ->setParameter('id', (string) $roadTransportId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneByName(string $name): ?RoadTransport
    {
        $qb = $this->createQueryBuilderFromFilter(new RoadTransportFilter());

        return $qb
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->eq('r.name', ':name'),
                        $qb->expr()->isNull('trans.name'),
                    ),
                    $qb->expr()->eq('trans.name', ':name'),
                ),
            )
            ->setParameter('name', $name)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(RoadTransportFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('r')
            ->from(RoadTransport::class, 'r')
            ->leftJoin(RoadTransportTranslation::class, 'trans', 'WITH', 'trans.translatable = r AND trans.locale = :locale')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('r.removed'),
                    $qb->expr()->eq('r.removed', $qb->expr()->literal(false)),
                ),
            )
            ->setParameter('locale', LocaleGetter::locale())
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->like('r.name', ':name'),
                            $qb->expr()->isNull('trans.name'),
                        ),
                        $qb->expr()->like('trans.name', ':name'),
                    ),
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        if ($filter->unit instanceof Unit) {
            $qb
                ->andWhere($qb->expr()->eq('r.unit', ':unit'))
                ->setParameter('unit', $filter->unit->value)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('r.registeredAt', $way),
            'updatedAt' => $qb->orderBy('r.updatedAt', $way),
            default => $qb->orderBy('r.name', $way),
        };

        return $qb;
    }

    /** @return array<RoadTransport> */
    public function findByFilter(RoadTransportFilter $filter): array
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

    public function totalResultsByFilter(RoadTransportFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT r)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->manager;
    }
}
