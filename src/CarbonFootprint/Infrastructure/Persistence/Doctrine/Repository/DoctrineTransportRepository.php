<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\Transport\Transport;
use App\CarbonFootprint\Domain\Model\Transport\TransportFilter;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\CarbonFootprint\Domain\Model\Transport\TransportMode;
use App\CarbonFootprint\Domain\Model\Transport\TransportRepository;
use App\CarbonFootprint\Domain\Model\Transport\TransportTranslation;
use App\CarbonFootprint\Domain\Model\Transport\Vehicle;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.transport', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineTransportRepository implements TransportRepository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private EntityManagerInterface $manager) {}

    public function add(Transport $transport): void
    {
        $this->manager->persist($transport);
        $this->addTranslations($transport, TransportTranslation::class, $this->manager);
        $this->manager->flush();
    }

    public function remove(Transport $transport): void
    {
        $this->manager->remove($transport);
        $this->manager->flush();
    }

    public function find(TransportId $transportId): ?Transport
    {
        $qb = $this->createQueryBuilderFromFilter(new TransportFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('r.id', ':id'),
            )
            ->setParameter('id', (string) $transportId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(TransportFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('r')
            ->from(Transport::class, 'r')
            ->leftJoin(TransportTranslation::class, 'trans', 'WITH', 'trans.translatable = r AND trans.locale = :locale')
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

        if ($filter->mode instanceof TransportMode) {
            $qb
                ->andWhere($qb->expr()->in('r.vehicle', ':vehicles'))
                ->setParameter('vehicles', array_map(static fn(Vehicle $vehicle): string => $vehicle->value, Vehicle::ofMode($filter->mode)))
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('r.registeredAt', $way),
            'updatedAt' => $qb->orderBy('r.updatedAt', $way),
            default => $qb->orderBy('r.name', $way),
        };

        $qb->addOrderBy('r.id', 'ASC');

        return $qb;
    }

    /** @return array<Transport> */
    public function findByFilter(TransportFilter $filter): array
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

    public function totalResultsByFilter(TransportFilter $filter): int
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
