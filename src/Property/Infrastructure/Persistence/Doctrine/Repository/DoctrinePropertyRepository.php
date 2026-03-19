<?php

namespace App\Property\Infrastructure\Persistence\Doctrine\Repository;

use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Domain\Model\Country\CountryId;
use App\Security\Domain\Model\Account\AccountId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.property', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrinePropertyRepository implements PropertyRepository
{
    const GEO_PRECISSION = 0.00001;

    public function __construct(private readonly EntityManagerInterface $manager)
    {

    }

    public function add(Property $property): void
    {
        $this->manager->persist($property);
        $this->manager->flush();
    }

    public function remove(Property $property): void
    {
        $this->manager->remove($property);
        $this->manager->flush();
    }

    public function find(PropertyId $propertyId): ?Property
    {
        $qb = $this->createQueryBuilderFromFilter(new PropertyFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('p.id', ':id')
            )
            ->setParameter('id', (string) $propertyId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByName(string $name): ?Property
    {
        $qb = $this->createQueryBuilderFromFilter(new PropertyFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('p.name', ':name')
            )
            ->setParameter('name', $name)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;
    }

    public function findByExternalId(ExternalId $externalId): ?Property
    {
        if ($externalId->isEmpty()) {
            return null;
        }

        $qb = $this->createQueryBuilderFromFilter(new PropertyFilter());

        $expr = $qb->expr()->orX();

        if (null != $externalId->bioscore()) {
            $expr->add($qb->expr()->like('p.externalId.bioscore', ':bioscore'));

            $qb->setParameter('bioscore', $externalId->bioscore());
        }

        if (null != $externalId->restel()) {
            $expr->add($qb->expr()->like('p.externalId.restel', ':restel'));

            $qb->setParameter('restel', $externalId->restel());
        }

        if (null != $externalId->hotelbeds()) {
            $expr->add($qb->expr()->like('p.externalId.hotelbeds', ':hotelbeds'));

            $qb->setParameter('hotelbeds', $externalId->hotelbeds());
        }

        return $qb
            ->andWhere(
                $expr
            )
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;
    }

    public function findBySourceAndExternalId(ExternalSource $source, ExternalId $externalId): ?Property
    {
        if ($externalId->isEmpty()) {
            return null;
        }

        $qb = $this->createQueryBuilderFromFilter(new PropertyFilter());

        $attribute = $source->attribute();
        $sourceId = $source->id($externalId);

        return $qb
            ->andWhere(
                $qb->expr()->like("p.externalId.{$attribute}", ":sourceId")
            )
            ->setParameter('sourceId', $sourceId)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;
    }

    public function findByGeoPosition(string $latitude, string $longitude): ?Property
    {
        $qb = $this->createQueryBuilderFromFilter(new PropertyFilter());

        $latitude = (float) $latitude;
        $longitude = (float) $longitude;

        return $qb
            ->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->between('p.info.latitude', ':min_latitude', ':max_latitude'),
                    $qb->expr()->between('p.info.longitude', ':min_longitude', ':max_longitude'),
                )
            )
            ->setParameter('min_latitude', $latitude - self::GEO_PRECISSION)
            ->setParameter('max_latitude', $latitude + self::GEO_PRECISSION)
            ->setParameter('min_longitude', $longitude - self::GEO_PRECISSION)
            ->setParameter('max_longitude', $longitude + self::GEO_PRECISSION)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(PropertyFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('p')
            ->from(Property::class, 'p')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('p.removed'),
                    $qb->expr()->eq('p.removed', $qb->expr()->literal(false))
                )
            )
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('p.name', ':name')
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        if ($filter->destinationId instanceof DestinationId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('p.info.destinationId', ':destinationId')
                )
                ->setParameter('destinationId', $filter->destinationId)
            ;
        }

        if ($filter->countryId instanceof CountryId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('p.info.countryId', ':countryId')
                )
                ->setParameter('countryId', $filter->countryId)
            ;
        }

        if ($filter->accountId instanceof AccountId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('p.accountId', ':accountId')
                )
                ->setParameter('accountId', $filter->accountId)
            ;
        }

        $way = $filter->orderWay === 'ASC' ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('p.registeredAt', $way),
            default => $qb->orderBy('p.name', $way),
        };

        return $qb;
    }

    public function findByFilter(PropertyFilter $filter): array
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

    public function totalResultsByFilter(PropertyFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return $qb->select('COUNT(DISTINCT p)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
