<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Persistence\Doctrine\Repository;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\DailyWaste\DailyWaste;
use App\Waste\Domain\Model\DailyWaste\DailyWasteFilter;
use App\Waste\Domain\Model\DailyWaste\DailyWasteId;
use App\Waste\Domain\Model\DailyWaste\DailyWasteRepository;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.dailyWaste', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineDailyWasteRepository implements DailyWasteRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(DailyWaste $dailyWaste): void
    {
        $this->manager->persist($dailyWaste);
        $this->manager->flush();
    }

    public function remove(DailyWaste $dailyWaste): void
    {
        $this->manager->remove($dailyWaste);
        $this->manager->flush();
    }

    public function find(DailyWasteId $dailyWasteId): ?DailyWaste
    {
        $qb = $this->createQueryBuilderFromFilter(new DailyWasteFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('m.id', ':id'),
            )
            ->setParameter('id', (string) $dailyWasteId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByDay(\DateTimeImmutable $day, PropertyId $propertyId, WasteId $wasteId, Purpose $purpose, WasteManagerId $wasteManagerId, AccountId $accountId): ?DailyWaste
    {
        $filter = new DailyWasteFilter(
            properties: [$propertyId],
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            purpose: $purpose,
            accountId: $accountId,
            day: $day,
            limit: 0,
        );

        return $this->createQueryBuilderFromFilter($filter)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(DailyWasteFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('m')
            ->from(DailyWaste::class, 'm')
        ;

        if (count($filter->properties) > 0) {
            $qb
                ->andWhere(
                    $qb->expr()->in('m.propertyId', ':properties'),
                )
                ->setParameter('properties', array_map(fn(PropertyId $propertyId) => (string) $propertyId, $filter->properties))
            ;
        }

        if ($filter->wasteId instanceof WasteId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('m.wasteId', ':wasteId'),
                )
                ->setParameter('wasteId', $filter->wasteId)
            ;
        }

        if ($filter->purpose instanceof Purpose) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('m.purpose', ':purpose'),
                )
                ->setParameter('purpose', $filter->purpose->value)
            ;
        }

        if ($filter->wasteManagerId instanceof WasteManagerId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('m.wasteManagerId', ':wasteManagerId'),
                )
                ->setParameter('wasteManagerId', $filter->wasteManagerId)
            ;
        }

        if ($filter->accountId instanceof AccountId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('m.accountId', ':accountId'),
                )
                ->setParameter('accountId', $filter->accountId)
            ;
        }

        if ($filter->day instanceof \DateTimeImmutable) {
            $qb
                ->andWhere(
                    $qb->expr()->like('m.day', ':day'),
                )
                ->setParameter('day', sprintf('%s%%', $filter->day->format('Y-m-d')))
            ;
        }

        if ($filter->dayFrom instanceof \DateTimeImmutable) {
            $qb
                ->andWhere(
                    $qb->expr()->gte('m.day', ':dayFrom'),
                )
                ->setParameter('dayFrom', $filter->dayFrom->format('Y-m-d'))
            ;
        }

        if ($filter->dayTo instanceof \DateTimeImmutable) {
            $qb
                ->andWhere(
                    $qb->expr()->lte('m.day', ':dayTo'),
                )
                ->setParameter('dayTo', $filter->dayTo->format('Y-m-d'))
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            default => $qb->orderBy('m.day', $way),
        };

        return $qb;
    }

    /** @return array<int, DailyWaste> */
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

        return (int) $qb->select('COUNT(DISTINCT m)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
