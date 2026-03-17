<?php

namespace App\Security\Infrastructure\Persistence\InMemory\Repository;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\DuplicatedUsernameException;
use App\Security\Domain\Model\User\RecoverToken;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserFilter;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryUserRepository implements UserRepository
{
    /**
     * @param User[] $users
     */
    public function __construct(private array $users = [])
    {

    }

    public function add(User $user): void
    {
        if (isset($this->users[(string) $user->id()])) {
            return ;
        }

        $usernameInUse = array_filter($this->users, fn(User $savedUser): bool => $user->username() === $savedUser->username() && !$savedUser->isRemoved());

        if ($usernameInUse !== []) {
            throw new DuplicatedUsernameException($user->username());
        }

        $this->users[(string) $user->id()] = $user;
    }

    public function remove(User $user): void
    {
        if (!isset($this->users[(string) $user->id()])) {
            return ;
        }

        unset($this->users[(string) $user->id()]);
    }

    public function find(UserId $userId): ?User
    {
        return (isset($this->users[(string) $userId]) && false == $this->users[(string) $userId]->isRemoved())
            ? $this->users[(string) $userId]
            : null
        ;
    }

    public function findByUsername(string $username): ?User
    {
        if (count($this->users) === 0) {
            return null;
        }

        foreach ($this->users as $user) {
            if ($user->username() == $username && !$user->isRemoved()) {
                return $this->find($user->id());
            }
        }

        return null;
    }

    public function findByRecoverToken(RecoverToken $recoverToken): ?User
    {
        if (count($this->users) === 0) {
            return null;
        }

        foreach ($this->users as $user) {
            if ($user->recoverToken()->equals($recoverToken)) {
                return $this->find($user->id());
            }
        }

        return null;
    }

    /**
     * @return list<User>
     */
    public function findByAccountId(AccountId $accountId): array
    {
        $account = new Account(
            id: $accountId
        );

        $filter = new UserFilter(
            account: $account
        );
        $filter->limit = 9999999999999;

        return $this->findByFilter($filter);
    }

    /**
     * @return list<User>
     */
    public function findByFilter(UserFilter $filter): array
    {
        $results = [];

        if (count($this->users) === 0) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->users as $user) {
            if (
                !in_array($filter->username, [null, '', '0'], true)
                && !str_contains(strtolower((string) $user->username()), strtolower($filter->username))
            ) {
                continue;
            }

            if ($filter->role instanceof Role && !in_array($filter->role, $user->roles())) {
                continue;
            }

            if ($filter->account instanceof Account && (null == $user->accountId() || false == $user->accountId()->equals($filter->account->id()))) {
                continue;
            }

            if (true == $user->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                $offset--;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $user;
        }

        return $results;
    }

    public function totalResultsByFilter(UserFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($filter));
    }
}
