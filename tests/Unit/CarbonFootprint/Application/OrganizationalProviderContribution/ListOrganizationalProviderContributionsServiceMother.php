<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\List\ListOrganizationalProviderContributionsService;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\ProviderContributionEmissionsCalculator;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepository;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepositoryMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;

class ListOrganizationalProviderContributionsServiceMother
{
    public static function create(
        ?PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository = null,
        ?CompanyCarbonFootprintRepository $companyCarbonFootprintRepository = null,
        ?OrganizationalProviderContributionRepository $contributionRepository = null,
        ?ProviderRepository $providerRepository = null,
        ?PropertyRepository $propertyRepository = null,
        ?ActivityRepository $activityRepository = null,
    ): ListOrganizationalProviderContributionsService {
        return new ListOrganizationalProviderContributionsService(
            OrganizationalCarbonFootprintLocatorMother::create($propertyCarbonFootprintRepository, $companyCarbonFootprintRepository),
            $contributionRepository ?? OrganizationalProviderContributionRepositoryMother::inMemory(),
            $providerRepository ?? ProviderRepositoryMother::inMemory(),
            $propertyRepository ?? PropertyRepositoryMother::inMemory(),
            new ProviderContributionEmissionsCalculator($activityRepository ?? ActivityRepositoryMother::inMemory()),
        );
    }
}
