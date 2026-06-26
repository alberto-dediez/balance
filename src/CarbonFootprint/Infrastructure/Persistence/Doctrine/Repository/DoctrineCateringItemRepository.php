<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\CateringItem\CateringItem;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemFilter;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemTranslation;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.catering_item', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineCateringItemRepository implements CateringItemRepository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private EntityManagerInterface $manager) {}

    public function add(CateringItem $cateringItem): void
    {
        $this->manager->persist($cateringItem);
        $this->addTranslations($cateringItem, CateringItemTranslation::class, $this->manager);
        $this->manager->flush();
    }

    public function remove(CateringItem $cateringItem): void
    {
        $this->manager->remove($cateringItem);
        $this->manager->flush();
    }

    public function find(CateringItemId $cateringItemId): ?CateringItem
    {
        $qb = $this->createQueryBuilderFromFilter(new CateringItemFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('m.id', ':id'),
            )
            ->setParameter('id', (string) $cateringItemId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneByName(string $name): ?CateringItem
    {
        $qb = $this->createQueryBuilderFromFilter(new CateringItemFilter());

        return $qb
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->eq('m.name', ':name'),
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

    private function createQueryBuilderFromFilter(CateringItemFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('m')
            ->from(CateringItem::class, 'm')
            ->leftJoin(CateringItemTranslation::class, 'trans', 'WITH', 'trans.translatable = m AND trans.locale = :locale')
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

        if (null !== $filter->category) {
            $qb
                ->andWhere($qb->expr()->eq('m.category', ':category'))
                ->setParameter('category', $filter->category->value)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('m.registeredAt', $way),
            'updatedAt' => $qb->orderBy('m.updatedAt', $way),
            default => $qb->orderBy('m.name', $way),
        };

        return $qb;
    }

    /** @return array<CateringItem> */
    public function findByFilter(CateringItemFilter $filter): array
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

    public function totalResultsByFilter(CateringItemFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT m)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->manager;
    }
}
