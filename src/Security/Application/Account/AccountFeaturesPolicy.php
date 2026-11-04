<?php

declare(strict_types=1);

namespace App\Security\Application\Account;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountFeature;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;

final class AccountFeaturesPolicy
{
    /** @var array<string, ?AccountFeatures> */
    private array $features = [];

    public function __construct(private readonly AccountRepository $accountRepository) {}

    public function isEnabled(AccountFeature $feature, ?AccountId $accountId): bool
    {
        if (!$accountId instanceof AccountId) {
            return false;
        }

        $features = $this->featuresOf($accountId);

        if (!$features instanceof AccountFeatures) {
            return false;
        }

        return $features->isEnabled($feature);
    }

    public function reset(): void
    {
        $this->features = [];
    }

    private function featuresOf(AccountId $accountId): ?AccountFeatures
    {
        $key = (string) $accountId;

        if (!array_key_exists($key, $this->features)) {
            $account = $this->accountRepository->find($accountId);
            $this->features[$key] = $account instanceof Account ? $account->features() : null;
        }

        return $this->features[$key];
    }
}
