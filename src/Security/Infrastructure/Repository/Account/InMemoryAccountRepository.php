<?php

namespace App\Security\Infrastructure\Repository\Account;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryAccountRepository implements AccountRepository
{
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
        return (isset($this->accounts[(string) $accountId])) 
            ? $this->accounts[(string) $accountId]
            : null
        ;
    }

    public function findByName(string $name): ?Account
    {
        if (count($this->accounts) == 0) {
            return null;
        }

        foreach ($this->accounts as $account) {
            if ($account->name() == $name) {
                return $account;
            }
        }

        return null;
    }
}