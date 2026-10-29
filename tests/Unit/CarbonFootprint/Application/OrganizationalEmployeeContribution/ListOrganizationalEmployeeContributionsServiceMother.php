<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\EmployeeContributionEmissionsCalculator;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\List\ListOrganizationalEmployeeContributionsService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipationRepositoryMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;

class ListOrganizationalEmployeeContributionsServiceMother
{
    public static function create(
        ?PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository = null,
        ?CompanyCarbonFootprintRepository $companyCarbonFootprintRepository = null,
        ?PropertyRepository $propertyRepository = null,
        ?ActivityRepository $activityRepository = null,
    ): ListOrganizationalEmployeeContributionsService {
        return new ListOrganizationalEmployeeContributionsService(
            OrganizationalCarbonFootprintLocatorMother::create($propertyCarbonFootprintRepository, $companyCarbonFootprintRepository),
            OrganizationalEmployeeParticipationRepositoryMother::inMemory(),
            OrganizationalEmployeeContributionRepositoryMother::inMemory(),
            EmployeeRepositoryMother::inMemory(),
            $propertyRepository ?? PropertyRepositoryMother::inMemory(),
            new EmployeeContributionEmissionsCalculator($activityRepository ?? ActivityRepositoryMother::inMemory()),
        );
    }
}
