<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuel;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelFilter;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelTranslation;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.machinery_fuel', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineMachineryFuelRepository implements MachineryFuelRepository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private EntityManagerInterface $manager) {}

    public function add(MachineryFuel $machineryFuel): void
    {
        $this->manager->persist($machineryFuel);
        $this->addTranslations($machineryFuel, MachineryFuelTranslation::class, $this->manager);
        $this->manager->flush();
    }

    public function remove(MachineryFuel $machineryFuel): void
    {
        $this->manager->remove($machineryFuel);
        $this->manager->flush();
    }

    public function find(MachineryFuelId $machineryFuelId): ?MachineryFuel
    {
        $qb = $this->createQueryBuilderFromFilter(new MachineryFuelFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('mf.id', ':id'),
            )
            ->setParameter('id', (string) $machineryFuelId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneByName(string $name): ?MachineryFuel
    {
        $qb = $this->createQueryBuilderFromFilter(new MachineryFuelFilter());

        return $qb
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->eq('mf.name', ':name'),
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

    private function createQueryBuilderFromFilter(MachineryFuelFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('mf')
            ->from(MachineryFuel::class, 'mf')
            ->leftJoin(MachineryFuelTranslation::class, 'trans', 'WITH', 'trans.translatable = mf AND trans.locale = :locale')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('mf.removed'),
                    $qb->expr()->eq('mf.removed', $qb->expr()->literal(false)),
                ),
            )
            ->setParameter('locale', LocaleGetter::locale())
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->like('mf.name', ':name'),
                            $qb->expr()->isNull('trans.name'),
                        ),
                        $qb->expr()->like('trans.name', ':name'),
                    ),
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        if (null !== $filter->machineryType) {
            $qb
                ->andWhere($qb->expr()->eq('mf.machineryType', ':machineryType'))
                ->setParameter('machineryType', $filter->machineryType->value)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('mf.registeredAt', $way),
            'updatedAt' => $qb->orderBy('mf.updatedAt', $way),
            default => $qb->orderBy('mf.name', $way),
        };

        return $qb;
    }

    /** @return array<MachineryFuel> */
    public function findByFilter(MachineryFuelFilter $filter): array
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

    public function totalResultsByFilter(MachineryFuelFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT mf)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->manager;
    }
}
