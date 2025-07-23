<?php

namespace App\Security\Infrastructure\Repository\Account;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountFilter;
use App\Security\Domain\Model\Account\AccountRepository;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(id: 'repository.account', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineAccountRepository implements AccountRepository
{
    private EntityRepository $repository;

    public function __construct(private EntityManagerInterface $manager)
    {
        $this->repository = $manager->getRepository(Account::class);
    }

    public function add(Account $account): void
    {
        $this->getEntityManager()->persist($account);
        $this->getEntityManager()->flush();
    }

    public function remove(Account $account): void
    {
        $this->getEntityManager()->remove($account);
        $this->getEntityManager()->flush();
    }

    public function find(AccountId $accountId): ?Account
    {
        $qb = $this->createQueryBuilderFromFilter(new AccountFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('a.id.id', ':id')
            )
            ->setParameter('id', (string) $accountId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByName(string $name): ?Account
    {
        $qb = $this->createQueryBuilderFromFilter(new AccountFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('a.name', ':name')
            )
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(AccountFilter $filter): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('a')
            ->from(Account::class, 'a')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('a.removed'),
                    $qb->expr()->eq('a.removed', $qb->expr()->literal(false))
                )
            )
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('a.name', ':name')
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        if (null !== $filter->active) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('a.active', ':active')
                )
                ->setParameter('active', $filter->active)
            ;
        }

        $way = $filter->orderWay == 'ASC' ? 'ASC' : 'DESC';

        switch ($filter->orderBy) {
            case 'registeredAt':
                $qb->orderBy('a.registeredAt', $way);
                break;
            case 'name':
            default:
                $qb->orderBy('a.name', $way);        
                break;
        }

        return $qb;
    }

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

        return $qb->select('COUNT(DISTINCT a)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getEntityManager()
    {
        return $this->manager;
    }
}