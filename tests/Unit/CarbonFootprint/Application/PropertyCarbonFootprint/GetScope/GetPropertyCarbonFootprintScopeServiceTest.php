<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PropertyCarbonFootprint\GetScope;

use App\CarbonFootprint\Application\Activity\Catalog\ActivityCatalogProviderRegistry;
use App\CarbonFootprint\Application\Activity\Catalog\CapitalGoodCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\CombustibleCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\EconomicSectorCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\ElectricityCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\MobilityCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\OvernightStayCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\ProcessCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\PurchaseServiceCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\RefrigerantGasCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\TransportCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\UseProfileCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\WasteCatalogProvider;
use App\CarbonFootprint\Application\CarbonFootprint\DerivedSourceEmissionsCalculator;
use App\CarbonFootprint\Application\CarbonFootprint\StageSectionsBuilder;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\GetScope\GetPropertyCarbonFootprintScopeRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\GetScope\GetPropertyCarbonFootprintScopeService;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOrganizationalProviderContributionRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\CountedActivityReferencesMother;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\ListOrganizationalProviderContributionsServiceMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\CombustibleActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Process\ProcessRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Waste\WasteRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class GetPropertyCarbonFootprintScopeServiceTest extends TestCase
{
    private \App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository $repository;
    private GetPropertyCarbonFootprintScopeService $sut;
    private InMemoryActivityRepository $activityRepository;
    private InMemoryOrganizationalProviderContributionRepository $contributionRepository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = PropertyCarbonFootprintRepositoryMother::inMemory();

        $catalogProviders = new ActivityCatalogProviderRegistry([
            new CombustibleCatalogProvider(CombustibleRepositoryMother::inMemory()),
            new TransportCatalogProvider(TransportRepositoryMother::inMemory()),
            new RefrigerantGasCatalogProvider(RefrigerantGasRepositoryMother::inMemory()),
            new ElectricityCatalogProvider(ElectricityMarketerRepositoryMother::inMemory(), GdOFactorRepositoryMother::inMemory()),
            new PurchaseServiceCatalogProvider(PurchaseServiceRepositoryMother::inMemory()),
            new CapitalGoodCatalogProvider(CapitalGoodRepositoryMother::inMemory()),
            new WasteCatalogProvider(WasteRepositoryMother::inMemory()),
            new MobilityCatalogProvider(MobilityRepositoryMother::inMemory()),
            new OvernightStayCatalogProvider(OvernightStayRepositoryMother::inMemory()),
            new ProcessCatalogProvider(ProcessRepositoryMother::inMemory()),
            new UseProfileCatalogProvider(UseProfileRepositoryMother::inMemory()),
            new EconomicSectorCatalogProvider(EconomicSectorRepositoryMother::inMemory()),
        ]);
        $this->activityRepository = ActivityRepositoryMother::inMemory();
        $this->contributionRepository = OrganizationalProviderContributionRepositoryMother::inMemory();

        $this->sut = new GetPropertyCarbonFootprintScopeService(
            $this->repository,
            new PropertyCarbonFootprintAuthorizer(),
            new StageSectionsBuilder($catalogProviders, $this->activityRepository),
            new DerivedSourceEmissionsCalculator($this->activityRepository, CountedActivityReferencesMother::create()),
            ListOrganizationalProviderContributionsServiceMother::create(
                propertyCarbonFootprintRepository: $this->repository,
                contributionRepository: $this->contributionRepository,
                activityRepository: $this->activityRepository,
            ),
        );
    }

    public function testScope1ReturnsItsThreeSections(): void
    {
        $footprint = $this->registeredFootprint();

        $response = ($this->sut)(new GetPropertyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertCount(3, $response->sections);
        $this->assertSame(['combustibles', 'vehicles', 'refrigerantGases'], array_map(static fn($s) => $s->definition->key, $response->sections));
        $this->assertSame(ActivityType::RefrigerantGas, $response->sections[2]->definition->activityType);
    }

    public function testScope2ReturnsOnlyElectricity(): void
    {
        $footprint = $this->registeredFootprint();

        $response = ($this->sut)(new GetPropertyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope2), $this->ownerUser());

        $this->assertCount(1, $response->sections);
        $this->assertSame('electricity', $response->sections[0]->definition->key);
    }

    public function testExclusionsAreIndexedBySource(): void
    {
        $footprint = $this->registeredFootprint();
        $footprint->excludeSource(Scope::Scope1, Source::RefrigerantLeaks, ExclusionReason::NoDataAvailable, 'Sin datos');

        $response = ($this->sut)(new GetPropertyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertArrayHasKey(Source::RefrigerantLeaks->value, $response->exclusions);
        $this->assertSame('Sin datos', $response->exclusions[Source::RefrigerantLeaks->value]->comment());
    }

    public function testScope3ReturnsItsSectionsAndTheDerivedEmissions(): void
    {
        $footprint = $this->registeredFootprint();
        $combustible = CombustibleActivityMother::registeredWithWellToTank();
        $this->activityRepository->save($combustible);
        $footprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));

        $response = ($this->sut)(new GetPropertyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope3), $this->ownerUser());

        $this->assertCount(12, $response->sections);
        $this->assertNull($response->stageExclusion);
        $this->assertSame('122', $response->derivedEmissions[Source::FuelAndEnergyRelated->value]->co2eq());
        $this->assertCount(1, $response->derivedLines);
    }

    public function testScope3ReturnsTheStageExclusion(): void
    {
        $footprint = $this->registeredFootprint();
        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable, 'Sin datos');

        $response = ($this->sut)(new GetPropertyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope3), $this->ownerUser());

        $this->assertNotNull($response->stageExclusion);
        $this->assertSame(ExclusionReason::NoDataAvailable, $response->stageExclusion->reason);
        $this->assertSame('Sin datos', $response->stageExclusion->comment);
        $this->assertSame('0', $response->derivedEmissions[Source::FuelAndEnergyRelated->value]->co2eq());
    }

    public function testUnknownFootprintThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new GetPropertyCarbonFootprintScopeRequest(PropertyCarbonFootprintId::generate(), Scope::Scope1), $this->ownerUser());
    }

    public function testOtherAccountIsDenied(): void
    {
        $footprint = $this->registeredFootprint('other-account-id');

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new GetPropertyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope1), $this->ownerUser());
    }

    public function testScope3IncludesProviderContributions(): void
    {
        $footprint = $this->registeredFootprint();
        $this->contributionRepository->add(OrganizationalProviderContributionMother::manual(carbonFootprintId: (string) $footprint->id(), accountId: 'account-id-test'));

        $scope3 = ($this->sut)(new GetPropertyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope3), $this->ownerUser());
        $scope1 = ($this->sut)(new GetPropertyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertCount(1, $scope3->providerContributions);
        $this->assertSame([], $scope1->providerContributions);
    }

    private function registeredFootprint(string $accountId = 'account-id-test'): \App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint
    {
        $footprint = PropertyCarbonFootprintMother::from((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), $accountId);
        $this->repository->add($footprint);

        return $footprint;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }
}
