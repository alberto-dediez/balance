<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Twig;

use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountFeature;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AccountExtension extends AbstractExtension
{
    /**
     * @var array<string, ?Account>
     */
    private array $accounts = [];

    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly AccountFeaturesPolicy $accountFeaturesPolicy,
        private readonly CurrentAccountIdProvider $currentAccountIdProvider,
    ) {}

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
            new TwigFunction('account_feature_enabled', $this->isFeatureEnabled(...)),
        ];
    }

    public function isFeatureEnabled(string $feature): bool
    {
        return $this->accountFeaturesPolicy->isEnabled(AccountFeature::from($feature), $this->currentAccountIdProvider->currentAccountId());
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
