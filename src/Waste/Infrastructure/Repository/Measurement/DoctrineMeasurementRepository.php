<?php

namespace App\Waste\Infrastructure\Repository\Measurement;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Container\Purpose;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\DailyWaste\DailyWaste;
use App\Property\Domain\Model\Property\PropertyId;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use App\Waste\Domain\Model\Measurement\MeasurementFilter;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;

#[AsAlias(id: 'repository.measurement', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineMeasurementRepository implements MeasurementRepository
{
    public function __construct(private EntityManagerInterface $manager)
    {
        
    }

    public function add(Measurement $measurement): void
    {
        $this->getEntityManager()->persist($measurement);
        $this->getEntityManager()->flush();
    }

    public function remove(Measurement $measurement): void
    {
        $this->getEntityManager()->remove($measurement);
        $this->getEntityManager()->flush();
    }

    public function find(MeasurementId $measurementId): ?Measurement
    {
        $qb = $this->createQueryBuilderFromFilter(new MeasurementFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('m.id', ':id')
            )
            ->setParameter('id', (string) $measurementId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByDay(\DateTimeImmutable $day, PropertyId $propertyId, WasteId $wasteId, Purpose $purpose, WasteManagerId $wasteManagerId, AccountId $accountId): array
    {
        $filter = new MeasurementFilter(
            limit: 0,
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            purpose: $purpose,
            accountId: $accountId,
            registeredAt: $day
        );

        return $this->findByFilter($filter);
    }

    private function createQueryBuilderFromFilter(MeasurementFilter $filter): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('m')
            ->from(Measurement::class, 'm')
        ;

        if (null !== $filter->containerId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('m.containerId', ':container')
                )
                ->setParameter('container', $filter->containerId)
            ;
        }

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

        if (null !== $filter->measurementMethod) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('m.measurementMethod', ':measurementMethod')
                )
                ->setParameter('measurementMethod', $filter->measurementMethod->value)
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

        if (null !== $filter->userId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('m.registeredBy', ':userId')
                )
                ->setParameter('userId', $filter->userId)
            ;
        }

        if (null !== $filter->registeredAt) {
            $qb
                ->andWhere(
                    $qb->expr()->like('m.registeredAt', ':registeredAt')
                )
                ->setParameter('registeredAt', sprintf("%s%%", $filter->registeredAt->format('Y-m-d')))
            ;
        }

        $way = $filter->orderWay == 'ASC' ? 'ASC' : 'DESC';

        switch ($filter->orderBy) {
            case 'registeredAt':
            default:
                $qb->orderBy('m.registeredAt', $way);
                break;
        }

        return $qb;
    }

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