<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContribution;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionId;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\ProviderContributionMode;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;

class OrganizationalProviderContributionMother
{
    /** @param list<Source> $sources */
    public static function manual(
        string $carbonFootprintId = 'footprint-id',
        string $providerId = 'provider-id',
        array $sources = [Source::UpstreamTransport],
        CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Property,
        string $accountId = 'account-id',
        ?string $propertyId = null,
        ?string $id = null,
    ): OrganizationalProviderContribution {
        return self::from(
            mode: ProviderContributionMode::Manual,
            carbonFootprintId: $carbonFootprintId,
            providerId: $providerId,
            sources: $sources,
            subjectType: $subjectType,
            accountId: $accountId,
            propertyId: $propertyId,
            id: $id,
        );
    }

    /** @param list<Source> $sources */
    public static function requested(
        string $carbonFootprintId = 'footprint-id',
        string $providerId = 'provider-id',
        array $sources = [Source::UpstreamTransport],
        CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Property,
        string $accountId = 'account-id',
        ?string $propertyId = null,
        ?string $id = null,
        ?string $token = null,
    ): OrganizationalProviderContribution {
        return self::from(
            mode: ProviderContributionMode::Requested,
            carbonFootprintId: $carbonFootprintId,
            providerId: $providerId,
            sources: $sources,
            subjectType: $subjectType,
            accountId: $accountId,
            propertyId: $propertyId,
            id: $id,
            token: $token,
        );
    }

    /** @param list<Source> $sources */
    public static function from(
        ProviderContributionMode $mode,
        string $carbonFootprintId,
        string $providerId,
        array $sources,
        CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Property,
        string $accountId = 'account-id',
        ?string $propertyId = null,
        ?string $id = null,
        ?string $token = null,
    ): OrganizationalProviderContribution {
        $contribution = new OrganizationalProviderContribution(
            id: null !== $id ? new OrganizationalProviderContributionId($id) : OrganizationalProviderContributionId::generate(),
        );
        $contribution->register(
            subjectType: $subjectType,
            carbonFootprintId: $carbonFootprintId,
            accountId: new AccountId($accountId),
            providerId: new ProviderId($providerId),
            propertyId: null !== $propertyId ? new PropertyId($propertyId) : null,
            mode: $mode,
            token: $mode->isRequested() ? ($token ?? OrganizationalProviderContribution::generateToken()) : '',
            sources: $sources,
            companyName: 'Logística Sur',
        );

        return $contribution;
    }
}
