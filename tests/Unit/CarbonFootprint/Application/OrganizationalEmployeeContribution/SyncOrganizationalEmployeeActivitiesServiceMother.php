<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Answer\ActivityAnswerHandlerRegistry;
use App\CarbonFootprint\Application\Activity\Answer\MobilityAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\OvernightStayAnswerHandler;
use App\CarbonFootprint\Application\Activity\MobilityEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\OvernightStayEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterMobilityActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterOvernightStayActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateMobilityActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateOvernightStayActivityService;
use App\CarbonFootprint\Application\CarbonFootprint\CarbonFootprintCountryResolver;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersSynchronizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Application\Mobility\MobilityFactorBasisProvider;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Sync\SyncOrganizationalEmployeeActivitiesService;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocator;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeContributionsAggregator;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMobilityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOrganizationalEmployeeContributionRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOrganizationalEmployeeParticipationRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOvernightStayRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipationRepositoryMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;

class SyncOrganizationalEmployeeActivitiesServiceMother
{
    public static function create(
        ?OrganizationalCarbonFootprintLocator $locator = null,
        ?InMemoryOrganizationalEmployeeParticipationRepository $participationRepository = null,
        ?InMemoryOrganizationalEmployeeContributionRepository $contributionRepository = null,
        ?InMemoryActivityRepository $activityRepository = null,
        ?InMemoryMobilityRepository $mobilityRepository = null,
        ?InMemoryOvernightStayRepository $overnightStayRepository = null,
        ?InMemoryPropertyRepository $propertyRepository = null,
    ): SyncOrganizationalEmployeeActivitiesService {
        $activityRepository ??= ActivityRepositoryMother::inMemory();
        $mobilityRepository ??= MobilityRepositoryMother::inMemory();
        $overnightStayRepository ??= new InMemoryOvernightStayRepository();
        $propertyRepository ??= PropertyRepositoryMother::inMemory();

        $authorizer = new ActivityAuthorizer();
        $calculator = EmissionsCalculatorMother::create();
        $mobilitySelector = new MobilityEmissionFactorSelector($mobilityRepository);
        $overnightStaySelector = new OvernightStayEmissionFactorSelector($overnightStayRepository);

        $handlers = new ActivityAnswerHandlerRegistry([
            new MobilityAnswerHandler(
                new RegisterMobilityActivityService($activityRepository, $authorizer, $mobilitySelector, $calculator),
                new UpdateMobilityActivityService($activityRepository, $authorizer, $mobilitySelector, $calculator),
            ),
            new OvernightStayAnswerHandler(
                new RegisterOvernightStayActivityService($activityRepository, $authorizer, $overnightStaySelector, $calculator),
                new UpdateOvernightStayActivityService($activityRepository, $authorizer, $overnightStaySelector, $calculator),
            ),
        ]);

        return new SyncOrganizationalEmployeeActivitiesService(
            $locator ?? OrganizationalCarbonFootprintLocatorMother::create(),
            $participationRepository ?? OrganizationalEmployeeParticipationRepositoryMother::inMemory(),
            $contributionRepository ?? OrganizationalEmployeeContributionRepositoryMother::inMemory(),
            new OrganizationalEmployeeContributionsAggregator(),
            new MobilityFactorBasisProvider($mobilityRepository),
            $mobilityRepository,
            $overnightStayRepository,
            new CompanyProperties($propertyRepository),
            new StageAnswersSynchronizer($handlers, new CarbonFootprintCountryResolver(EventRepositoryMother::inMemory(), $propertyRepository)),
        );
    }
}
