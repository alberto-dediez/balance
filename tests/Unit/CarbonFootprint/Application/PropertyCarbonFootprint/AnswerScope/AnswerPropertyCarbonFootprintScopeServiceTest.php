<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PropertyCarbonFootprint\AnswerScope;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Answer\ActivityAnswerHandlerRegistry;
use App\CarbonFootprint\Application\Activity\Answer\CombustibleAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\EconomicSectorAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\ElectricityAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\PurchaseServiceAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\RefrigerantGasAnswerHandler;
use App\CarbonFootprint\Application\Activity\CombustibleEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\EconomicSectorEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\ElectricityEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\ElectricityMixEmissionsResolver;
use App\CarbonFootprint\Application\Activity\PurchaseServiceEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\RefrigerantGasEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterCombustibleActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterEconomicSectorActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterElectricityActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterPurchaseServiceActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterRefrigerantGasActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateCombustibleActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateEconomicSectorActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateElectricityActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdatePurchaseServiceActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateRefrigerantGasActivityService;
use App\CarbonFootprint\Application\CarbonFootprint\CarbonFootprintCountryResolver;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersProcessor;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersSynchronizer;
use App\CarbonFootprint\Application\CarbonFootprint\StageExclusionAnswer;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\AnswerScope\AnswerPropertyCarbonFootprintScopeRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\AnswerScope\AnswerPropertyCarbonFootprintScopeService;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\CombustibleAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\EconomicSectorAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ElectricityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\PurchaseServiceAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\RefrigerantGasAnswer;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivity;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionDefinition;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSector;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMitigationPlanStarted;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseService;
use App\CarbonFootprint\Domain\Model\Quantity;
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
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepositoryMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class AnswerPropertyCarbonFootprintScopeServiceTest extends TestCase
{
    private const YEAR = 2025;

    private InMemoryActivityRepository $activityRepository;
    private InMemoryPropertyCarbonFootprintRepository $repository;
    private InMemoryPropertyRepository $propertyRepository;
    private \App\CarbonFootprint\Domain\Model\Combustible\Combustible $combustible;
    private \App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGas $refrigerantGas;
    private \App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketer $electricityMarketer;
    private PurchaseService $purchaseService;
    private EconomicSector $economicSector;
    private AnswerPropertyCarbonFootprintScopeService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->activityRepository = ActivityRepositoryMother::inMemory();
        $this->repository = PropertyCarbonFootprintRepositoryMother::inMemory();
        $this->propertyRepository = PropertyRepositoryMother::inMemory();

        $this->combustible = CombustibleMother::fromName('Gas natural');
        $this->combustible->setEmissionFactor($this->factor('0.18', Unit::KWh));
        $combustibleRepository = CombustibleRepositoryMother::inMemory();
        $combustibleRepository->add($this->combustible);

        $this->refrigerantGas = RefrigerantGasMother::fromName('R-410A');
        $this->refrigerantGas->setEmissionFactor($this->factor('2088', Unit::Kilogram));
        $refrigerantGasRepository = RefrigerantGasRepositoryMother::inMemory();
        $refrigerantGasRepository->add($this->refrigerantGas);

        $this->electricityMarketer = ElectricityMarketerMother::fromName('Marketer');
        $this->electricityMarketer->setEmissionFactor($this->factor('0.25', Unit::KWh));
        $electricityMarketerRepository = ElectricityMarketerRepositoryMother::inMemory();
        $electricityMarketerRepository->add($this->electricityMarketer);

        $electricityMix = ElectricityMixMother::fromCountryAndId('C-ES', (string) ElectricityMixId::generate());
        $electricityMix->setEmissionFactor($this->factor('0.18', Unit::KWh));
        $electricityMixRepository = ElectricityMixRepositoryMother::inMemory();
        $electricityMixRepository->add($electricityMix);

        $this->purchaseService = PurchaseServiceMother::registeredPurchaseService();
        $this->purchaseService->setEmissionFactor($this->factor('0.5', Unit::Euro));
        $purchaseServiceRepository = PurchaseServiceRepositoryMother::inMemory();
        $purchaseServiceRepository->add($this->purchaseService);

        $this->economicSector = EconomicSectorMother::registeredEconomicSector();
        $this->economicSector->setEmissionFactor($this->factor('0.2', Unit::Euro));
        $economicSectorRepository = EconomicSectorRepositoryMother::inMemory();
        $economicSectorRepository->add($this->economicSector);

        $authorizer = new ActivityAuthorizer();
        $calculator = EmissionsCalculatorMother::create();

        $combustibleSelector = new CombustibleEmissionFactorSelector($combustibleRepository);
        $refrigerantGasSelector = new RefrigerantGasEmissionFactorSelector($refrigerantGasRepository);
        $electricitySelector = new ElectricityEmissionFactorSelector($electricityMarketerRepository, GdOFactorRepositoryMother::inMemory());
        $mixResolver = new ElectricityMixEmissionsResolver($electricityMixRepository, $calculator);
        $purchaseServiceSelector = new PurchaseServiceEmissionFactorSelector($purchaseServiceRepository);
        $economicSectorSelector = new EconomicSectorEmissionFactorSelector($economicSectorRepository);

        $handlers = new ActivityAnswerHandlerRegistry([
            new CombustibleAnswerHandler(
                new RegisterCombustibleActivityService($this->activityRepository, $authorizer, $combustibleSelector, $calculator),
                new UpdateCombustibleActivityService($this->activityRepository, $authorizer, $combustibleSelector, $calculator),
            ),
            new RefrigerantGasAnswerHandler(
                new RegisterRefrigerantGasActivityService($this->activityRepository, $authorizer, $refrigerantGasSelector, $calculator),
                new UpdateRefrigerantGasActivityService($this->activityRepository, $authorizer, $refrigerantGasSelector, $calculator),
            ),
            new ElectricityAnswerHandler(
                new RegisterElectricityActivityService($this->activityRepository, $authorizer, $electricitySelector, $calculator, $mixResolver),
                new UpdateElectricityActivityService($this->activityRepository, $authorizer, $electricitySelector, $calculator, $mixResolver),
            ),
            new PurchaseServiceAnswerHandler(
                new RegisterPurchaseServiceActivityService($this->activityRepository, $authorizer, $purchaseServiceSelector, $calculator),
                new UpdatePurchaseServiceActivityService($this->activityRepository, $authorizer, $purchaseServiceSelector, $calculator),
            ),
            new EconomicSectorAnswerHandler(
                new RegisterEconomicSectorActivityService($this->activityRepository, $authorizer, $economicSectorSelector, $calculator),
                new UpdateEconomicSectorActivityService($this->activityRepository, $authorizer, $economicSectorSelector, $calculator),
            ),
        ]);

        $this->sut = new AnswerPropertyCarbonFootprintScopeService(
            $this->repository,
            new PropertyCarbonFootprintAuthorizer(),
            new StageAnswersProcessor(),
            new StageAnswersSynchronizer($handlers, new CarbonFootprintCountryResolver(EventRepositoryMother::inMemory(), $this->propertyRepository)),
        );
    }

    public function testRegistersScope1ActivitiesAndAdvancesTheStatus(): void
    {
        $footprint = $this->registeredFootprint();

        ($this->sut)(new AnswerPropertyCarbonFootprintScopeRequest(
            $footprint->id(),
            Scope::Scope1,
            [$this->combustibleSection(), $this->refrigerantGasSection()],
        ), $this->ownerUser());

        $this->assertSame(OrganizationalCarbonFootprintStatus::Scope1Answered, $footprint->status());
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities)));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::RefrigerantLeaks)));
    }

    public function testASecondSubmissionWithoutRowsRemovesTheActivities(): void
    {
        $footprint = $this->registeredFootprint();

        ($this->sut)(new AnswerPropertyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope1, [$this->combustibleSection()]), $this->ownerUser());
        ($this->sut)(new AnswerPropertyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope1, []), $this->ownerUser());

        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities)));
    }

    public function testExcludingASourceEmptiesIt(): void
    {
        $footprint = $this->registeredFootprint();

        ($this->sut)(new AnswerPropertyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope1, [$this->combustibleSection()]), $this->ownerUser());

        ($this->sut)(new AnswerPropertyCarbonFootprintScopeRequest(
            $footprint->id(),
            Scope::Scope1,
            [],
            [new ExclusionAnswer(Source::FixedFacilities, ExclusionReason::NoDataAvailable, 'Sin datos')],
        ), $this->ownerUser());

        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities)));
        $this->assertTrue($footprint->isSourceExcluded(Scope::Scope1, Source::FixedFacilities));
    }

    public function testScope2ElectricityGetsTheLocationBasedResultFromThePropertyCountry(): void
    {
        $footprint = $this->registeredFootprint();

        ($this->sut)(new AnswerPropertyCarbonFootprintScopeRequest(
            $footprint->id(),
            Scope::Scope2,
            [new SectionAnswer(
                Scope::Scope2->sections()[0],
                [new ElectricityAnswer($this->electricityMarketer->id(), null, new Quantity(1000.0, Unit::KWh))],
            )],
        ), $this->ownerUser());

        $activityIds = $footprint->activityIds(new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));
        $this->assertCount(1, $activityIds);

        $activity = $this->activityRepository->findById($activityIds[0]);
        $this->assertInstanceOf(ElectricityActivity::class, $activity);
        $this->assertSame(250.0, (float) $activity->emissions()->co2eq());
        $this->assertSame(180.0, (float) $activity->locationBasedEmissions()?->co2eq());
    }

    public function testRegistersScope3PurchasesAndInvestmentsAndStartsTheMitigationPlan(): void
    {
        $footprint = $this->registeredFootprint();
        $footprint->answerScope(Scope::Scope1);
        $footprint->answerScope(Scope::Scope2);

        ($this->sut)(new AnswerPropertyCarbonFootprintScopeRequest(
            $footprint->id(),
            Scope::Scope3,
            [
                new SectionAnswer($this->scope3Section('purchasedGoodsAndServices'), [new PurchaseServiceAnswer($this->purchaseService->id(), new Quantity(1000.0, Unit::Euro))]),
                new SectionAnswer($this->scope3Section('investments'), [new EconomicSectorAnswer($this->economicSector->id(), new Quantity(5000.0, Unit::Euro))]),
            ],
        ), $this->ownerUser());

        $this->assertSame(OrganizationalCarbonFootprintStatus::Scope3Answered, $footprint->status());
        $this->assertTrue($footprint->isMitigationPlanStarted());
        $this->assertCount(1, array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintMitigationPlanStarted));

        $purchases = $footprint->activityIds(new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices));
        $investments = $footprint->activityIds(new ActivityPlacement(Scope::Scope3, Source::Investments));
        $this->assertCount(1, $purchases);
        $this->assertCount(1, $investments);
        $this->assertSame(500.0, (float) $this->activityRepository->findById($purchases[0])?->emissions()->co2eq());
        $this->assertSame(1000.0, (float) $this->activityRepository->findById($investments[0])?->emissions()->co2eq());
    }

    public function testExcludingTheWholeScope3RemovesItsActivitiesAndAnswersTheScope(): void
    {
        $footprint = $this->registeredFootprint();
        $footprint->answerScope(Scope::Scope1);
        $footprint->answerScope(Scope::Scope2);

        ($this->sut)(new AnswerPropertyCarbonFootprintScopeRequest(
            $footprint->id(),
            Scope::Scope3,
            [new SectionAnswer($this->scope3Section('purchasedGoodsAndServices'), [new PurchaseServiceAnswer($this->purchaseService->id(), new Quantity(1000.0, Unit::Euro))])],
            [new ExclusionAnswer(Source::Investments, ExclusionReason::NotApplicable)],
        ), $this->ownerUser());

        ($this->sut)(new AnswerPropertyCarbonFootprintScopeRequest(
            $footprint->id(),
            Scope::Scope3,
            stageExclusion: new StageExclusionAnswer(ExclusionReason::NoDataAvailable, 'Sin datos'),
        ), $this->ownerUser());

        $this->assertSame(OrganizationalCarbonFootprintStatus::EmployeesAnswered, $footprint->status());
        $this->assertTrue($footprint->isStageExcluded(Scope::Scope3));
        $this->assertSame(ExclusionReason::NoDataAvailable, $footprint->stageExclusionReason(Scope::Scope3));
        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices)));
        $this->assertSame([], $footprint->exclusionsFor(Scope::Scope3));
    }

    public function testAnsweringScope3AgainWithoutExclusionIncludesTheScope(): void
    {
        $footprint = $this->registeredFootprint();
        $footprint->answerScope(Scope::Scope1);
        $footprint->answerScope(Scope::Scope2);
        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable);

        ($this->sut)(new AnswerPropertyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope3), $this->ownerUser());

        $this->assertFalse($footprint->isStageExcluded(Scope::Scope3));
    }

    public function testExcludingTheWholeScope3WithAnswersThrows(): void
    {
        $footprint = $this->registeredFootprint();

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new AnswerPropertyCarbonFootprintScopeRequest(
            $footprint->id(),
            Scope::Scope3,
            [new SectionAnswer($this->scope3Section('purchasedGoodsAndServices'), [new PurchaseServiceAnswer($this->purchaseService->id(), new Quantity(1000.0, Unit::Euro))])],
            stageExclusion: new StageExclusionAnswer(ExclusionReason::NoDataAvailable),
        ), $this->ownerUser());
    }

    public function testOtherAccountIsDenied(): void
    {
        $footprint = $this->registeredFootprint('other-account-id');

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new AnswerPropertyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope1, []), $this->ownerUser());
    }

    public function testIsHandled(): void
    {
        $this->assertTrue($this->sut->isHandled(new AnswerPropertyCarbonFootprintScopeRequest(PropertyCarbonFootprintId::generate(), Scope::Scope1)));
    }

    private function scope3Section(string $key): SectionDefinition
    {
        foreach (Scope::Scope3->sections() as $section) {
            if ($key === $section->key) {
                return $section;
            }
        }

        throw new \InvalidArgumentException($key);
    }

    private function combustibleSection(): SectionAnswer
    {
        return new SectionAnswer(
            Scope::Scope1->sections()[0],
            [new CombustibleAnswer($this->combustible->id(), new Quantity(1000.0, Unit::KWh))],
        );
    }

    private function refrigerantGasSection(): SectionAnswer
    {
        return new SectionAnswer(
            Scope::Scope1->sections()[2],
            [new RefrigerantGasAnswer($this->refrigerantGas->id(), new Quantity(1.0, Unit::Kilogram))],
        );
    }

    private function registeredFootprint(string $accountId = 'account-id-test'): PropertyCarbonFootprint
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
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }
}
