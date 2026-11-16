<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Balance;

use App\Balance\Domain\Model\AccountProject\AccountProjectFilter;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\CreditPurchase\SharedCreditProviders;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Domain\Model\Account\AccountFeature;
use App\Security\Domain\Model\Account\AccountId;

final class CreditSourceSelector
{
    public function __construct(
        private CreditAvailability $creditAvailability,
        private SharedCreditProviders $sharedCreditProviders,
        private AccountProjectRepository $accountProjectRepository,
        private AccountFeaturesPolicy $accountFeaturesPolicy,
    ) {}

    /** @return list<CreditSource> */
    public function candidates(
        AccountId $accountId,
        ProjectId $projectId,
        bool $allowShared,
        ?PropertyId $propertyId,
        \DateTimeImmutable $now,
    ): array {
        $candidates = [];

        if ($this->hasJoined($accountId, $projectId)) {
            $candidates[] = new CreditSource(
                accountId: $accountId,
                availableTonnes: $this->creditAvailability->for($accountId, $projectId, $now)->availableTonnes(),
                shared: false,
            );
        }

        if (!$allowShared) {
            return $candidates;
        }

        foreach ($this->providers($accountId, $propertyId) as $providerId) {
            if (!$this->hasJoined($providerId, $projectId)) {
                continue;
            }

            $availableTonnes = $this->creditAvailability->sharedFor($providerId, $projectId, $now);

            if ($availableTonnes <= 0.0) {
                continue;
            }

            $candidates[] = new CreditSource(
                accountId: $providerId,
                availableTonnes: $availableTonnes,
                shared: true,
            );
        }

        return $candidates;
    }

    public function select(
        AccountId $accountId,
        ProjectId $projectId,
        bool $allowShared,
        ?PropertyId $propertyId,
        ?float $tonnes,
        \DateTimeImmutable $now,
    ): ?CreditSource {
        foreach ($this->candidates($accountId, $projectId, $allowShared, $propertyId, $now) as $candidate) {
            if ($candidate->availableTonnes <= 0.0) {
                continue;
            }

            if (null === $tonnes || $candidate->availableTonnes >= $tonnes) {
                return $candidate;
            }
        }

        return null;
    }

    public function maxAvailableTonnes(
        AccountId $accountId,
        ProjectId $projectId,
        bool $allowShared,
        ?PropertyId $propertyId,
        \DateTimeImmutable $now,
    ): float {
        $maxAvailableTonnes = 0.0;

        foreach ($this->candidates($accountId, $projectId, $allowShared, $propertyId, $now) as $candidate) {
            $maxAvailableTonnes = max($maxAvailableTonnes, $candidate->availableTonnes);
        }

        return $maxAvailableTonnes;
    }

    /** @return list<ProjectId> */
    public function sharedProjectIds(AccountId $accountId, ?PropertyId $propertyId, \DateTimeImmutable $now): array
    {
        $projectIds = [];

        foreach ($this->providers($accountId, $propertyId) as $providerId) {
            $accountProjects = $this->accountProjectRepository->findByFilter(
                new AccountProjectFilter(accountId: $providerId, limit: 0),
            );

            foreach ($accountProjects as $accountProject) {
                $projectId = $accountProject->projectId();

                if (!$projectId instanceof ProjectId || isset($projectIds[(string) $projectId])) {
                    continue;
                }

                if ($this->creditAvailability->sharedFor($providerId, $projectId, $now) > 0.0) {
                    $projectIds[(string) $projectId] = $projectId;
                }
            }
        }

        return array_values($projectIds);
    }

    /** @return list<AccountId> */
    private function providers(AccountId $accountId, ?PropertyId $propertyId): array
    {
        return array_values(array_filter(
            $this->sharedCreditProviders->for($accountId, $propertyId),
            fn(AccountId $providerId): bool => !$providerId->equals($accountId)
                && $this->accountFeaturesPolicy->isEnabled(AccountFeature::Compensation, $providerId),
        ));
    }

    private function hasJoined(AccountId $accountId, ProjectId $projectId): bool
    {
        return [] !== $this->accountProjectRepository->findByFilter(
            new AccountProjectFilter(accountId: $accountId, projectId: $projectId, limit: 1),
        );
    }
}
