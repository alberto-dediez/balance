<?php

declare(strict_types=1);

namespace App\Bioscore\Domain\Model\BioscoreConsumption;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;

final readonly class BioscoreConsumptionsQuery
{
    public const int MAX_PROPERTIES = 100;

    /** @param list<PropertyId> $propertyIds */
    public function __construct(
        public AccountId $accountId,
        public array $propertyIds,
        public int $year,
    ) {
        if ([] === $propertyIds) {
            throw new \InvalidArgumentException('At least one property is required to query Bioscore consumptions.');
        }

        if (count($propertyIds) > self::MAX_PROPERTIES) {
            throw new \InvalidArgumentException(sprintf('Bioscore consumptions can be queried for at most %d properties at once.', self::MAX_PROPERTIES));
        }
    }
}
