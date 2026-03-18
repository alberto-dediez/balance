<?php

namespace App\Security\Infrastructure\Persistence\Doctrine\Repository;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\DuplicatedUsernameException;
use App\Security\Domain\Model\User\RecoverToken;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserFilter;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.user', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineUserRepository implements UserRepository
{
    public function __construct(private readonly EntityManagerInterface $manager, private readonly DoctrineAccountRepository $accountRepository)
    {

    }

    public function add(User $user): void
    {
        $savedUser = $this->find($user->id());

        if (!$savedUser instanceof User && $this->findByUsername($user->username()) instanceof User) {
            throw new DuplicatedUsernameException($user->username());
        }

        if (null == $user->account() && $user->accountId() instanceof AccountId) {
            $account = $this->accountRepository->find($user->accountId());

            $user = new User(
                id: $user->id(),
                username: $user->username(),
                password: $user->password(),
                accountId: $user->accountId(),
                account: $account,
                roles: $user->roles(),
                events: $user->recordedEvents(),
                removed: $user->isRemoved(),
                registeredAt: $user->registeredAt() ?? new \DateTimeImmutable(),
                removedAt: $user->removedAt()
            );
        }

        $this->manager->persist($user);
        $this->manager->flush();
    }

    public function remove(User $user): void
    {
        $this->manager->remove($user);
        $this->manager->flush();
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

    /**
     * @return list<User>
     */
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
        $qb = $this->manager->createQueryBuilder();

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

        if (!in_array($filter->username, [null, '', '0'], true)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('u.username', ':username')
                )
                ->setParameter('username', "%{$filter->username}%")
            ;
        }

        if ($filter->role instanceof Role) {
            $qb
                ->andWhere(
                    $qb->expr()->like('u.roles', ':role')
                )
                ->setParameter('role', "%" . $filter->role->value . "%")
            ;
        }

        if ($filter->account instanceof Account) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('u.accountId', ':accountId')
                )
                ->setParameter('accountId', $filter->account->id())
            ;
        }

        $way = $filter->orderWay === 'ASC' ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('u.registeredAt', $way),
            default => $qb->orderBy('u.username', $way),
        };

        return $qb;
    }

    /**
     * @return list<User>
     */
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

        $count = $qb->select('COUNT(DISTINCT u.username)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return intval($count);
    }
}
