<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Persistence\Doctrine\Repository;

use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use App\Waste\Domain\Model\Waste\Waste;
use App\Waste\Domain\Model\Waste\WasteFilter;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Waste\WasteRepository;
use App\Waste\Domain\Model\Waste\WasteTranslation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.waste', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineWasteRepository implements WasteRepository
{
    use DoctrineTranslatableRepositoryTrait;

    private EntityRepository $repository;

    public function __construct(private EntityManagerInterface $manager, private LocaleGetter $localeGetter)
    {
        $this->repository = $manager->getRepository(Waste::class);
    }

    public function add(Waste $waste): void
    {
        $this->manager->persist($waste);

        $this->addTranslations($waste, $this->manager->getRepository(WasteTranslation::class));

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

        $waste = $qb
            ->andWhere(
                $qb->expr()->eq('w.id', ':id'),
            )
            ->setParameter('id', (string) $wasteId)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        $this->loadTranslations($waste, $this->manager->getRepository(WasteTranslation::class));

        return $waste;
    }

    private function createQueryBuilderFromFilter(WasteFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('w')
            ->from(Waste::class, 'w')
            ->leftJoin(WasteTranslation::class, 'trans', 'WITH', 'trans.translatableId = w.id AND trans.locale = :locale')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('w.removed'),
                    $qb->expr()->eq('w.removed', $qb->expr()->literal(false)),
                ),
            )
            ->setParameter('locale', $this->localeGetter->locale())
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

        if (null !== $filter->isDangerous) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('w.isDangerous', ':isDangerous'),
                )
                ->setParameter('isDangerous', $filter->isDangerous)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('w.registeredAt', $way),
            default => $qb->orderBy('w.name', $way),
        };

        return $qb;
    }

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

        $wastes = (array) $query->getResult();

        if (0 === count($wastes)) {
            return [];
        }

        foreach ($wastes as $waste) {
            $this->loadTranslations($waste, $this->manager->getRepository(WasteTranslation::class));
        }

        return $wastes;
    }

    public function totalResultsByFilter(WasteFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return $qb->select('COUNT(DISTINCT w)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->manager;
    }
}
