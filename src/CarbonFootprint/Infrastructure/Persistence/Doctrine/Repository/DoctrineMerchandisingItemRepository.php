<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItem;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemFilter;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemTranslation;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.merchandising_item', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineMerchandisingItemRepository implements MerchandisingItemRepository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private EntityManagerInterface $manager) {}

    public function add(MerchandisingItem $merchandisingItem): void
    {
        $this->manager->persist($merchandisingItem);
        $this->addTranslations($merchandisingItem, MerchandisingItemTranslation::class, $this->manager);
        $this->manager->flush();
    }

    public function remove(MerchandisingItem $merchandisingItem): void
    {
        $this->manager->remove($merchandisingItem);
        $this->manager->flush();
    }

    public function find(MerchandisingItemId $merchandisingItemId): ?MerchandisingItem
    {
        $qb = $this->createQueryBuilderFromFilter(new MerchandisingItemFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('m.id', ':id'),
            )
            ->setParameter('id', (string) $merchandisingItemId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(MerchandisingItemFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('m')
            ->from(MerchandisingItem::class, 'm')
            ->leftJoin(MerchandisingItemTranslation::class, 'trans', 'WITH', 'trans.translatable = m AND trans.locale = :locale')
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

    /** @return array<MerchandisingItem> */
    public function findByFilter(MerchandisingItemFilter $filter): array
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

    public function totalResultsByFilter(MerchandisingItemFilter $filter): int
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
