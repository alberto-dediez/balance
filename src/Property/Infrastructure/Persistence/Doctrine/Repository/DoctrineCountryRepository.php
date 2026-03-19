<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Persistence\Doctrine\Repository;

use App\Property\Domain\Model\Country\Country;
use App\Property\Domain\Model\Country\CountryFilter;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.country', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineCountryRepository implements CountryRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(Country $country): void
    {
        $this->manager->persist($country);
        $this->manager->flush();
    }

    public function remove(Country $country): void
    {
        $this->manager->remove($country);
        $this->manager->flush();
    }

    public function find(CountryId $countryId): ?Country
    {
        $qb = $this->createQueryBuilderFromFilter(new CountryFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('c.id', ':id'),
            )
            ->setParameter('id', (string) $countryId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByName(string $name): ?Country
    {
        $qb = $this->createQueryBuilderFromFilter(new CountryFilter());

        return $qb
            ->andWhere(
                $qb->expr()->like('c.name', ':name'),
            )
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByIso(string $iso): ?Country
    {
        $qb = $this->createQueryBuilderFromFilter(new CountryFilter());

        return $qb
            ->andWhere(
                $qb->expr()->like('c.iso', ':iso'),
            )
            ->setParameter('iso', $iso)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findBySourceAndExternalId(ExternalSource $source, ExternalId $externalId): ?Country
    {
        if ($externalId->isEmpty()) {
            return null;
        }

        $qb = $this->createQueryBuilderFromFilter(new CountryFilter());

        $attribute = $source->attribute();
        $sourceId = $source->id($externalId);

        return $qb
            ->andWhere(
                $qb->expr()->like("c.externalId.{$attribute}", ':sourceId'),
            )
            ->setParameter('sourceId', $sourceId)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(CountryFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('c')
            ->from(Country::class, 'c')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('c.removed'),
                    $qb->expr()->eq('c.removed', $qb->expr()->literal(false)),
                ),
            )
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('c.name', ':name'),
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        if (null !== $filter->iso) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('c.iso', ':iso'),
                )
                ->setParameter('iso', $filter->iso)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('c.registeredAt', $way),
            'name' => $qb->orderBy('c.name', $way),
            default => $qb->orderBy('c.iso', $way),
        };

        return $qb;
    }

    public function findByFilter(CountryFilter $filter): array
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

    public function totalResultsByFilter(CountryFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return $qb->select('COUNT(DISTINCT c)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
