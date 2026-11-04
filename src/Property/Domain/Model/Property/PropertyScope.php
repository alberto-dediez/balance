<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Property;

use App\Security\Domain\Model\Account\AccountId;

final readonly class PropertyScope
{
    /**
     * @param list<AccountId>  $accountIds
     * @param list<PropertyId> $propertyIds
     */
    public function __construct(
        public array $accountIds = [],
        public array $propertyIds = [],
    ) {}

    public static function forAccount(AccountId $accountId): self
    {
        return new self(accountIds: [$accountId]);
    }

    public function isEmpty(): bool
    {
        return [] === $this->accountIds && [] === $this->propertyIds;
    }

    public function merge(self $other): self
    {
        return new self(
            accountIds: self::unique([...$this->accountIds, ...$other->accountIds]),
            propertyIds: self::unique([...$this->propertyIds, ...$other->propertyIds]),
        );
    }

    /**
     * @template T of AccountId|PropertyId
     *
     * @param list<T> $ids
     *
     * @return list<T>
     */
    private static function unique(array $ids): array
    {
        $unique = [];

        foreach ($ids as $id) {
            $unique[(string) $id] = $id;
        }

        return array_values($unique);
    }
}
