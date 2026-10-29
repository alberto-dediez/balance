<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MitigationAction;

use App\CarbonFootprint\Application\Activity\Catalog\ActivityCatalogProviderRegistry;
use App\CarbonFootprint\Application\Activity\Catalog\CombustibleCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\ElectricityCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\MobilityCatalogProvider;
use App\CarbonFootprint\Application\CarbonFootprint\DerivedSourceEmissionsCalculator;
use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportSubjects;
use App\CarbonFootprint\Application\CarbonFootprintReport\Data\ActivityItemNameResolver;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\Reopen\ReopenCompanyCarbonFootprintService;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\EventCarbonFootprint\Reopen\ReopenEventCarbonFootprintService;
use App\CarbonFootprint\Application\MitigationAction\GetPlan\GetMitigationPlanService;
use App\CarbonFootprint\Application\MitigationAction\Import\ImportMitigationActionsService;
use App\CarbonFootprint\Application\MitigationAction\MitigationActionAuthorizer;
use App\CarbonFootprint\Application\MitigationAction\MitigationEmissionSourcesBuilder;
use App\CarbonFootprint\Application\MitigationAction\MitigationPlanFootprints;
use App\CarbonFootprint\Application\MitigationAction\Register\RegisterMitigationActionService;
use App\CarbonFootprint\Application\MitigationAction\Remove\RemoveMitigationActionService;
use App\CarbonFootprint\Application\MitigationAction\RemoveOfCarbonFootprint\RemoveMitigationActionsOfCarbonFootprintService;
use App\CarbonFootprint\Application\MitigationAction\Update\UpdateMitigationActionService;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\Reopen\ReopenPropertyCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\CombustibleActivity;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivity;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMitigationActionRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMitigationMeasureRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOrganizationalProviderContributionRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryProviderRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\CountedActivityReferencesMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;

trait MitigationPlanContextTrait
{
    private InMemoryActivityRepository $activities;
    private InMemoryPropertyCarbonFootprintRepository $propertyCarbonFootprints;
    private InMemoryCompanyCarbonFootprintRepository $companyCarbonFootprints;
    private InMemoryMitigationActionRepository $mitigationActions;
    private InMemoryMitigationMeasureRepository $mitigationMeasures;
    private CombustibleId $naturalGasId;
    private ElectricityMarketerId $marketerId;
    private MitigationEmissionSourcesBuilder $emissionSourcesBuilder;
    private InMemoryPropertyRepository $properties;
    private InMemoryProviderRepository $providers;
    private InMemoryOrganizationalProviderContributionRepository $providerContributions;

    private function initMitigationPlan(): void
    {
        $this->activities = ActivityRepositoryMother::inMemory();
        $this->propertyCarbonFootprints = new InMemoryPropertyCarbonFootprintRepository();
        $this->companyCarbonFootprints = new InMemoryCompanyCarbonFootprintRepository();
        $this->mitigationActions = new InMemoryMitigationActionRepository();
        $this->mitigationMeasures = new InMemoryMitigationMeasureRepository();
        $this->naturalGasId = CombustibleId::generate();
        $this->marketerId = ElectricityMarketerId::generate();
        $this->properties = PropertyRepositoryMother::inMemory();
        $this->providers = ProviderRepositoryMother::inMemory();
        $this->providerContributions = OrganizationalProviderContributionRepositoryMother::inMemory();
        $this->emissionSourcesBuilder = new MitigationEmissionSourcesBuilder(
            $this->activities,
            new ActivityItemNameResolver(
                new ActivityCatalogProviderRegistry([
                    new CombustibleCatalogProvider(CombustibleRepositoryMother::inMemory()),
                    new ElectricityCatalogProvider(ElectricityMarketerRepositoryMother::inMemory(), GdOFactorRepositoryMother::inMemory()),
                    new MobilityCatalogProvider(MobilityRepositoryMother::inMemory()),
                ]),
                new FakeTranslator(),
            ),
            new DerivedSourceEmissionsCalculator($this->activities, CountedActivityReferencesMother::create($this->providerContributions)),
            $this->properties,
            $this->providers,
            new FakeTranslator(),
            CountedActivityReferencesMother::create($this->providerContributions),
        );
    }

