<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Persistence\Doctrine\Repository;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementFilter;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.measurement', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineMeasurementRepository implements MeasurementRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(Measurement $measurement): void
    {
        $this->manager->persist($measurement);
        $this->manager->flush();
    }

    public function remove(Measurement $measurement): void
    {
        $this->manager->remove($measurement);
        $this->manager->flush();
    }

    public function find(MeasurementId $measurementId): ?Measurement
    {
        $qb = $this->createQueryBuilderFromFilter(new MeasurementFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('m.id', ':id'),
            )
            ->setParameter('id', (string) $measurementId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /** @return array<int, Measurement> */
    public function findByDay(\DateTimeImmutable $day, PropertyId $propertyId, WasteId $wasteId, Purpose $purpose, WasteManagerId $wasteManagerId, AccountId $accountId): array
    {
        $filter = new MeasurementFilter(
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            purpose: $purpose,
            accountId: $accountId,
            registeredAt: $day,
            limit: 0,
        );

        return $this->findByFilter($filter);
    }

    private function createQueryBuilderFromFilter(MeasurementFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('m')
            ->from(Measurement::class, 'm')
        ;

        if ($filter->containerId instanceof ContainerId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('m.containerId', ':container'),
                )
                ->setParameter('container', $filter->containerId)
            ;
        }

        if ($filter->propertyId instanceof PropertyId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('m.propertyId', ':property'),
                )
                ->setParameter('property', $filter->propertyId)
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

        if ($filter->measurementMethod instanceof MeasurementMethod) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('m.measurementMethod', ':measurementMethod'),
                )
                ->setParameter('measurementMethod', $filter->measurementMethod->value)
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

        if ($filter->userId instanceof UserId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('m.registeredBy', ':userId'),
                )
                ->setParameter('userId', $filter->userId)
            ;
        }

        if (!empty($filter->reference)) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('m.reference', ':reference'),
                )
                ->setParameter('reference', $filter->reference)
            ;
        }

        if ($filter->registeredAt instanceof \DateTimeImmutable) {
            $qb
                ->andWhere(
                    $qb->expr()->like('m.registeredAt', ':registeredAt'),
                )
                ->setParameter('registeredAt', sprintf('%s%%', $filter->registeredAt->format('Y-m-d')))
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            default => $qb->orderBy('m.registeredAt', $way),
        };

        return $qb;
    }

    /** @return array<int, Measurement> */
    public function findByFilter(MeasurementFilter $filter): array
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

    public function totalResultsByFilter(MeasurementFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT m)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
