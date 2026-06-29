<?php

declare(strict_types=1);

namespace App\EventOperations\Infrastructure\Persistence\Doctrine\Repository;

use App\EventOperations\Domain\Model\Provider\Provider;
use App\EventOperations\Domain\Model\Provider\ProviderFilter;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.provider', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineProviderRepository implements ProviderRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(Provider $provider): void
    {
        $this->manager->persist($provider);
        $this->manager->flush();
    }

    public function remove(Provider $provider): void
    {
        $this->manager->remove($provider);
        $this->manager->flush();
    }

    public function find(ProviderId $providerId): ?Provider
    {
        $qb = $this->createQueryBuilderFromFilter(new ProviderFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('p.id', ':id'),
            )
            ->setParameter('id', (string) $providerId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(ProviderFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('p')
            ->from(Provider::class, 'p')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('p.removed'),
                    $qb->expr()->eq('p.removed', ':removed'),
                ),
            )
            ->setParameter('removed', false, Types::BOOLEAN)
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('p.name', ':name'),
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        if ($filter->accountId instanceof AccountId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('p.accountId', ':accountId'),
                )
                ->setParameter('accountId', $filter->accountId)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('p.registeredAt', $way),
            default => $qb->orderBy('p.name', $way),
        };

        return $qb;
    }

    /** @return array<Provider> */
    public function findByFilter(ProviderFilter $filter): array
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

    public function totalResultsByFilter(ProviderFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT p)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
