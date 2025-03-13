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
        ];
    }

    public function getFunctions(): array
    {
        return [
            
        ];
    }

    public function accountName(AccountId $accountId): ?Account
    {
        if (!isset($accounts[(string) $accountId])) {
            $accounts[(string) $accountId] = $this->accountRepository->find($accountId);
        }

        return $accounts[(string) $accountId];
    }
}
