<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\Property;

use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyScope;
use App\Security\Domain\Model\Account\AccountId;
use PHPUnit\Framework\TestCase;

class PropertyScopeTest extends TestCase
{
    public function testMergeWithoutDuplicates(): void
    {
        $accountId = AccountId::generate();
        $otherAccountId = AccountId::generate();
        $propertyId = PropertyId::generate();

        $scope = PropertyScope::forAccount($accountId)
            ->merge(new PropertyScope(accountIds: [new AccountId((string) $accountId), $otherAccountId], propertyIds: [$propertyId]))
            ->merge(new PropertyScope(propertyIds: [new PropertyId((string) $propertyId)]))
        ;

        $this->assertSame([(string) $accountId, (string) $otherAccountId], array_map('strval', $scope->accountIds));
        $this->assertSame([(string) $propertyId], array_map('strval', $scope->propertyIds));
    }

    public function testIsEmpty(): void
    {
        $this->assertTrue((new PropertyScope())->isEmpty());
        $this->assertFalse(PropertyScope::forAccount(AccountId::generate())->isEmpty());
        $this->assertFalse((new PropertyScope(propertyIds: [PropertyId::generate()]))->isEmpty());
    }
}
