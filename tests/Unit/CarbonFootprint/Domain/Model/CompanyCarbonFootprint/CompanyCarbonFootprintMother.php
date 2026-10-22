<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;

class CompanyCarbonFootprintMother
{
    public static function registered(): CompanyCarbonFootprint
    {
        return self::from(
            companyId: (string) CompanyId::generate(),
            id: (string) CompanyCarbonFootprintId::generate(),
        );
    }

    public static function fromCompanyIdAndAccount(string $companyId, string $accountId): CompanyCarbonFootprint
    {
        return self::from(
            companyId: $companyId,
            id: (string) CompanyCarbonFootprintId::generate(),
            accountId: $accountId,
        );
    }

    public static function from(
        string $companyId,
        string $id,
        string $accountId = 'account-id',
        int $year = 2025,
        ?EmissionFactorGroupId $emissionFactorGroupId = null,
    ): CompanyCarbonFootprint {
        $companyCarbonFootprint = new CompanyCarbonFootprint(
            id: new CompanyCarbonFootprintId($id),
        );
        $companyCarbonFootprint->register(
            companyId: new CompanyId($companyId),
            accountId: new AccountId($accountId),
            emissionFactorGroupId: $emissionFactorGroupId ?? EmissionFactorGroupMother::default()->id(),
            year: $year,
        );

        return $companyCarbonFootprint;
    }

    public static function withScope1Answered(
        string $companyId,
        string $id,
        string $accountId = 'account-id',
        int $year = 2025,
        ?EmissionFactorGroupId $emissionFactorGroupId = null,
    ): CompanyCarbonFootprint {
        $companyCarbonFootprint = self::from($companyId, $id, $accountId, $year, $emissionFactorGroupId);
        $companyCarbonFootprint->answerScope(Scope::Scope1);

        return $companyCarbonFootprint;
    }

    public static function withScope2Answered(
        string $companyId,
        string $id,
        string $accountId = 'account-id',
        int $year = 2025,
        ?EmissionFactorGroupId $emissionFactorGroupId = null,
    ): CompanyCarbonFootprint {
        $companyCarbonFootprint = self::withScope1Answered($companyId, $id, $accountId, $year, $emissionFactorGroupId);
        $companyCarbonFootprint->answerScope(Scope::Scope2);

        return $companyCarbonFootprint;
    }

    public static function withScope3Answered(
        string $companyId,
        string $id,
        string $accountId = 'account-id',
        int $year = 2025,
        ?EmissionFactorGroupId $emissionFactorGroupId = null,
    ): CompanyCarbonFootprint {
        $companyCarbonFootprint = self::withScope2Answered($companyId, $id, $accountId, $year, $emissionFactorGroupId);
        $companyCarbonFootprint->answerScope(Scope::Scope3);

        return $companyCarbonFootprint;
    }

    public static function withScope3Excluded(
        string $companyId,
        string $id,
        string $accountId = 'account-id',
        int $year = 2025,
        ?EmissionFactorGroupId $emissionFactorGroupId = null,
        ExclusionReason $reason = ExclusionReason::NoDataAvailable,
        ?string $comment = null,
    ): CompanyCarbonFootprint {
        $companyCarbonFootprint = self::withScope2Answered($companyId, $id, $accountId, $year, $emissionFactorGroupId);
        $companyCarbonFootprint->excludeStage(Scope::Scope3, $reason, $comment);
        $companyCarbonFootprint->answerScope(Scope::Scope3);

        return $companyCarbonFootprint;
    }

    public static function withNonConsolidatedProperty(
        PropertyId $propertyId,
        Source $source,
        Emissions $calculated,
        ?string $manual = null,
        string $companyId = 'company-id',
        string $id = 'company-carbon-footprint-id',
        string $accountId = 'account-id',
        int $year = 2025,
    ): CompanyCarbonFootprint {
        $companyCarbonFootprint = self::withScope3Answered($companyId, $id, $accountId, $year);
        $companyCarbonFootprint->recordNonConsolidatedProperty($propertyId, $source, null, $calculated, $manual);

        return $companyCarbonFootprint;
    }

    public static function withMitigationPlanStarted(
        string $companyId,
        string $id,
        string $accountId = 'account-id',
        int $year = 2025,
        ?EmissionFactorGroupId $emissionFactorGroupId = null,
    ): CompanyCarbonFootprint {
        return self::withScope3Answered($companyId, $id, $accountId, $year, $emissionFactorGroupId);
    }
}
