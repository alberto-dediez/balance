<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\Persistence\InMemory\Repository;

use App\DataExchange\Domain\Model\ImportationProfile\ImportationProfile;
use App\DataExchange\Domain\Model\ImportationProfile\ImportationProfileId;
use App\DataExchange\Domain\Model\ImportationProfile\ImportationProfileRepository;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryImportationProfileRepository implements ImportationProfileRepository
{
    /** @param array<string, ImportationProfile> $importationProfiles */
    public function __construct(private array $importationProfiles = []) {}

    public function add(ImportationProfile $importationProfile): void
    {
        $this->importationProfiles[(string) $importationProfile->id()] = $importationProfile;
    }

    public function find(ImportationProfileId $importationProfileId): ?ImportationProfile
    {
        return $this->importationProfiles[(string) $importationProfileId] ?? null;
    }

    public function findByTypeAndAccount(string $type, ?AccountId $accountId): ?ImportationProfile
    {
        foreach ($this->importationProfiles as $importationProfile) {
            if ($importationProfile->type() !== $type) {
                continue;
            }

            if (null === $accountId && null === $importationProfile->accountId()) {
                return $importationProfile;
            }

            if (null !== $accountId && $importationProfile->accountId() instanceof AccountId && $importationProfile->accountId()->equals($accountId)) {
                return $importationProfile;
            }
        }

        return null;
    }

    public function remove(ImportationProfile $importationProfile): void
    {
        unset($this->importationProfiles[(string) $importationProfile->id()]);
    }
}
