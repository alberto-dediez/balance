<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bioscore\Domain\Model\BioscoreConsumption;

use App\Bioscore\Domain\Model\BioscoreConsumption\BioscoreConsumptionsQuery;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use PHPUnit\Framework\TestCase;

class BioscoreConsumptionsQueryTest extends TestCase
{
    public function testRejectsAnEmptyPropertyList(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new BioscoreConsumptionsQuery(new AccountId('account-id'), [], 2025);
    }

    public function testRejectsMoreThanTheMaximumProperties(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new BioscoreConsumptionsQuery(new AccountId('account-id'), $this->propertyIds(BioscoreConsumptionsQuery::MAX_PROPERTIES + 1), 2025);
    }

    public function testAcceptsTheMaximumProperties(): void
    {
        $query = new BioscoreConsumptionsQuery(new AccountId('account-id'), $this->propertyIds(BioscoreConsumptionsQuery::MAX_PROPERTIES), 2025);

        $this->assertCount(BioscoreConsumptionsQuery::MAX_PROPERTIES, $query->propertyIds);
        $this->assertSame(2025, $query->year);
    }

    /** @return list<PropertyId> */
    private function propertyIds(int $count): array
    {
        $ids = [];

        for ($i = 0; $i < $count; ++$i) {
            $ids[] = PropertyId::generate();
        }

        return $ids;
    }
}
