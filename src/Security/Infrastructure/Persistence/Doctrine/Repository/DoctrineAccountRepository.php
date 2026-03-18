<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Persistence\Doctrine\Repository;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountFilter;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.account', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineAccountRepository implements AccountRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(Account $account): void
    {
        $this->manager->persist($account);
        $this->manager->flush();
    }

    public function remove(Account $account): void
    {
        $this->manager->remove($account);
        $this->manager->flush();
    }

    public function find(AccountId $accountId): ?Account
    {
        $qb = $this->createQueryBuilderFromFilter(new AccountFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('a.id', ':id'),
            )
            ->setParameter('id', $accountId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByName(string $name): ?Account
    {
        $qb = $this->createQueryBuilderFromFilter(new AccountFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('a.name', ':name'),
            )
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(AccountFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('a')
            ->from(Account::class, 'a')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('a.removed'),
                    $qb->expr()->eq('a.removed', $qb->expr()->literal(false)),
                ),
            )
        ;

        if (!in_array($filter->name, [null, '', '0'], true)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('a.name', ':name'),
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        if (null !== $filter->active) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('a.active', ':active'),
                )
                ->setParameter('active', $filter->active)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('a.registeredAt', $way),
            default => $qb->orderBy('a.name', $way),
        };

        return $qb;
    }

    /**
     * @return list<Account>
     */
    public function findByFilter(AccountFilter $filter): array
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

    public function totalResultsByFilter(AccountFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        $count = $qb->select('COUNT(DISTINCT a)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return intval($count);
    }
}
