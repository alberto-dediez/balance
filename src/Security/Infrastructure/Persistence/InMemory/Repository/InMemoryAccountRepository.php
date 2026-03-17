<?php

namespace App\Security\Infrastructure\Persistence\InMemory\Repository;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountFilter;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryAccountRepository implements AccountRepository
{
    /**
     * @param Account[] $accounts
     */
    public function __construct(private array $accounts = [])
    {

    }

    public function add(Account $account): void
    {
        if (isset($this->accounts[(string) $account->id()])) {
            return ;
        }

        $this->accounts[(string) $account->id()] = $account;
    }

    public function remove(Account $account): void
    {
        if (!isset($this->accounts[(string) $account->id()])) {
            return ;
        }

        unset($this->accounts[(string) $account->id()]);
    }

    public function find(AccountId $accountId): ?Account
    {
        return (isset($this->accounts[(string) $accountId]) && false == $this->accounts[(string) $accountId]->isRemoved())
            ? $this->accounts[(string) $accountId]
            : null
        ;
    }

    public function findByName(string $name): ?Account
    {
        if (count($this->accounts) === 0) {
            return null;
        }

        foreach ($this->accounts as $account) {
            if ($account->name() == $name) {
                return $this->find($account->id());
            }
        }

        return null;
    }

    /**
     * @return list<Account>
     */
    public function findByFilter(AccountFilter $filter): array
    {
        $results = [];

        if (count($this->accounts) === 0) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->accounts as $account) {
            if (
                !in_array($filter->name, [null, '', '0'], true)
                && !str_contains(strtolower((string) $account->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (null !== $filter->active && $filter->active !== $account->isActive()) {
                continue;
            }

            if (true == $account->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                $offset--;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $account;
        }

        return $results;
    }

    public function totalResultsByFilter(AccountFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($filter));
    }
}
