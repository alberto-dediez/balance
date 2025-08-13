<?php

namespace App\Waste\Infrastructure\Repository\DailyWaste;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Container\Purpose;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\DailyWaste\DailyWaste;
use App\Property\Domain\Model\Property\PropertyId;
use App\Waste\Domain\Model\DailyWaste\DailyWasteId;
use App\Waste\Domain\Model\DailyWaste\DailyWasteFilter;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use Symfony\Component\DependencyInjection\Attribute\When;
use App\Waste\Domain\Model\DailyWaste\DailyWasteRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(id: 'repository.dailyWaste', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineDailyWasteRepository implements DailyWasteRepository
{
    public function __construct(private EntityManagerInterface $manager)
    {
        
    }

    public function add(DailyWaste $dailyWaste): void
    {
        $this->getEntityManager()->persist($dailyWaste);
        $this->getEntityManager()->flush();
    }

    public function remove(DailyWaste $dailyWaste): void
    {
        $this->getEntityManager()->remove($dailyWaste);
        $this->getEntityManager()->flush();
    }

    public function find(DailyWasteId $dailyWasteId): ?DailyWaste
    {
        $qb = $this->createQueryBuilderFromFilter(new DailyWasteFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('m.id', ':id')
            )
            ->setParameter('id', (string) $dailyWasteId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByDay(\DateTimeImmutable $day, PropertyId $propertyId, WasteId $wasteId, Purpose $purpose, WasteManagerId $wasteManagerId, AccountId $accountId): ?DailyWaste
    {
        $filter = new DailyWasteFilter(
            limit: 0,
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            purpose: $purpose,
            accountId: $accountId,
            day: $day
        );

        return $this->createQueryBuilderFromFilter($filter)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(DailyWasteFilter $filter): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('m')
            ->from(DailyWaste::class, 'm')
        ;

        if (null !== $filter->propertyId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('m.propertyId', ':property')
                )
                ->setParameter('property', $filter->propertyId)
            ;
        }

        if (null !== $filter->wasteId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('m.wasteId', ':wasteId')
                )
                ->setParameter('wasteId', $filter->wasteId)
            ;
        }

        if (null !== $filter->purpose) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('m.purpose', ':purpose')
                )
                ->setParameter('purpose', $filter->purpose->value)
            ;
        }

        if (null !== $filter->wasteManagerId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('m.wasteManagerId', ':wasteManagerId')
                )
                ->setParameter('wasteManagerId', $filter->wasteManagerId)
            ;
        }

        if (null !== $filter->accountId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('m.accountId', ':accountId')
                )
                ->setParameter('accountId', $filter->accountId)
            ;
        }

        if (null !== $filter->day) {
            $qb
                ->andWhere(
                    $qb->expr()->like('m.day', ':day')
                )
                ->setParameter('day', sprintf("%s%%", $filter->day->format('Y-m-d')))
            ;
        }

        $way = $filter->orderWay == 'ASC' ? 'ASC' : 'DESC';

        switch ($filter->orderBy) {
            case 'day':
            default:
                $qb->orderBy('m.day', $way);
                break;
        }

        return $qb;
    }

    public function findByFilter(DailyWasteFilter $filter): array
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        $query = $qb->getQuery();

        if ($filter->limit > 0) {
            $query
                ->setMaxResults($filter->limit)
                ->setFirstResult($filter->page * $filter->limit)
            ;
        }

        return (array) $query->getResult();
    }

    public function totalResultsByFilter(DailyWasteFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return $qb->select('COUNT(DISTINCT m)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getEntityManager()
    {
        return $this->manager;
    }
}