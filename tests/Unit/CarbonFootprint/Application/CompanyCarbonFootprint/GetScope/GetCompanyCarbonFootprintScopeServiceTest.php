<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CompanyCarbonFootprint\GetScope;

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
use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Application\CarbonFootprint\StageSectionsBuilder;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\GetScope\GetCompanyCarbonFootprintScopeRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\GetScope\GetCompanyCarbonFootprintScopeService;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\NonConsolidatedProperty\NonConsolidatedPropertyEmissionsResolver;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\OperatingRegime;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\CombustibleActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Process\ProcessRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Waste\WasteRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class GetCompanyCarbonFootprintScopeServiceTest extends TestCase
{
    private \App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository $repository;
    private GetCompanyCarbonFootprintScopeService $sut;
    private InMemoryActivityRepository $activityRepository;
    private InMemoryPropertyRepository $propertyRepository;
    private InMemoryPropertyCarbonFootprintRepository $propertyCarbonFootprintRepository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = CompanyCarbonFootprintRepositoryMother::inMemory();

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

        $this->propertyRepository = PropertyRepositoryMother::inMemory();
        $this->propertyCarbonFootprintRepository = PropertyCarbonFootprintRepositoryMother::inMemory();
        $companyProperties = new CompanyProperties($this->propertyRepository);

        $this->sut = new GetCompanyCarbonFootprintScopeService(
            $this->repository,
            $companyProperties,
            new CompanyCarbonFootprintAuthorizer(),
            new StageSectionsBuilder($catalogProviders, $this->activityRepository),
            new DerivedSourceEmissionsCalculator($this->activityRepository),
            new NonConsolidatedPropertyEmissionsResolver(
                $companyProperties,
                $this->propertyCarbonFootprintRepository,
                new EmissionsBreakdownBuilder($this->activityRepository, new DerivedSourceEmissionsCalculator($this->activityRepository)),
            ),
        );
    }

    public function testScope1ReturnsItsThreeSections(): void
    {
        $footprint = $this->registeredFootprint();

        $response = ($this->sut)(new GetCompanyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertCount(1, $response->blocks);
        $this->assertCount(3, $response->blocks[0]->sections);
        $this->assertSame(['combustibles', 'vehicles', 'refrigerantGases'], array_map(static fn($s) => $s->definition->key, $response->blocks[0]->sections));
        $this->assertSame(ActivityType::RefrigerantGas, $response->blocks[0]->sections[2]->definition->activityType);
    }

    public function testScope2ReturnsOnlyElectricity(): void
    {
        $footprint = $this->registeredFootprint();

        $response = ($this->sut)(new GetCompanyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope2), $this->ownerUser());

        $this->assertCount(1, $response->blocks[0]->sections);
        $this->assertSame('electricity', $response->blocks[0]->sections[0]->definition->key);
    }

    public function testExclusionsAreIndexedBySource(): void
    {
        $footprint = $this->registeredFootprint();
        $footprint->excludeSource(Scope::Scope1, Source::RefrigerantLeaks, ExclusionReason::NoDataAvailable, 'Sin datos');

        $response = ($this->sut)(new GetCompanyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertArrayHasKey(Source::RefrigerantLeaks->value, $response->exclusions);
        $this->assertSame('Sin datos', $response->exclusions[Source::RefrigerantLeaks->value]->comment());
    }

    public function testScope3ReturnsItsSectionsAndTheDerivedEmissions(): void
    {
        $footprint = $this->registeredFootprint();
        $combustible = CombustibleActivityMother::registeredWithWellToTank();
        $this->activityRepository->save($combustible);
        $footprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));

        $response = ($this->sut)(new GetCompanyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope3), $this->ownerUser());

        $this->assertCount(12, $response->blocks[0]->sections);
        $this->assertNull($response->stageExclusion);
        $this->assertSame('122', $response->derivedEmissions[Source::FuelAndEnergyRelated->value]->co2eq());
        $this->assertCount(1, $response->derivedLines);
    }

    public function testScope3ReturnsTheStageExclusion(): void
    {
        $footprint = $this->registeredFootprint();
        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable, 'Sin datos');

        $response = ($this->sut)(new GetCompanyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope3), $this->ownerUser());

        $this->assertNotNull($response->stageExclusion);
        $this->assertSame(ExclusionReason::NoDataAvailable, $response->stageExclusion->reason);
        $this->assertSame('Sin datos', $response->stageExclusion->comment);
        $this->assertSame('0', $response->derivedEmissions[Source::FuelAndEnergyRelated->value]->co2eq());
    }

    public function testUnknownFootprintThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new GetCompanyCarbonFootprintScopeRequest(CompanyCarbonFootprintId::generate(), Scope::Scope1), $this->ownerUser());
    }

    public function testOtherAccountIsDenied(): void
    {
        $footprint = $this->registeredFootprint('other-account-id');

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new GetCompanyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope1), $this->ownerUser());
    }

    public function testBlocksExcludeNonConsolidatedProperties(): void
    {
        $footprint = $this->registeredFootprint(companyId: 'company-1');
        $this->propertyRepository->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Hotel Propio', new CompanyId('company-1'), new PropertyId('owned-1'), OperatingRegime::Owned));
        $this->propertyRepository->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Hotel Franquiciado', new CompanyId('company-1'), new PropertyId('franchised-1'), OperatingRegime::Franchised));

        $response = ($this->sut)(new GetCompanyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertCount(2, $response->blocks);
        $this->assertNull($response->blocks[0]->property);
        $this->assertSame('Hotel Propio', $response->blocks[1]->property?->name());
        $this->assertSame([], $response->nonConsolidatedProperties);
    }

    public function testScope3ReturnsTheLiveNonConsolidatedProperties(): void
    {
        $footprint = $this->registeredFootprint(companyId: 'company-1');
        $this->propertyRepository->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Hotel Franquiciado', new CompanyId('company-1'), new PropertyId('franchised-1'), OperatingRegime::Franchised));
        $this->propertyRepository->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Hotel Arrendado', new CompanyId('company-1'), new PropertyId('leased-out-1'), OperatingRegime::LeasedOut));

        $propertyFootprint = PropertyCarbonFootprintMother::withScope3Answered('franchised-1', (string) PropertyCarbonFootprintId::generate(), 'account-id-test');
        $activity = CombustibleActivityMother::registered();
        $this->activityRepository->save($activity);
        $propertyFootprint->addActivity($activity->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $propertyFootprint->complete(new Emissions('15'));
        $this->propertyCarbonFootprintRepository->add($propertyFootprint);

        $footprint->answerScope(Scope::Scope1);
        $footprint->answerScope(Scope::Scope2);
        $footprint->recordNonConsolidatedProperty(new PropertyId('leased-out-1'), Source::DownstreamLeasedAssets, null, new Emissions(), '4500');

        $response = ($this->sut)(new GetCompanyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope3), $this->ownerUser());

        $this->assertCount(2, $response->nonConsolidatedProperties);
        [$leasedOut, $franchised] = $response->nonConsolidatedProperties;

        $this->assertSame('Hotel Arrendado', $leasedOut->property?->name());
        $this->assertSame(Source::DownstreamLeasedAssets, $leasedOut->source);
        $this->assertNull($leasedOut->originId);
        $this->assertSame('4500', $leasedOut->manualCo2eq);
        $this->assertSame('4500', $leasedOut->emissions->co2eq());

        $this->assertSame('Hotel Franquiciado', $franchised->property?->name());
        $this->assertSame(Source::Franchises, $franchised->source);
        $this->assertSame((string) $propertyFootprint->id(), (string) $franchised->originId);
        $this->assertSame(2025, $franchised->year);
        $this->assertSame(15.0, (float) $franchised->calculatedEmissions->co2eq());
        $this->assertNull($franchised->manualCo2eq);
    }

    public function testScope3ReturnsTheSnapshotOfACompletedFootprint(): void
    {
        $footprint = $this->registeredFootprint(companyId: 'company-1');
        $this->propertyRepository->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Hotel Franquiciado', new CompanyId('company-1'), new PropertyId('franchised-1'), OperatingRegime::Franchised));

        $footprint->answerScope(Scope::Scope1);
        $footprint->answerScope(Scope::Scope2);
        $footprint->answerScope(Scope::Scope3);
        $footprint->recordNonConsolidatedProperty(new PropertyId('franchised-1'), Source::Franchises, null, new Emissions('5000'), null);
        $footprint->complete(new Emissions('5000'));

        $property = $this->propertyRepository->find(new PropertyId('franchised-1'));
        assert(null !== $property);
        $property->update(name: $property->name(), accountId: null, companyId: $property->companyId(), operatingRegime: OperatingRegime::Owned);

        $response = ($this->sut)(new GetCompanyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope3), $this->ownerUser());

        $this->assertCount(1, $response->nonConsolidatedProperties);
        $this->assertSame('Hotel Franquiciado', $response->nonConsolidatedProperties[0]->property?->name());
        $this->assertSame(Source::Franchises, $response->nonConsolidatedProperties[0]->source);
        $this->assertSame('5000', $response->nonConsolidatedProperties[0]->emissions->co2eq());
    }

    private function registeredFootprint(string $accountId = 'account-id-test', ?string $companyId = null): \App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint
    {
        $footprint = CompanyCarbonFootprintMother::from($companyId ?? (string) CompanyId::generate(), (string) CompanyCarbonFootprintId::generate(), $accountId);
        $this->repository->add($footprint);

        return $footprint;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }
}
