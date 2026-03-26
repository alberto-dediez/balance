<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Persistence\Doctrine\Repository;

use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Destination\Destination;
use App\Property\Domain\Model\Destination\DestinationFilter;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Domain\Model\Destination\DestinationRepository;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.destination', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineDestinationRepository implements DestinationRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(Destination $destination): void
    {
        $this->manager->persist($destination);
        $this->manager->flush();
    }

    public function remove(Destination $destination): void
    {
        $this->manager->remove($destination);
        $this->manager->flush();
    }

    public function find(DestinationId $destinationId): ?Destination
    {
        $qb = $this->createQueryBuilderFromFilter(new DestinationFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('d.id', ':id'),
            )
            ->setParameter('id', (string) $destinationId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByNameAndCountry(string $name, CountryId $countryId): ?Destination
    {
        $qb = $this->createQueryBuilderFromFilter(new DestinationFilter());

        return $qb
            ->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->like('d.name', ':name'),
                    $qb->expr()->eq('d.countryId', ':countryId'),
                ),
            )
            ->setParameter('name', $name)
            ->setParameter('countryId', $countryId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findBySourceAndExternalId(ExternalSource $source, ExternalId $externalId): ?Destination
    {
        if ($externalId->isEmpty()) {
            return null;
        }

        $qb = $this->createQueryBuilderFromFilter(new DestinationFilter());

        $attribute = $source->attribute();
        $sourceId = $source->id($externalId);

        return $qb
            ->andWhere(
                $qb->expr()->like("d.externalId.{$attribute}", ':sourceId'),
            )
            ->setParameter('sourceId', $sourceId)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(DestinationFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('d')
            ->from(Destination::class, 'd')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('d.removed'),
                    $qb->expr()->eq('d.removed', $qb->expr()->literal(false)),
                ),
            )
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('d.name', ':name'),
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        if ($filter->countryId instanceof CountryId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('d.countryId', ':countryId'),
                )
                ->setParameter('countryId', $filter->countryId)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('d.registeredAt', $way),
            default => $qb->orderBy('d.name', $way),
        };

        return $qb;
    }

    /** @return Destination[] */
    public function findByFilter(DestinationFilter $filter): array
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

    public function totalResultsByFilter(DestinationFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT d)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
