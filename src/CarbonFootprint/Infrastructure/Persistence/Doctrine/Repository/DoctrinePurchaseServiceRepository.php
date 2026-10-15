<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseService;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceFilter;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceTranslation;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.purchase_service', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrinePurchaseServiceRepository implements PurchaseServiceRepository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private EntityManagerInterface $manager) {}

    public function add(PurchaseService $purchaseService): void
    {
        $this->manager->persist($purchaseService);
        $this->addTranslations($purchaseService, PurchaseServiceTranslation::class, $this->manager);
        $this->manager->flush();
    }

    public function remove(PurchaseService $purchaseService): void
    {
        $this->manager->remove($purchaseService);
        $this->manager->flush();
    }

    public function find(PurchaseServiceId $purchaseServiceId): ?PurchaseService
    {
        $qb = $this->createQueryBuilderFromFilter(new PurchaseServiceFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('p.id', ':id'),
            )
            ->setParameter('id', (string) $purchaseServiceId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(PurchaseServiceFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('p')
            ->from(PurchaseService::class, 'p')
            ->leftJoin(PurchaseServiceTranslation::class, 'trans', 'WITH', 'trans.translatable = p AND trans.locale = :locale')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('p.removed'),
                    $qb->expr()->eq('p.removed', $qb->expr()->literal(false)),
                ),
            )
            ->setParameter('locale', LocaleGetter::locale())
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->like('p.name', ':name'),
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
                ->andWhere($qb->expr()->eq('p.category', ':category'))
                ->setParameter('category', $filter->category->value)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('p.registeredAt', $way),
            'updatedAt' => $qb->orderBy('p.updatedAt', $way),
            default => $qb->orderBy('p.name', $way),
        };

        $qb->addOrderBy('p.id', 'ASC');

        return $qb;
    }

    /** @return array<PurchaseService> */
    public function findByFilter(PurchaseServiceFilter $filter): array
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

    public function totalResultsByFilter(PurchaseServiceFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT p)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->manager;
    }
}
