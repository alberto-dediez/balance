<?php

namespace App\Security\Infrastructure\Symfony\Twig;

use Twig\TwigFilter;
use Twig\Extension\AbstractExtension;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;

class AccountExtension extends AbstractExtension
{
    private array $accounts = [];

    public function __construct(private AccountRepository $accountRepository)
    {

    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('account_name', [$this, 'accountName']),
            new TwigFilter('account_id', [$this, 'accountId']),
        ];
    }

    public function getFunctions(): array
    {
        return [

        ];
    }

    public function accountId($accountId): AccountId
    {
        return new AccountId((string) $accountId);
    }

    public function accountName(AccountId $accountId): ?Account
    {
        if (!isset($this->accounts[(string) $accountId])) {
            $this->accounts[(string) $accountId] = $this->accountRepository->find($accountId);
        }

        return $this->accounts[(string) $accountId];
    }
}