    private function owner(string $accountId = 'account-id'): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner-' . $accountId, Role::Owner, new AccountId($accountId));
    }

    private function footprints(): MitigationPlanFootprints
    {
        $eventCarbonFootprints = new InMemoryEventCarbonFootprintRepository();

        return new MitigationPlanFootprints(
            new CarbonFootprintReportSubjects(
                $eventCarbonFootprints,
                $this->propertyCarbonFootprints,
                $this->companyCarbonFootprints,
                new ReopenEventCarbonFootprintService($eventCarbonFootprints, new EventCarbonFootprintAuthorizer()),
                new ReopenPropertyCarbonFootprintService($this->propertyCarbonFootprints, new PropertyCarbonFootprintAuthorizer()),
                new ReopenCompanyCarbonFootprintService($this->companyCarbonFootprints, new CompanyCarbonFootprintAuthorizer()),
            ),
            $this->propertyCarbonFootprints,
            $this->companyCarbonFootprints,
            new PropertyCarbonFootprintAuthorizer(),
            new CompanyCarbonFootprintAuthorizer(),
        );
    }

    private function emissionSourcesBuilder(): MitigationEmissionSourcesBuilder
    {
        return $this->emissionSourcesBuilder;
    }

    private function registerService(): RegisterMitigationActionService
    {
        return new RegisterMitigationActionService($this->mitigationActions, $this->mitigationMeasures, $this->footprints(), $this->emissionSourcesBuilder());
    }

    private function updateService(): UpdateMitigationActionService
    {
        return new UpdateMitigationActionService($this->mitigationActions, new MitigationActionAuthorizer(), $this->footprints(), $this->emissionSourcesBuilder());
    }

    private function removeService(): RemoveMitigationActionService
    {
        return new RemoveMitigationActionService($this->mitigationActions, new MitigationActionAuthorizer(), $this->footprints());
    }

    private function removeOfCarbonFootprintService(): RemoveMitigationActionsOfCarbonFootprintService
    {
        return new RemoveMitigationActionsOfCarbonFootprintService($this->mitigationActions, new MitigationActionAuthorizer());
    }

    private function importService(): ImportMitigationActionsService
    {
        return new ImportMitigationActionsService($this->mitigationActions, $this->footprints(), $this->registerService());
    }

    private function getPlanService(): GetMitigationPlanService
    {
        return new GetMitigationPlanService($this->mitigationActions, $this->footprints(), $this->emissionSourcesBuilder());
    }

    private function propertyFootprint(int $year = 2025, ?PropertyId $propertyId = null, string $accountId = 'account-id', bool $withActivities = true): PropertyCarbonFootprint
    {
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered((string) ($propertyId ?? PropertyId::generate()), (string) PropertyCarbonFootprintId::generate(), $accountId, $year);

        if ($withActivities) {
            $footprint->addActivity($this->naturalGasActivity('600')->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
            $footprint->addActivity($this->naturalGasActivity('400')->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
            $footprint->addActivity($this->marketerElectricityActivity('500')->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));
        }

        $this->propertyCarbonFootprints->add($footprint);

        return $footprint;
    }

    private function companyFootprint(int $year = 2025, string $companyId = 'company-id'): CompanyCarbonFootprint
    {
        $footprint = CompanyCarbonFootprintMother::withEmployeesAnswered($companyId, (string) CompanyCarbonFootprintId::generate(), 'account-id', $year);
        $footprint->addActivity($this->naturalGasActivity('600')->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: PropertyId::generate()));
        $footprint->addActivity($this->naturalGasActivity('300')->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: PropertyId::generate()));
        $footprint->addActivity($this->naturalGasActivity('100')->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));

        $this->companyCarbonFootprints->add($footprint);

        return $footprint;
    }

    private function naturalGasActivity(string $co2eq, string $wellToTank = '0'): Activity
    {
        return $this->saveActivity(CombustibleActivity::register(
            id: ActivityId::generate(),
            accountId: new AccountId('account-id'),
            combustibleId: $this->naturalGasId,
            emissionFactor: new EmissionFactor('1.8', '1.8', '0', '0', 2025, EmissionFactorGroupMother::defaultId(), Unit::Liter, 'MITECO'),
            emissions: new Emissions($co2eq, $co2eq, '0', '0'),
            quantity: new Quantity(100.0, Unit::Liter),
            wellToTankEmissions: new Emissions($wellToTank, $wellToTank, '0', '0'),
        ));
    }

    private function marketerElectricityActivity(string $co2eq, string $wellToTank = '0'): Activity
    {
        return $this->saveActivity(ElectricityActivity::register(
            id: ActivityId::generate(),
            accountId: new AccountId('account-id'),
            electricityMarketerId: $this->marketerId,
            gdOFactorId: null,
            emissionFactor: new EmissionFactor('0.25', '0.25', '0', '0', 2025, EmissionFactorGroupMother::defaultId(), Unit::KWh, 'Comercializadora'),
            emissions: new Emissions($co2eq, $co2eq, '0', '0'),
            quantity: new Quantity(10000.0, Unit::KWh),
            wellToTankEmissions: new Emissions($wellToTank, $wellToTank, '0', '0'),
        ));
    }

    private function guaranteeOfOriginElectricityActivity(string $co2eq): Activity
    {
        return $this->saveActivity(ElectricityActivity::register(
            id: ActivityId::generate(),
            accountId: new AccountId('account-id'),
            electricityMarketerId: null,
            gdOFactorId: GdOFactorId::generate(),
            emissionFactor: new EmissionFactor('0', '0', '0', '0', 2025, EmissionFactorGroupMother::defaultId(), Unit::KWh, 'GdO'),
            emissions: new Emissions($co2eq, $co2eq, '0', '0'),
            quantity: new Quantity(10000.0, Unit::KWh),
        ));
    }

    private function saveActivity(Activity $activity): Activity
    {
        $this->activities->save($activity);

        return $activity;
    }
}
