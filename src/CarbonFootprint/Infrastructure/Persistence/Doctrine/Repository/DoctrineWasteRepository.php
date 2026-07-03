<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\Waste\Waste;
use App\CarbonFootprint\Domain\Model\Waste\WasteFilter;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\CarbonFootprint\Domain\Model\Waste\WasteRepository;
use App\CarbonFootprint\Domain\Model\Waste\WasteTranslation;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.waste', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineWasteRepository implements WasteRepository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private EntityManagerInterface $manager) {}

    public function add(Waste $waste): void
    {
        $this->manager->persist($waste);
        $this->addTranslations($waste, WasteTranslation::class, $this->manager);
        $this->manager->flush();
    }

    public function remove(Waste $waste): void
    {
        $this->manager->remove($waste);
        $this->manager->flush();
    }

    public function find(WasteId $wasteId): ?Waste
    {
        $qb = $this->createQueryBuilderFromFilter(new WasteFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('w.id', ':id'),
            )
            ->setParameter('id', (string) $wasteId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(WasteFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('w')
            ->from(Waste::class, 'w')
            ->leftJoin(WasteTranslation::class, 'trans', 'WITH', 'trans.translatable = w AND trans.locale = :locale')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('w.removed'),
                    $qb->expr()->eq('w.removed', $qb->expr()->literal(false)),
                ),
            )
            ->setParameter('locale', LocaleGetter::locale())
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->like('w.name', ':name'),
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
            'registeredAt' => $qb->orderBy('w.registeredAt', $way),
            'updatedAt' => $qb->orderBy('w.updatedAt', $way),
            default => $qb->orderBy('w.name', $way),
        };

        return $qb;
    }

    /** @return array<Waste> */
    public function findByFilter(WasteFilter $filter): array
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

    public function totalResultsByFilter(WasteFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT w)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->manager;
    }
}
