<?php

namespace App\Security\Infrastructure\Repository\User;

use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserFilter;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryUserRepository implements UserRepository
{
    public function __construct(private array $users = [])
    {

    }

    public function add(User $user): void
    {
        if (isset($this->users[(string) $user->id()])) {
            return ;
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
        return (isset($this->users[(string) $userId])) 
            ? $this->users[(string) $userId]
            : null
        ;
    }

    public function findByUsername(string $username): ?User
    {
        if (count($this->users) == 0) {
            return null;
        }

        foreach ($this->users as $user) {
            if ($user->username() == $username) {
                return $user;
            }
        }

        return null;
    }

    public function findByFilter(UserFilter $filter): array
    {
        $results = [];

        if (count($this->users) == 0) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->users as $user) {
            if (!empty($filter->username) && $filter->username != $user->username()) {
                continue;
            }

            if (null !== $filter->role && !in_array($filter->role->value, $user->roles())) {
                continue;
            }

            if (null !== $filter->account && (null == $user->accountId() || false == $user->accountId()->equals($filter->account->id()))) {
                continue;
            }

            if ($offset > 0) {
                $offset--;

                continue;
            }

            if (count($results) >= $filter->limit) {
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