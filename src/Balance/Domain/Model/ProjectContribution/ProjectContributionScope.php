<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ProjectContribution;

use App\Security\Domain\Model\Account\AccountId;

final readonly class ProjectContributionScope
{
    public const array PROPERTY_ORIGINS = [
        ProjectContributionOrigin::Property,
        ProjectContributionOrigin::PropertyTripCalculation,
    ];

    /**
     * @param list<AccountId> $accountIds
     * @param list<AccountId> $propertyAccountIds
     * @param list<string>    $propertyIds
     * @param list<string>    $propertyTripCalculationIds
     */
    public function __construct(
        public array $accountIds = [],
        public array $propertyAccountIds = [],
        public array $propertyIds = [],
        public array $propertyTripCalculationIds = [],
    ) {}

    public static function forAccount(AccountId $accountId): self
    {
        return new self(accountIds: [$accountId]);
    }

    public function isEmpty(): bool
    {
        return [] === $this->accountIds
            && [] === $this->propertyAccountIds
            && [] === $this->propertyIds
            && [] === $this->propertyTripCalculationIds;
    }

    public function merge(self $other): self
    {
        return new self(
            accountIds: self::uniqueAccountIds([...$this->accountIds, ...$other->accountIds]),
            propertyAccountIds: self::uniqueAccountIds([...$this->propertyAccountIds, ...$other->propertyAccountIds]),
            propertyIds: array_values(array_unique([...$this->propertyIds, ...$other->propertyIds])),
            propertyTripCalculationIds: array_values(array_unique([...$this->propertyTripCalculationIds, ...$other->propertyTripCalculationIds])),
        );
    }

    public function includes(ProjectContribution $projectContribution): bool
    {
        $accountId = $projectContribution->accountId();
        $origin = $projectContribution->origin();
        $originId = $projectContribution->originId();

        if ($accountId instanceof AccountId && self::containsAccountId($this->accountIds, $accountId)) {
            return true;
        }

        if ($accountId instanceof AccountId
            && in_array($origin, self::PROPERTY_ORIGINS, true)
            && self::containsAccountId($this->propertyAccountIds, $accountId)
        ) {
            return true;
        }

        if (null === $originId) {
            return false;
        }

        return match ($origin) {
            ProjectContributionOrigin::Property => in_array($originId, $this->propertyIds, true),
            ProjectContributionOrigin::PropertyTripCalculation => in_array($originId, $this->propertyTripCalculationIds, true),
            default => false,
        };
    }

    /** @param list<AccountId> $accountIds */
    private static function containsAccountId(array $accountIds, AccountId $accountId): bool
    {
        foreach ($accountIds as $candidate) {
            if ($candidate->equals($accountId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<AccountId> $accountIds
     *
     * @return list<AccountId>
     */
    private static function uniqueAccountIds(array $accountIds): array
    {
        $unique = [];

        foreach ($accountIds as $accountId) {
            $unique[(string) $accountId] = $accountId;
        }

        return array_values($unique);
    }
}
