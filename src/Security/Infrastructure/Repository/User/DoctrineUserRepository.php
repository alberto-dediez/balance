<?php

namespace App\Security\Infrastructure\Repository\User;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityRepository;
use App\Security\Domain\Model\User\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserFilter;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\RecoverToken;
use App\Security\Domain\Model\User\UserRepository;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use App\Security\Infrastructure\Repository\Account\DoctrineAccountRepository;

#[AsAlias(id: 'repository.user', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineUserRepository implements UserRepository
{
    private EntityRepository $repository;

    public function __construct(private EntityManagerInterface $manager, private DoctrineAccountRepository $accountRepository)
    {
        $this->repository = $manager->getRepository(User::class);
    }

    public function add(User $user): void
    {
        if (null == $user->account() && null !== $user->accountId()) {
            $account = $this->accountRepository->find($user->accountId());

            $user = new User(
                id: $user->id(),
                username: $user->username(),
                password: $user->password(),
                accountId: $user->accountId(),
                account: $account,
                roles: $user->roles(),
                events: $user->recordedEvents(),
                registeredAt: $user->registeredAt(),
                removed: $user->isRemoved(),
                removedAt: $user->removedAt()
            );
        }

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function remove(User $user): void
    {
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();
    }

    public function find(UserId $userId): ?User
    {
        $qb = $this->createQueryBuilderFromFilter(new UserFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('u.id', ':id')
            )
            ->setParameter('id', $userId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByUsername(string $username): ?User
    {
        $qb = $this->createQueryBuilderFromFilter(new UserFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('u.username', ':username')
            )
            ->setParameter('username', $username)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByRecoverToken(RecoverToken $recoverToken): ?User
    {
        $qb = $this->createQueryBuilderFromFilter(new UserFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('u.recoverToken.token', ':token')
            )
            ->setParameter('token', $recoverToken->token())
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByAccountId(AccountId $accountId): array
    {
        $qb = $this->createQueryBuilderFromFilter(new UserFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('u.accountId', ':accountId')
            )
            ->setParameter('accountId', $accountId)
            ->getQuery()
            ->getResult()
        ;
    }

    private function createQueryBuilderFromFilter(UserFilter $filter): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('u, a')
            ->from(User::class, 'u')
            ->leftJoin('u.account', 'a')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('u.removed'),
                    $qb->expr()->eq('u.removed', $qb->expr()->literal(false))
                )
            )
        ;

        if (!empty($filter->username)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('u.username', ':username')
                )
                ->setParameter('username', "%{$filter->username}%")
            ;
        }

        if (null !== $filter->role) {
            $qb
                ->andWhere(
                    $qb->expr()->like('u.roles', ':role')
                )
                ->setParameter('role', "%" . $filter->role->value . "%")
            ;
        }

        if (null !== $filter->account) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('u.accountId', ':accountId')
                )
                ->setParameter('accountId', $filter->account->id())
            ;
        }

        $way = $filter->orderWay == 'ASC' ? 'ASC' : 'DESC';

        switch ($filter->orderBy) {
            case 'registeredAt':
                $qb->orderBy('u.registeredAt', $way);
                break;
            case 'username':
            default:
                $qb->orderBy('u.username', $way);        
                break;
        }

        return $qb;
    }

    public function findByFilter(UserFilter $filter): array
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

    public function totalResultsByFilter(UserFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return $qb->select('COUNT(DISTINCT u.username)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getEntityManager()
    {
        return $this->manager;
    }
}