<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocator;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;

class OrganizationalCarbonFootprintLocatorMother
{
    public static function create(
        ?PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository = null,
        ?CompanyCarbonFootprintRepository $companyCarbonFootprintRepository = null,
    ): OrganizationalCarbonFootprintLocator {
        return new OrganizationalCarbonFootprintLocator(
            $propertyCarbonFootprintRepository ?? PropertyCarbonFootprintRepositoryMother::inMemory(),
            $companyCarbonFootprintRepository ?? CompanyCarbonFootprintRepositoryMother::inMemory(),
            new PropertyCarbonFootprintAuthorizer(),
            new CompanyCarbonFootprintAuthorizer(),
        );
    }
}
