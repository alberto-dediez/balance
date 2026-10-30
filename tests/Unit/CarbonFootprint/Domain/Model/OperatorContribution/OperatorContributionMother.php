<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OperatorContribution;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContribution;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\Consent\ConsentProof;
use App\Shared\Domain\Model\Locale\Locale;

class OperatorContributionMother
{
    public static function pending(
        string $companyCarbonFootprintId = 'company-footprint-id',
        string $propertyId = 'property-id',
        string $accountId = 'account-id',
        Source $source = Source::Franchises,
        ?string $id = null,
        ?string $token = null,
        ?string $userId = null,
        string $contactEmail = 'operador@hotel.es',
        ?string $emissionFactorGroupId = null,
        int $year = 2025,
    ): OperatorContribution {
        $contribution = new OperatorContribution(
            id: null !== $id ? new OperatorContributionId($id) : OperatorContributionId::generate(),
        );
        $contribution->register(
            companyCarbonFootprintId: new CompanyCarbonFootprintId($companyCarbonFootprintId),
            accountId: new AccountId($accountId),
            propertyId: new PropertyId($propertyId),
            source: $source,
            year: $year,
            emissionFactorGroupId: null !== $emissionFactorGroupId ? new EmissionFactorGroupId($emissionFactorGroupId) : null,
            contactEmail: $contactEmail,
            contactName: null,
            contactLocale: Locale::Spanish,
            token: $token ?? OperatorContribution::generateToken(),
        );

        if (null !== $userId) {
            $contribution->linkUser(new UserId($userId));
        }

        return $contribution;
    }

    public static function withConsent(OperatorContribution $contribution): OperatorContribution
    {
        $contribution->grantConsent(new ConsentProof(
            consentedAt: new \DateTimeImmutable(),
            policyVersion: 'v1',
            locale: 'es',
        ));

        return $contribution;
    }

    public static function answered(
        string $companyCarbonFootprintId = 'company-footprint-id',
        string $propertyId = 'property-id',
        string $accountId = 'account-id',
        ?string $id = null,
    ): OperatorContribution {
        $contribution = self::pending(companyCarbonFootprintId: $companyCarbonFootprintId, propertyId: $propertyId, accountId: $accountId, id: $id);
        $contribution->answer();

        return $contribution;
    }

    public static function ignored(
        string $companyCarbonFootprintId = 'company-footprint-id',
        string $propertyId = 'property-id',
        string $accountId = 'account-id',
        ?string $id = null,
    ): OperatorContribution {
        $contribution = self::pending(companyCarbonFootprintId: $companyCarbonFootprintId, propertyId: $propertyId, accountId: $accountId, id: $id);
        $contribution->ignore();

        return $contribution;
    }

    public static function precalculated(
        string $companyCarbonFootprintId = 'company-footprint-id',
        string $propertyId = 'property-id',
        string $accountId = 'account-id',
        ?string $id = null,
    ): OperatorContribution {
        $contribution = self::pending(companyCarbonFootprintId: $companyCarbonFootprintId, propertyId: $propertyId, accountId: $accountId, id: $id);
        $contribution->markAsPrecalculated();

        return $contribution;
    }

    /** @param list<ActivityId> $activityIds */
    public static function withActivities(array $activityIds, Scope $scope = Scope::Scope1, Source $source = Source::FixedFacilities): OperatorContribution
    {
        $contribution = self::pending();

        foreach ($activityIds as $activityId) {
            $contribution->addActivity($activityId, new ActivityPlacement($scope, $source));
        }

        return $contribution;
    }
}
