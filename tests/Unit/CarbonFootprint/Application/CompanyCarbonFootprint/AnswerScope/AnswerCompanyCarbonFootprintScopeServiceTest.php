<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerScope;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Answer\ActivityAnswerHandlerRegistry;
use App\CarbonFootprint\Application\Activity\Answer\CombustibleAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\EconomicSectorAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\ElectricityAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\PurchaseServiceAnswerHandler;
use App\CarbonFootprint\Application\Activity\CombustibleEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\EconomicSectorEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\ElectricityEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\ElectricityMixEmissionsResolver;
use App\CarbonFootprint\Application\Activity\PurchaseServiceEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterCombustibleActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterEconomicSectorActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterElectricityActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterPurchaseServiceActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateCombustibleActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateEconomicSectorActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateElectricityActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdatePurchaseServiceActivityService;
use App\CarbonFootprint\Application\CarbonFootprint\CarbonFootprintCountryResolver;
use App\CarbonFootprint\Application\CarbonFootprint\DerivedSourceEmissionsCalculator;
use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersProcessor;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersSynchronizer;
use App\CarbonFootprint\Application\CarbonFootprint\StageExclusionAnswer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerScope\AnswerCompanyCarbonFootprintScopeRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerScope\AnswerCompanyCarbonFootprintScopeService;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerScope\PropertySectionAnswers;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\NonConsolidatedProperty\NonConsolidatedPropertiesSynchronizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\NonConsolidatedProperty\NonConsolidatedPropertyAnswer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\NonConsolidatedProperty\NonConsolidatedPropertyEmissionsResolver;
use App\CarbonFootprint\Domain\Model\Activity\Answer\CombustibleAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\EconomicSectorAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ElectricityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\PurchaseServiceAnswer;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivity;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionDefinition;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSector;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseService;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\OperatingRegime;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\CountedActivityReferencesMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepositoryMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class AnswerCompanyCarbonFootprintScopeServiceTest extends TestCase
{
    private const YEAR = 2025;
    private const COMPANY_ID = 'company-1';

    private InMemoryActivityRepository $activityRepository;
    private InMemoryCompanyCarbonFootprintRepository $repository;
    private InMemoryPropertyRepository $propertyRepository;
    private \App\CarbonFootprint\Domain\Model\Combustible\Combustible $combustible;
    private \App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketer $electricityMarketer;
    private PropertyId $firstPropertyId;
    private PropertyId $secondPropertyId;
    private PropertyId $franchisedPropertyId;
    private PurchaseService $purchaseService;
    private EconomicSector $economicSector;
    private AnswerCompanyCarbonFootprintScopeService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->activityRepository = ActivityRepositoryMother::inMemory();
        $this->repository = CompanyCarbonFootprintRepositoryMother::inMemory();
        $this->propertyRepository = PropertyRepositoryMother::inMemory();

        $this->firstPropertyId = new PropertyId('property-1');
        $this->secondPropertyId = new PropertyId('property-2');
        $this->franchisedPropertyId = new PropertyId('property-3');

        $this->propertyRepository->add(PropertyMother::from(
            propertyId: $this->firstPropertyId,
            name: 'Hotel A',
            accountId: new AccountId('account-id-test'),
            info: new PropertyInfo(countryId: new CountryId('C-ES')),
            companyId: new CompanyId(self::COMPANY_ID),
        ));
        $this->propertyRepository->add(PropertyMother::from(
            propertyId: $this->secondPropertyId,
            name: 'Hotel B',
            accountId: new AccountId('account-id-test'),
            companyId: new CompanyId(self::COMPANY_ID),
        ));
        $this->propertyRepository->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Hotel C', new CompanyId(self::COMPANY_ID), $this->franchisedPropertyId, OperatingRegime::Franchised));

        $this->combustible = CombustibleMother::fromName('Gas natural');
        $this->combustible->setEmissionFactor($this->factor('0.18', Unit::KWh));
        $combustibleRepository = CombustibleRepositoryMother::inMemory();
        $combustibleRepository->add($this->combustible);

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
        $electricitySelector = new ElectricityEmissionFactorSelector($electricityMarketerRepository, GdOFactorRepositoryMother::inMemory());
        $mixResolver = new ElectricityMixEmissionsResolver($electricityMixRepository, $calculator);
        $purchaseServiceSelector = new PurchaseServiceEmissionFactorSelector($purchaseServiceRepository);
        $economicSectorSelector = new EconomicSectorEmissionFactorSelector($economicSectorRepository);

        $handlers = new ActivityAnswerHandlerRegistry([
            new CombustibleAnswerHandler(
                new RegisterCombustibleActivityService($this->activityRepository, $authorizer, $combustibleSelector, $calculator),
                new UpdateCombustibleActivityService($this->activityRepository, $authorizer, $combustibleSelector, $calculator),
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

        $companyProperties = new CompanyProperties($this->propertyRepository);

        $this->sut = new AnswerCompanyCarbonFootprintScopeService(
            $this->repository,
            $companyProperties,
            new CompanyCarbonFootprintAuthorizer(),
            new StageAnswersProcessor(),
            new StageAnswersSynchronizer($handlers, new CarbonFootprintCountryResolver(EventRepositoryMother::inMemory(), $this->propertyRepository)),
            new NonConsolidatedPropertiesSynchronizer(new NonConsolidatedPropertyEmissionsResolver(
                $companyProperties,
                PropertyCarbonFootprintRepositoryMother::inMemory(),
                new EmissionsBreakdownBuilder($this->activityRepository, new DerivedSourceEmissionsCalculator($this->activityRepository, CountedActivityReferencesMother::create()), CountedActivityReferencesMother::create()),
            )),
        );
    }

    public function testKeepsEachPropertyBlockApart(): void
    {
        $footprint = $this->registeredFootprint();

        ($this->sut)(new AnswerCompanyCarbonFootprintScopeRequest(
            $footprint->id(),
            Scope::Scope1,
            [
                new PropertySectionAnswers(null, [$this->combustibleSection()]),
                new PropertySectionAnswers($this->firstPropertyId, [$this->combustibleSection()]),
                new PropertySectionAnswers($this->secondPropertyId, [$this->combustibleSection()]),
            ],
        ), $this->ownerUser());

        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities)));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: $this->firstPropertyId)));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: $this->secondPropertyId)));
    }

    public function testAMissingBlockRemovesItsActivities(): void
    {
        $footprint = $this->registeredFootprint();

        ($this->sut)(new AnswerCompanyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope1, [
            new PropertySectionAnswers($this->firstPropertyId, [$this->combustibleSection()]),
        ]), $this->ownerUser());

        ($this->sut)(new AnswerCompanyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope1, []), $this->ownerUser());

        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: $this->firstPropertyId)));
    }

    public function testAPropertyOutsideTheCompanyIsRejected(): void
    {
        $footprint = $this->registeredFootprint();

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new AnswerCompanyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope1, [
            new PropertySectionAnswers(new PropertyId('property-outside'), [$this->combustibleSection()]),
        ]), $this->ownerUser());
    }

    public function testElectricityUsesThePropertyCountryAndTheUnassignedBlockHasNoLocationResult(): void
    {
        $footprint = $this->registeredFootprint();

        $section = new SectionAnswer(
            Scope::Scope2->sections()[0],
            [new ElectricityAnswer($this->electricityMarketer->id(), null, new Quantity(1000.0, Unit::KWh))],
        );

        ($this->sut)(new AnswerCompanyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope2, [
            new PropertySectionAnswers($this->firstPropertyId, [$section]),
            new PropertySectionAnswers(null, [$section]),
        ]), $this->ownerUser());

        $withProperty = $this->activityRepository->findById($footprint->activityIds(new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity, propertyId: $this->firstPropertyId))[0]);
        $unassigned = $this->activityRepository->findById($footprint->activityIds(new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity))[0]);

        $this->assertInstanceOf(ElectricityActivity::class, $withProperty);
        $this->assertInstanceOf(ElectricityActivity::class, $unassigned);
        $this->assertSame(180.0, (float) $withProperty->locationBasedEmissions()?->co2eq());
        $this->assertNull($unassigned->locationBasedEmissions());
    }

    public function testRegistersScope3ActivitiesByPropertyAndInTheUnassignedBlock(): void
    {
        $footprint = $this->registeredFootprint();
        $footprint->answerScope(Scope::Scope1);
        $footprint->answerScope(Scope::Scope2);

        ($this->sut)(new AnswerCompanyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope3, [
            new PropertySectionAnswers($this->firstPropertyId, [$this->purchasesSection()]),
            new PropertySectionAnswers(null, [new SectionAnswer($this->scope3Section('investments'), [new EconomicSectorAnswer($this->economicSector->id(), new Quantity(5000.0, Unit::Euro))])]),
        ]), $this->ownerUser());

        $this->assertSame(OrganizationalCarbonFootprintStatus::Scope3Answered, $footprint->status());
        $this->assertTrue($footprint->isMitigationPlanStarted());
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices, propertyId: $this->firstPropertyId)));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope3, Source::Investments)));
    }

    public function testExcludingTheWholeScope3EmptiesEveryBlock(): void
    {
        $footprint = $this->registeredFootprint();
        $footprint->answerScope(Scope::Scope1);
        $footprint->answerScope(Scope::Scope2);

        ($this->sut)(new AnswerCompanyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope3, [
            new PropertySectionAnswers($this->firstPropertyId, [$this->purchasesSection()]),
            new PropertySectionAnswers(null, [$this->purchasesSection()]),
        ]), $this->ownerUser());

        ($this->sut)(new AnswerCompanyCarbonFootprintScopeRequest(
            $footprint->id(),
            Scope::Scope3,
            stageExclusion: new StageExclusionAnswer(ExclusionReason::NoDataAvailable),
        ), $this->ownerUser());

        $this->assertSame(OrganizationalCarbonFootprintStatus::EmployeesAnswered, $footprint->status());
        $this->assertTrue($footprint->isStageExcluded(Scope::Scope3));
        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices, propertyId: $this->firstPropertyId)));
        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices)));
    }

    public function testExcludingTheWholeScope3WithAnswersInABlockThrows(): void
    {
        $footprint = $this->registeredFootprint();

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new AnswerCompanyCarbonFootprintScopeRequest(
            $footprint->id(),
            Scope::Scope3,
            [new PropertySectionAnswers($this->firstPropertyId, [$this->purchasesSection()])],
            stageExclusion: new StageExclusionAnswer(ExclusionReason::NoDataAvailable),
        ), $this->ownerUser());
    }

    public function testAnsweringScope3RecordsTheNonConsolidatedPropertiesWithTheManualEmissions(): void
    {
        $footprint = $this->registeredFootprint();
        $footprint->answerScope(Scope::Scope1);
        $footprint->answerScope(Scope::Scope2);

        ($this->sut)(new AnswerCompanyCarbonFootprintScopeRequest(
            $footprint->id(),
            Scope::Scope3,
            nonConsolidatedProperties: [new NonConsolidatedPropertyAnswer($this->franchisedPropertyId, '4500')],
        ), $this->ownerUser());

        $lines = $footprint->nonConsolidatedPropertyLines();
        $this->assertCount(1, $lines);
        $this->assertSame((string) $this->franchisedPropertyId, (string) $lines[0]->propertyId);
        $this->assertSame(Source::Franchises, $lines[0]->source);
        $this->assertSame('4500', $lines[0]->manualCo2eq);
        $this->assertSame('0', $lines[0]->calculatedEmissions->co2eq());
        $this->assertNull($lines[0]->propertyCarbonFootprintId);
    }

    public function testABlockOfAFranchisedPropertyIsRejected(): void
    {
        $footprint = $this->registeredFootprint();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Property does not belong to company');

        ($this->sut)(new AnswerCompanyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope1, [
            new PropertySectionAnswers($this->franchisedPropertyId, [$this->combustibleSection()]),
        ]), $this->ownerUser());
    }

    public function testAnsweringAScopeRemovesTheActivitiesOfAPropertyThatBecameFranchised(): void
    {
        $footprint = $this->registeredFootprint();

        ($this->sut)(new AnswerCompanyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope1, [
            new PropertySectionAnswers($this->secondPropertyId, [$this->combustibleSection()]),
        ]), $this->ownerUser());

        $placement = new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: $this->secondPropertyId);
        $this->assertCount(1, $footprint->activityIds($placement));

        $property = $this->propertyRepository->find($this->secondPropertyId);
        assert(null !== $property);
        $property->update(name: $property->name(), accountId: $property->accountId(), companyId: $property->companyId(), operatingRegime: OperatingRegime::Franchised);

        ($this->sut)(new AnswerCompanyCarbonFootprintScopeRequest($footprint->id(), Scope::Scope1, []), $this->ownerUser());

        $this->assertCount(0, $footprint->activityIds($placement));
    }

    public function testExcludingTheWholeScope3DoesNotRecordNonConsolidatedProperties(): void
    {
        $footprint = $this->registeredFootprint();
        $footprint->answerScope(Scope::Scope1);
        $footprint->answerScope(Scope::Scope2);

        ($this->sut)(new AnswerCompanyCarbonFootprintScopeRequest(
            $footprint->id(),
            Scope::Scope3,
            stageExclusion: new StageExclusionAnswer(ExclusionReason::NoDataAvailable),
            nonConsolidatedProperties: [new NonConsolidatedPropertyAnswer($this->franchisedPropertyId, '4500')],
        ), $this->ownerUser());

        $this->assertSame([], $footprint->nonConsolidatedPropertyLines());
    }

    public function testManualEmissionsOfAnOwnedPropertyAreRejected(): void
    {
        $footprint = $this->registeredFootprint();
        $footprint->answerScope(Scope::Scope1);
        $footprint->answerScope(Scope::Scope2);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Property is not a non-consolidated property of the company');

        ($this->sut)(new AnswerCompanyCarbonFootprintScopeRequest(
            $footprint->id(),
            Scope::Scope3,
            nonConsolidatedProperties: [new NonConsolidatedPropertyAnswer($this->firstPropertyId, '100')],
        ), $this->ownerUser());
    }

    private function purchasesSection(): SectionAnswer
    {
        return new SectionAnswer(
            $this->scope3Section('purchasedGoodsAndServices'),
            [new PurchaseServiceAnswer($this->purchaseService->id(), new Quantity(1000.0, Unit::Euro))],
        );
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

    private function registeredFootprint(): CompanyCarbonFootprint
    {
        $footprint = CompanyCarbonFootprintMother::from(
            companyId: self::COMPANY_ID,
            id: (string) CompanyCarbonFootprintId::generate(),
            accountId: 'account-id-test',
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
