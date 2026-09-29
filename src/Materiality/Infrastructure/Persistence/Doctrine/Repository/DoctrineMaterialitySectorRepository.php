<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\Persistence\Doctrine\Repository;

use App\Materiality\Domain\Model\MaterialitySector\MaterialitySector;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorFilter;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepository;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorTranslation;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.materiality_sector', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineMaterialitySectorRepository implements MaterialitySectorRepository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private EntityManagerInterface $manager) {}

    public function add(MaterialitySector $materialitySector): void
    {
        $this->manager->persist($materialitySector);
        $this->addTranslations($materialitySector, MaterialitySectorTranslation::class, $this->manager);
        $this->manager->flush();
    }

    public function remove(MaterialitySector $materialitySector): void
    {
        $this->manager->remove($materialitySector);
        $this->manager->flush();
    }

    public function find(MaterialitySectorId $materialitySectorId): ?MaterialitySector
    {
        $qb = $this->createQueryBuilderFromFilter(new MaterialitySectorFilter());

        return $qb
            ->andWhere($qb->expr()->eq('s.id', ':id'))
            ->setParameter('id', (string) $materialitySectorId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /** @return array<MaterialitySector> */
    public function findByFilter(MaterialitySectorFilter $filter): array
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

    public function totalResultsByFilter(MaterialitySectorFilter $filter): int
    {
        return (int) $this->createQueryBuilderFromFilter($filter)
            ->select('COUNT(DISTINCT s)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /** @return list<MaterialitySector> */
    public function findAll(): array
    {
        return array_values($this->findByFilter(new MaterialitySectorFilter(limit: 0)));
    }

    private function createQueryBuilderFromFilter(MaterialitySectorFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('s')
            ->from(MaterialitySector::class, 's')
            ->leftJoin(MaterialitySectorTranslation::class, 'trans', 'WITH', 'trans.translatable = s AND trans.locale = :locale')
            ->setParameter('locale', LocaleGetter::locale())
        ;

        if (!$filter->includeRemoved) {
            $qb->andWhere($qb->expr()->eq('s.removed', $qb->expr()->literal(false)));
        }

        if (null !== $filter->name && '' !== $filter->name) {
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
                ->setParameter('name', '%' . $filter->name . '%')
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('s.registeredAt', $way),
            'updatedAt' => $qb->orderBy('s.updatedAt', $way),
            default => $qb->addSelect('COALESCE(trans.name, s.name) AS HIDDEN sortName')->orderBy('sortName', $way),
        };

        $qb->addOrderBy('s.id', 'ASC');

        return $qb;
    }
}
