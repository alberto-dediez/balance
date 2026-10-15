<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bioscore\Application\BioscoreConsumption\LoadPropertyCarbonFootprintConsumptions;

use App\Bioscore\Application\BioscoreCatalogItem\CatalogTargetFinder;
use App\Bioscore\Application\BioscoreConsumption\BioscoreConsumptionAnswersBuilder;
use App\Bioscore\Application\BioscoreConsumption\BioscoreConsumptionSkipReason;
use App\Bioscore\Application\BioscoreConsumption\LoadPropertyCarbonFootprintConsumptions\LoadPropertyCarbonFootprintConsumptionsRequest;
use App\Bioscore\Application\BioscoreConsumption\LoadPropertyCarbonFootprintConsumptions\LoadPropertyCarbonFootprintConsumptionsService;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreUnavailableException;
use App\Bioscore\Infrastructure\Gateway\InMemoryBioscoreConsumptionGateway;
use App\Bioscore\Infrastructure\Persistence\InMemory\Repository\InMemoryBioscoreCatalogItemRepository;
use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Answer\ActivityAnswerHandlerRegistry;
use App\CarbonFootprint\Application\Activity\Answer\CombustibleAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\ElectricityAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\RefrigerantGasAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\RoadTransportAnswerHandler;
use App\CarbonFootprint\Application\Activity\CombustibleEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\ElectricityEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\ElectricityMixEmissionsResolver;
use App\CarbonFootprint\Application\Activity\RefrigerantGasEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterCombustibleActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterElectricityActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterRefrigerantGasActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterRoadTransportActivityService;
use App\CarbonFootprint\Application\Activity\RoadTransportEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Update\UpdateCombustibleActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateElectricityActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateRefrigerantGasActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateRoadTransportActivityService;
use App\CarbonFootprint\Application\CarbonFootprint\CarbonFootprintCountryResolver;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersProcessor;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersSynchronizer;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\ReplaceScopeAnswers\ReplacePropertyCarbonFootprintScopeAnswersRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\ReplaceScopeAnswers\ReplacePropertyCarbonFootprintScopeAnswersService;
use App\CarbonFootprint\Domain\Model\Activity\Answer\CombustibleAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ElectricityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\RefrigerantGasAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\RoadTransportAnswer;
use App\CarbonFootprint\Domain\Model\Activity\CombustibleActivity;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivity;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemMother;
use App\Tests\Unit\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemRepositoryMother;
use App\Tests\Unit\Bioscore\Domain\Model\BioscoreConsumption\BioscoreConsumptionMother;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepositoryMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class LoadPropertyCarbonFootprintConsumptionsServiceTest extends TestCase
{
    private const YEAR = 2025;
    private const ACCOUNT_ID = 'account-id-test';

    private InMemoryActivityRepository $activityRepository;
    private InMemoryPropertyCarbonFootprintRepository $repository;
    private InMemoryPropertyRepository $propertyRepository;
    private InMemoryBioscoreCatalogItemRepository $catalogItems;
    private InMemoryBioscoreConsumptionGateway $gateway;
    private string $combustibleId;
    private string $roadTransportId;
    private string $refrigerantGasId;
    private string $electricityMarketerId;
    private ReplacePropertyCarbonFootprintScopeAnswersService $replaceAnswers;
    private LoadPropertyCarbonFootprintConsumptionsService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->activityRepository = ActivityRepositoryMother::inMemory();
        $this->repository = PropertyCarbonFootprintRepositoryMother::inMemory();
        $this->propertyRepository = PropertyRepositoryMother::inMemory();
        $this->catalogItems = BioscoreCatalogItemRepositoryMother::inMemory();
        $this->gateway = new InMemoryBioscoreConsumptionGateway();

        $this->combustibleId = (string) Uuid::v7();
        $this->roadTransportId = (string) Uuid::v7();
        $this->refrigerantGasId = (string) Uuid::v7();
        $this->electricityMarketerId = (string) Uuid::v7();

        $combustible = CombustibleMother::from('Gas natural', $this->combustibleId, Unit::CubicMeter);
        $combustible->setEmissionFactor($this->factor('2.0', Unit::CubicMeter));
        $combustibleRepository = CombustibleRepositoryMother::inMemory();
        $combustibleRepository->add($combustible);

        $roadTransport = RoadTransportMother::from('Gasolina', $this->roadTransportId, Unit::Liter);
        $roadTransport->setEmissionFactor($this->factor('2.3', Unit::Liter));
        $roadTransportRepository = RoadTransportRepositoryMother::inMemory();
        $roadTransportRepository->add($roadTransport);

        $refrigerantGas = RefrigerantGasMother::from('R-410A', $this->refrigerantGasId);
        $refrigerantGas->setEmissionFactor($this->factor('2088', Unit::Kilogram));
        $refrigerantGasRepository = RefrigerantGasRepositoryMother::inMemory();
        $refrigerantGasRepository->add($refrigerantGas);

        $electricityMarketer = ElectricityMarketerMother::from('Endesa', $this->electricityMarketerId);
        $electricityMarketer->setEmissionFactor($this->factor('0.25', Unit::KWh));
        $electricityMarketerRepository = ElectricityMarketerRepositoryMother::inMemory();
        $electricityMarketerRepository->add($electricityMarketer);

        $electricityMix = ElectricityMixMother::fromCountryAndId('C-ES', (string) ElectricityMixId::generate());
        $electricityMix->setEmissionFactor($this->factor('0.18', Unit::KWh));
        $electricityMixRepository = ElectricityMixRepositoryMother::inMemory();
        $electricityMixRepository->add($electricityMix);

        $this->catalogItems->add(BioscoreCatalogItemMother::mapped($this->combustibleId, catalog: BioscoreCatalog::FacilityFuel, bioscoreId: 7, name: 'Gas Natural'));
        $this->catalogItems->add(BioscoreCatalogItemMother::mapped($this->roadTransportId, catalog: BioscoreCatalog::TransportFuel, bioscoreId: 8, name: 'Gasolina', unit: 'l'));
        $this->catalogItems->add(BioscoreCatalogItemMother::mapped($this->refrigerantGasId, catalog: BioscoreCatalog::RefrigerantGas, bioscoreId: 9, name: 'R-410A', unit: 'kg'));
        $this->catalogItems->add(BioscoreCatalogItemMother::mapped($this->electricityMarketerId, catalog: BioscoreCatalog::ElectricityMarketer, bioscoreId: 3, name: 'Endesa', unit: 'kWh'));

        $authorizer = new ActivityAuthorizer();
        $calculator = EmissionsCalculatorMother::create();
        $combustibleSelector = new CombustibleEmissionFactorSelector($combustibleRepository);
        $roadTransportSelector = new RoadTransportEmissionFactorSelector($roadTransportRepository);
        $refrigerantGasSelector = new RefrigerantGasEmissionFactorSelector($refrigerantGasRepository);
        $electricitySelector = new ElectricityEmissionFactorSelector($electricityMarketerRepository, GdOFactorRepositoryMother::inMemory());
        $mixResolver = new ElectricityMixEmissionsResolver($electricityMixRepository, $calculator);

        $handlers = new ActivityAnswerHandlerRegistry([
            new CombustibleAnswerHandler(
                new RegisterCombustibleActivityService($this->activityRepository, $authorizer, $combustibleSelector, $calculator),
                new UpdateCombustibleActivityService($this->activityRepository, $authorizer, $combustibleSelector, $calculator),
            ),
            new RoadTransportAnswerHandler(
                new RegisterRoadTransportActivityService($this->activityRepository, $authorizer, $roadTransportSelector, $calculator),
                new UpdateRoadTransportActivityService($this->activityRepository, $authorizer, $roadTransportSelector, $calculator),
            ),
            new RefrigerantGasAnswerHandler(
                new RegisterRefrigerantGasActivityService($this->activityRepository, $authorizer, $refrigerantGasSelector, $calculator),
                new UpdateRefrigerantGasActivityService($this->activityRepository, $authorizer, $refrigerantGasSelector, $calculator),
            ),
            new ElectricityAnswerHandler(
                new RegisterElectricityActivityService($this->activityRepository, $authorizer, $electricitySelector, $calculator, $mixResolver),
                new UpdateElectricityActivityService($this->activityRepository, $authorizer, $electricitySelector, $calculator, $mixResolver),
            ),
        ]);

        $footprintAuthorizer = new PropertyCarbonFootprintAuthorizer();

        $this->replaceAnswers = new ReplacePropertyCarbonFootprintScopeAnswersService(
            $this->repository,
            $footprintAuthorizer,
            new StageAnswersProcessor(),
            new StageAnswersSynchronizer($handlers, new CarbonFootprintCountryResolver(EventRepositoryMother::inMemory(), $this->propertyRepository)),
        );

        $this->sut = new LoadPropertyCarbonFootprintConsumptionsService(
            $this->repository,
            $footprintAuthorizer,
            $this->gateway,
            new BioscoreConsumptionAnswersBuilder(
                $this->catalogItems,
                new CatalogTargetFinder($combustibleRepository, $roadTransportRepository, $refrigerantGasRepository, $electricityMarketerRepository),
            ),
            $this->replaceAnswers,
        );
    }

    public function testLoadsScope1ReplacingTheManualActivities(): void
    {
        $footprint = $this->footprintWithManualActivities();
        $propertyId = $this->propertyIdOf($footprint);

        $this->gateway->add($propertyId, 'combustibles', BioscoreConsumptionMother::facilityFuel(7, 'Gas Natural', 1000.0, 'm3'));
        $this->gateway->add($propertyId, 'refrigerant_gases', BioscoreConsumptionMother::refrigerantGas(9, 'R-410A', 3.0));

        $response = ($this->sut)(new LoadPropertyCarbonFootprintConsumptionsRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertTrue($response->resolved);
        $this->assertSame(2, $response->activities);
        $this->assertSame([], $response->skipped);
        $this->assertSame([], $response->includedSources);

        $combustibleIds = $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $this->assertCount(1, $combustibleIds);
        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::OwnVehicles)));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::RefrigerantLeaks)));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity)));
        $this->assertSame(OrganizationalCarbonFootprintStatus::Started, $footprint->status());

        $activity = $this->activityRepository->findById($combustibleIds[0]);
        $this->assertInstanceOf(CombustibleActivity::class, $activity);
        $this->assertSame('Bioscore', $activity->source());
        $this->assertSame(1000.0, $activity->quantity()->value());
        $this->assertSame(Unit::CubicMeter, $activity->quantity()->unit());
    }

    public function testLoadsScope2WithTheLocationBasedResult(): void
    {
        $footprint = $this->footprintWithManualActivities();
        $propertyId = $this->propertyIdOf($footprint);

        $this->gateway->add($propertyId, 'electricity', BioscoreConsumptionMother::electricity(3, 'Endesa', 1000.0));
        $this->gateway->add($propertyId, 'combustibles', BioscoreConsumptionMother::facilityFuel(7, 'Gas Natural', 1000.0));

        $response = ($this->sut)(new LoadPropertyCarbonFootprintConsumptionsRequest($footprint->id(), Scope::Scope2), $this->ownerUser());

        $this->assertTrue($response->resolved);
        $this->assertSame(1, $response->activities);

        $electricityIds = $footprint->activityIds(new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));
        $this->assertCount(1, $electricityIds);
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities)));

        $activity = $this->activityRepository->findById($electricityIds[0]);
        $this->assertInstanceOf(ElectricityActivity::class, $activity);
        $this->assertSame('Bioscore', $activity->source());
        $this->assertSame(250.0, (float) $activity->emissions()->co2eq());
        $this->assertSame(180.0, (float) $activity->locationBasedEmissions()?->co2eq());
    }

    public function testAnExcludedSourceWithDataIsIncludedAgain(): void
    {
        $footprint = $this->registeredFootprint();
        $footprint->excludeSource(Scope::Scope1, Source::RefrigerantLeaks, ExclusionReason::NoDataAvailable);
        $footprint->excludeSource(Scope::Scope1, Source::OwnVehicles, ExclusionReason::NoDataAvailable);

        $this->gateway->add($this->propertyIdOf($footprint), 'refrigerant_gases', BioscoreConsumptionMother::refrigerantGas(9, 'R-410A', 3.0));

        $response = ($this->sut)(new LoadPropertyCarbonFootprintConsumptionsRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertSame([Source::RefrigerantLeaks], $response->includedSources);
        $this->assertFalse($footprint->isSourceExcluded(Scope::Scope1, Source::RefrigerantLeaks));
        $this->assertTrue($footprint->isSourceExcluded(Scope::Scope1, Source::OwnVehicles));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::RefrigerantLeaks)));
    }

    public function testNothingChangesWhenBioscoreDoesNotResolveTheProperty(): void
    {
        $footprint = $this->footprintWithManualActivities();

        $response = ($this->sut)(new LoadPropertyCarbonFootprintConsumptionsRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertFalse($response->resolved);
        $this->assertSame(0, $response->activities);
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities)));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::OwnVehicles)));
    }

    public function testUnmappedItemsAreSkippedAndTheRestIsLoaded(): void
    {
        $footprint = $this->registeredFootprint();
        $propertyId = $this->propertyIdOf($footprint);

        $this->gateway->add($propertyId, 'combustibles', BioscoreConsumptionMother::facilityFuel(7, 'Gas Natural', 1000.0));
        $this->gateway->add($propertyId, 'combustibles', BioscoreConsumptionMother::transportFuel(15, 'Diesel', 200.0, 'l'));

        $response = ($this->sut)(new LoadPropertyCarbonFootprintConsumptionsRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertSame(1, $response->activities);
        $this->assertCount(1, $response->skipped);
        $this->assertSame(15, $response->skipped[0]->bioscoreId);
        $this->assertSame(BioscoreConsumptionSkipReason::Unmapped, $response->skipped[0]->reason);
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities)));
    }

    public function testAFailingGatewayLeavesTheFootprintUntouched(): void
    {
        $footprint = $this->footprintWithManualActivities();
        $this->gateway->fail();

        try {
            ($this->sut)(new LoadPropertyCarbonFootprintConsumptionsRequest($footprint->id(), Scope::Scope1), $this->ownerUser());
            $this->fail('Expected BioscoreUnavailableException');
        } catch (BioscoreUnavailableException) {
        }

        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities)));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::OwnVehicles)));
    }

    public function testTheQueryCarriesTheAccountPropertyAndYearOfTheFootprint(): void
    {
        $footprint = $this->registeredFootprint();

        ($this->sut)(new LoadPropertyCarbonFootprintConsumptionsRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $query = $this->gateway->lastQuery();
        $this->assertNotNull($query);
        $this->assertSame(self::ACCOUNT_ID, (string) $query->accountId);
        $this->assertCount(1, $query->propertyIds);
        $this->assertTrue($query->propertyIds[0]->equals($this->propertyIdOf($footprint)));
        $this->assertSame(self::YEAR, $query->year);
    }

    public function testAnOwnerOfAnotherAccountIsDeniedBeforeCallingBioscore(): void
    {
        $footprint = $this->registeredFootprint('other-account-id');

        try {
            ($this->sut)(new LoadPropertyCarbonFootprintConsumptionsRequest($footprint->id(), Scope::Scope1), $this->ownerUser());
            $this->fail('Expected UnauthorizedActionException');
        } catch (UnauthorizedActionException) {
        }

        $this->assertSame([], $this->gateway->queries());
    }

    public function testAPlainUserIsDenied(): void
    {
        $footprint = $this->registeredFootprint();

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(
            new LoadPropertyCarbonFootprintConsumptionsRequest($footprint->id(), Scope::Scope1),
            UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId(self::ACCOUNT_ID)),
        );
    }

    public function testIsHandled(): void
    {
        $this->assertTrue($this->sut->isHandled(new LoadPropertyCarbonFootprintConsumptionsRequest(PropertyCarbonFootprintId::generate(), Scope::Scope1)));
    }

    private function footprintWithManualActivities(): PropertyCarbonFootprint
    {
        $footprint = $this->registeredFootprint();
        $sections = Scope::Scope1->sections();

        ($this->replaceAnswers)(new ReplacePropertyCarbonFootprintScopeAnswersRequest($footprint->id(), Scope::Scope1, [
            new SectionAnswer($sections[0], [new CombustibleAnswer(new CombustibleId($this->combustibleId), new Quantity(10.0, Unit::CubicMeter))]),
            new SectionAnswer($sections[1], [new RoadTransportAnswer(new RoadTransportId($this->roadTransportId), new Quantity(10.0, Unit::Liter))]),
            new SectionAnswer($sections[2], [new RefrigerantGasAnswer(new RefrigerantGasId($this->refrigerantGasId), new Quantity(1.0, Unit::Kilogram))]),
        ]), $this->ownerUser());

        ($this->replaceAnswers)(new ReplacePropertyCarbonFootprintScopeAnswersRequest($footprint->id(), Scope::Scope2, [
            new SectionAnswer(Scope::Scope2->sections()[0], [new ElectricityAnswer(new ElectricityMarketerId($this->electricityMarketerId), null, new Quantity(10.0, Unit::KWh))]),
        ]), $this->ownerUser());

        return $footprint;
    }

    private function propertyIdOf(PropertyCarbonFootprint $footprint): PropertyId
    {
        $propertyId = $footprint->propertyId();
        $this->assertNotNull($propertyId);

        return $propertyId;
    }

    private function registeredFootprint(string $accountId = self::ACCOUNT_ID): PropertyCarbonFootprint
    {
        $property = PropertyMother::from(
            propertyId: PropertyId::generate(),
            accountId: new AccountId($accountId),
            info: new PropertyInfo(countryId: new CountryId('C-ES')),
        );
        $this->propertyRepository->add($property);

        $footprint = PropertyCarbonFootprintMother::from(
            propertyId: (string) $property->id(),
            id: (string) PropertyCarbonFootprintId::generate(),
            accountId: $accountId,
            year: self::YEAR,
        );
        $this->repository->add($footprint);

        return $footprint;
    }

    private function factor(string $co2eq, Unit $unit): EmissionFactor
    {
        return new EmissionFactor(
            co2eq: $co2eq,
            co2: $co2eq,
            ch4: '0',
            n2o: '0',
            year: self::YEAR,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
            unit: $unit,
        );
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId(self::ACCOUNT_ID));
    }
}
