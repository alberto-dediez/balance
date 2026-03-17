<?php

namespace App\Security\Infrastructure\Symfony\Twig;

use Twig\TwigFilter;
use Twig\Extension\AbstractExtension;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use Twig\TwigFunction;

class AccountExtension extends AbstractExtension
{
    /**
     * @var Account[] $accounts
     */
    private array $accounts = [];

    public function __construct(private readonly AccountRepository $accountRepository)
    {

    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('account_name', $this->accountName(...)),
            new TwigFilter('account_id', $this->accountId(...)),
        ];
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [

        ];
    }

    public function accountId(mixed $accountId): AccountId
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
