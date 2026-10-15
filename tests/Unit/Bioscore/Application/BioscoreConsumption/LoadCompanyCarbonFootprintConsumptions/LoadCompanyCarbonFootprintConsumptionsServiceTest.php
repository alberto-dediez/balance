<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bioscore\Application\BioscoreConsumption\LoadCompanyCarbonFootprintConsumptions;

use App\Bioscore\Application\BioscoreCatalogItem\CatalogTargetFinder;
use App\Bioscore\Application\BioscoreConsumption\BioscoreConsumptionAnswersBuilder;
use App\Bioscore\Application\BioscoreConsumption\LoadCompanyCarbonFootprintConsumptions\LoadCompanyCarbonFootprintConsumptionsRequest;
use App\Bioscore\Application\BioscoreConsumption\LoadCompanyCarbonFootprintConsumptions\LoadCompanyCarbonFootprintConsumptionsService;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreUnavailableException;
use App\Bioscore\Infrastructure\Gateway\InMemoryBioscoreConsumptionGateway;
use App\Bioscore\Infrastructure\Persistence\InMemory\Repository\InMemoryBioscoreCatalogItemRepository;
use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Answer\ActivityAnswerHandlerRegistry;
use App\CarbonFootprint\Application\Activity\Answer\CombustibleAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\RefrigerantGasAnswerHandler;
use App\CarbonFootprint\Application\Activity\CombustibleEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\RefrigerantGasEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterCombustibleActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterRefrigerantGasActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateCombustibleActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateRefrigerantGasActivityService;
use App\CarbonFootprint\Application\CarbonFootprint\CarbonFootprintCountryResolver;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersProcessor;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersSynchronizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerScope\PropertySectionAnswers;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\ReplaceScopeAnswers\ReplaceCompanyCarbonFootprintScopeAnswersRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\ReplaceScopeAnswers\ReplaceCompanyCarbonFootprintScopeAnswersService;
use App\CarbonFootprint\Domain\Model\Activity\Answer\CombustibleAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\PropertyId;
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
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepositoryMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class LoadCompanyCarbonFootprintConsumptionsServiceTest extends TestCase
{
    private const YEAR = 2025;
    private const ACCOUNT_ID = 'account-id-test';
    private const COMPANY_ID = 'company-1';

    private InMemoryActivityRepository $activityRepository;
    private InMemoryCompanyCarbonFootprintRepository $repository;
    private InMemoryPropertyRepository $propertyRepository;
    private InMemoryBioscoreCatalogItemRepository $catalogItems;
    private InMemoryBioscoreConsumptionGateway $gateway;
    private string $combustibleId;
    private string $refrigerantGasId;
    private PropertyId $firstPropertyId;
    private PropertyId $secondPropertyId;
    private ReplaceCompanyCarbonFootprintScopeAnswersService $replaceAnswers;
    private LoadCompanyCarbonFootprintConsumptionsService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->activityRepository = ActivityRepositoryMother::inMemory();
        $this->repository = CompanyCarbonFootprintRepositoryMother::inMemory();
        $this->propertyRepository = PropertyRepositoryMother::inMemory();
        $this->catalogItems = BioscoreCatalogItemRepositoryMother::inMemory();
        $this->gateway = new InMemoryBioscoreConsumptionGateway();

        $this->firstPropertyId = new PropertyId('property-1');
        $this->secondPropertyId = new PropertyId('property-2');
        $this->addCompanyProperty($this->firstPropertyId, 'Hotel A');
        $this->addCompanyProperty($this->secondPropertyId, 'Hotel B');

        $this->combustibleId = (string) Uuid::v7();
        $this->refrigerantGasId = (string) Uuid::v7();

        $combustible = CombustibleMother::from('Gas natural', $this->combustibleId, Unit::CubicMeter);
        $combustible->setEmissionFactor($this->factor('2.0', Unit::CubicMeter));
        $combustibleRepository = CombustibleRepositoryMother::inMemory();
        $combustibleRepository->add($combustible);

        $refrigerantGas = RefrigerantGasMother::from('R-410A', $this->refrigerantGasId);
        $refrigerantGas->setEmissionFactor($this->factor('2088', Unit::Kilogram));
        $refrigerantGasRepository = RefrigerantGasRepositoryMother::inMemory();
        $refrigerantGasRepository->add($refrigerantGas);

        $this->catalogItems->add(BioscoreCatalogItemMother::mapped($this->combustibleId, catalog: BioscoreCatalog::FacilityFuel, bioscoreId: 7, name: 'Gas Natural'));
        $this->catalogItems->add(BioscoreCatalogItemMother::mapped($this->refrigerantGasId, catalog: BioscoreCatalog::RefrigerantGas, bioscoreId: 9, name: 'R-410A', unit: 'kg'));

        $authorizer = new ActivityAuthorizer();
        $calculator = EmissionsCalculatorMother::create();
        $combustibleSelector = new CombustibleEmissionFactorSelector($combustibleRepository);
        $refrigerantGasSelector = new RefrigerantGasEmissionFactorSelector($refrigerantGasRepository);

        $handlers = new ActivityAnswerHandlerRegistry([
            new CombustibleAnswerHandler(
                new RegisterCombustibleActivityService($this->activityRepository, $authorizer, $combustibleSelector, $calculator),
                new UpdateCombustibleActivityService($this->activityRepository, $authorizer, $combustibleSelector, $calculator),
            ),
            new RefrigerantGasAnswerHandler(
                new RegisterRefrigerantGasActivityService($this->activityRepository, $authorizer, $refrigerantGasSelector, $calculator),
                new UpdateRefrigerantGasActivityService($this->activityRepository, $authorizer, $refrigerantGasSelector, $calculator),
            ),
        ]);

        $footprintAuthorizer = new CompanyCarbonFootprintAuthorizer();

        $this->replaceAnswers = new ReplaceCompanyCarbonFootprintScopeAnswersService(
            $this->repository,
            $this->propertyRepository,
            $footprintAuthorizer,
            new StageAnswersProcessor(),
            new StageAnswersSynchronizer($handlers, new CarbonFootprintCountryResolver(EventRepositoryMother::inMemory(), $this->propertyRepository)),
        );

        $this->sut = new LoadCompanyCarbonFootprintConsumptionsService(
            $this->repository,
            $this->propertyRepository,
            $footprintAuthorizer,
            $this->gateway,
            new BioscoreConsumptionAnswersBuilder(
                $this->catalogItems,
                new CatalogTargetFinder($combustibleRepository, RoadTransportRepositoryMother::inMemory(), $refrigerantGasRepository, ElectricityMarketerRepositoryMother::inMemory()),
            ),
            $this->replaceAnswers,
        );
    }

    public function testOnlyTheResolvedPropertyBlockIsReplaced(): void
    {
        $footprint = $this->footprintWithActivitiesInEveryBlock();

        $this->gateway->add($this->firstPropertyId, 'refrigerant_gases', BioscoreConsumptionMother::refrigerantGas(9, 'R-410A', 3.0));

        $response = ($this->sut)(new LoadCompanyCarbonFootprintConsumptionsRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertTrue($response->isResolved());
        $this->assertSame(2, $response->totalProperties);
        $this->assertSame(1, $response->resolvedProperties);
        $this->assertSame(1, $response->activities);

        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: $this->firstPropertyId)));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::RefrigerantLeaks, propertyId: $this->firstPropertyId)));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: $this->secondPropertyId)));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities)));
        $this->assertSame(OrganizationalCarbonFootprintStatus::Started, $footprint->status());
    }

    public function testBothPropertiesAreReplacedWhenBioscoreReturnsBoth(): void
    {
        $footprint = $this->footprintWithActivitiesInEveryBlock();

        $this->gateway->add($this->firstPropertyId, 'combustibles', BioscoreConsumptionMother::facilityFuel(7, 'Gas Natural', 100.0));
        $this->gateway->add($this->firstPropertyId, 'combustibles', BioscoreConsumptionMother::facilityFuel(7, 'Gas Natural', 200.0));
        $this->gateway->add($this->secondPropertyId, 'refrigerant_gases', BioscoreConsumptionMother::refrigerantGas(9, 'R-410A', 3.0));

        $response = ($this->sut)(new LoadCompanyCarbonFootprintConsumptionsRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertSame(2, $response->resolvedProperties);
        $this->assertSame(3, $response->activities);
        $this->assertCount(2, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: $this->firstPropertyId)));
        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: $this->secondPropertyId)));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::RefrigerantLeaks, propertyId: $this->secondPropertyId)));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities)));
    }

    public function testAPropertyOutsideTheCompanyIsIgnored(): void
    {
        $footprint = $this->registeredFootprint();

        $this->gateway->add(new PropertyId('property-9'), 'combustibles', BioscoreConsumptionMother::facilityFuel(7, 'Gas Natural', 100.0));

        $response = ($this->sut)(new LoadCompanyCarbonFootprintConsumptionsRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertFalse($response->isResolved());
        $this->assertSame(2, $response->totalProperties);
        $this->assertSame(0, $response->activities);
    }

    public function testACompanyWithoutPropertiesDoesNotCallBioscore(): void
    {
        $footprint = CompanyCarbonFootprintMother::from('company-without-properties', (string) CompanyCarbonFootprintId::generate(), self::ACCOUNT_ID, self::YEAR);
        $this->repository->add($footprint);

        $response = ($this->sut)(new LoadCompanyCarbonFootprintConsumptionsRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertSame(0, $response->totalProperties);
        $this->assertFalse($response->isResolved());
        $this->assertSame([], $this->gateway->queries());
    }

    public function testPropertiesAreQueriedInChunksOfOneHundred(): void
    {
        $footprint = $this->registeredFootprint();

        for ($i = 3; $i <= 150; ++$i) {
            $this->addCompanyProperty(new PropertyId('property-' . $i), 'Hotel ' . $i);
        }

        ($this->sut)(new LoadCompanyCarbonFootprintConsumptionsRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $queries = $this->gateway->queries();
        $this->assertCount(2, $queries);
        $this->assertCount(100, $queries[0]->propertyIds);
        $this->assertCount(50, $queries[1]->propertyIds);
        $this->assertSame(self::ACCOUNT_ID, (string) $queries[0]->accountId);
        $this->assertSame(self::YEAR, $queries[1]->year);
    }

    public function testAFailureInTheSecondQueryLeavesTheFootprintUntouched(): void
    {
        $footprint = $this->footprintWithActivitiesInEveryBlock();

        for ($i = 3; $i <= 150; ++$i) {
            $this->addCompanyProperty(new PropertyId('property-' . $i), 'Hotel ' . $i);
        }

        $this->gateway->add($this->firstPropertyId, 'refrigerant_gases', BioscoreConsumptionMother::refrigerantGas(9, 'R-410A', 3.0));
        $this->gateway->fail(fromQuery: 2);

        try {
            ($this->sut)(new LoadCompanyCarbonFootprintConsumptionsRequest($footprint->id(), Scope::Scope1), $this->ownerUser());
            $this->fail('Expected BioscoreUnavailableException');
        } catch (BioscoreUnavailableException) {
        }

        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: $this->firstPropertyId)));
        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::RefrigerantLeaks, propertyId: $this->firstPropertyId)));
    }

    public function testAnOwnerOfAnotherAccountIsDenied(): void
    {
        $footprint = $this->registeredFootprint('other-account-id');

        try {
            ($this->sut)(new LoadCompanyCarbonFootprintConsumptionsRequest($footprint->id(), Scope::Scope1), $this->ownerUser());
            $this->fail('Expected UnauthorizedActionException');
        } catch (UnauthorizedActionException) {
        }

        $this->assertSame([], $this->gateway->queries());
    }

    public function testIsHandled(): void
    {
        $this->assertTrue($this->sut->isHandled(new LoadCompanyCarbonFootprintConsumptionsRequest(CompanyCarbonFootprintId::generate(), Scope::Scope1)));
    }

    private function footprintWithActivitiesInEveryBlock(): CompanyCarbonFootprint
    {
        $footprint = $this->registeredFootprint();

        ($this->replaceAnswers)(new ReplaceCompanyCarbonFootprintScopeAnswersRequest($footprint->id(), Scope::Scope1, [
            new PropertySectionAnswers(null, [$this->combustibleSection()]),
            new PropertySectionAnswers($this->firstPropertyId, [$this->combustibleSection()]),
            new PropertySectionAnswers($this->secondPropertyId, [$this->combustibleSection()]),
        ]), $this->ownerUser());

        return $footprint;
    }

    private function combustibleSection(): SectionAnswer
    {
        return new SectionAnswer(
            Scope::Scope1->sections()[0],
            [new CombustibleAnswer(new CombustibleId($this->combustibleId), new Quantity(10.0, Unit::CubicMeter))],
        );
    }

    private function addCompanyProperty(PropertyId $propertyId, string $name): void
    {
        $this->propertyRepository->add(PropertyMother::from(
            propertyId: $propertyId,
            name: $name,
            accountId: new AccountId(self::ACCOUNT_ID),
            companyId: new CompanyId(self::COMPANY_ID),
        ));
    }

    private function registeredFootprint(string $accountId = self::ACCOUNT_ID): CompanyCarbonFootprint
    {
        $footprint = CompanyCarbonFootprintMother::from(
            companyId: self::COMPANY_ID,
            id: (string) CompanyCarbonFootprintId::generate(),
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
