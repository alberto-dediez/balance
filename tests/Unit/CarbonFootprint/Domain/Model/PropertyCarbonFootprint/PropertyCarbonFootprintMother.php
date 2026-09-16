<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;

class PropertyCarbonFootprintMother
{
    public static function registered(): PropertyCarbonFootprint
    {
        return self::from(
            propertyId: (string) PropertyId::generate(),
            id: (string) PropertyCarbonFootprintId::generate(),
        );
    }

    public static function fromPropertyIdAndAccount(string $propertyId, string $accountId): PropertyCarbonFootprint
    {
        return self::from(
            propertyId: $propertyId,
            id: (string) PropertyCarbonFootprintId::generate(),
            accountId: $accountId,
        );
    }

    public static function from(
        string $propertyId,
        string $id,
        string $accountId = 'account-id',
        int $year = 2025,
        ?EmissionFactorGroupId $emissionFactorGroupId = null,
    ): PropertyCarbonFootprint {
        $propertyCarbonFootprint = new PropertyCarbonFootprint(
            id: new PropertyCarbonFootprintId($id),
        );
        $propertyCarbonFootprint->register(
            propertyId: new PropertyId($propertyId),
            accountId: new AccountId($accountId),
            emissionFactorGroupId: $emissionFactorGroupId ?? EmissionFactorGroupMother::default()->id(),
            year: $year,
        );

        return $propertyCarbonFootprint;
    }

    public static function withScope1Answered(
        string $propertyId,
        string $id,
        string $accountId = 'account-id',
        int $year = 2025,
        ?EmissionFactorGroupId $emissionFactorGroupId = null,
    ): PropertyCarbonFootprint {
        $propertyCarbonFootprint = self::from($propertyId, $id, $accountId, $year, $emissionFactorGroupId);
        $propertyCarbonFootprint->answerScope(Scope::Scope1);

        return $propertyCarbonFootprint;
    }

    public static function withScope2Answered(
        string $propertyId,
        string $id,
        string $accountId = 'account-id',
        int $year = 2025,
        ?EmissionFactorGroupId $emissionFactorGroupId = null,
    ): PropertyCarbonFootprint {
        $propertyCarbonFootprint = self::withScope1Answered($propertyId, $id, $accountId, $year, $emissionFactorGroupId);
        $propertyCarbonFootprint->answerScope(Scope::Scope2);

        return $propertyCarbonFootprint;
    }
}
