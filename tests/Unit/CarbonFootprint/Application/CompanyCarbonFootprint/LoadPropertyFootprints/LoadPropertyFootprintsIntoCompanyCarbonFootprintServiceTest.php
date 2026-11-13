<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CompanyCarbonFootprint\LoadPropertyFootprints;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Answer\ActivityAnswerHandlerRegistry;
use App\CarbonFootprint\Application\Activity\Answer\CombustibleAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\ElectricityAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\RefrigerantGasAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\TransportAnswerHandler;
use App\CarbonFootprint\Application\Activity\CombustibleEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\ElectricityEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\ElectricityMixEmissionsResolver;
use App\CarbonFootprint\Application\Activity\RefrigerantGasEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterCombustibleActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterElectricityActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterRefrigerantGasActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterTransportActivityService;
use App\CarbonFootprint\Application\Activity\TransportEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Update\UpdateCombustibleActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateElectricityActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateRefrigerantGasActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateTransportActivityService;
use App\CarbonFootprint\Application\CarbonFootprint\CarbonFootprintCountryResolver;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersProcessor;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersSynchronizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerScope\PropertySectionAnswers;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompletedPropertyFootprintFinder;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\LoadPropertyFootprints\LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\LoadPropertyFootprints\LoadPropertyFootprintsIntoCompanyCarbonFootprintService;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\LoadPropertyFootprints\PropertyActivityAnswerBuilder;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\LoadPropertyFootprints\PropertyFootprintSkipReason;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\LoadPropertyFootprints\SkippedPropertyFootprint;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\ReplaceScopeAnswers\ReplaceCompanyCarbonFootprintScopeAnswersRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\ReplaceScopeAnswers\ReplaceCompanyCarbonFootprintScopeAnswersService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\CombustibleAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ElectricityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\RefrigerantGasAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\TransportAnswer;
use App\CarbonFootprint\Domain\Model\Activity\CombustibleActivity;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivity;
use App\CarbonFootprint\Domain\Model\Activity\TransportActivity;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Combustible\Combustible;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactor;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGas;
use App\CarbonFootprint\Domain\Model\Transport\Transport;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryElectricityMixRepository;
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
use App\Shared\Infrastructure\Persistence\InMemory\Transaction\InMemoryTransactionalSession;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportRepositoryMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class LoadPropertyFootprintsIntoCompanyCarbonFootprintServiceTest extends TestCase
{
    private const YEAR = 2025;
    private const ACCOUNT_ID = 'account-id-test';
    private const COMPANY_ID = 'company-1';

    private InMemoryActivityRepository $activityRepository;
    private InMemoryCompanyCarbonFootprintRepository $repository;
    private InMemoryPropertyCarbonFootprintRepository $propertyFootprintRepository;
    private InMemoryPropertyRepository $propertyRepository;
    private ActivityAnswerHandlerRegistry $handlers;
    private Combustible $combustible;
    private Transport $transport;
    private RefrigerantGas $refrigerantGas;
    private ElectricityMarketer $electricityMarketer;
    private GdOFactor $gdOFactor;
    private PropertyId $firstPropertyId;
    private PropertyId $secondPropertyId;
    private ReplaceCompanyCarbonFootprintScopeAnswersService $replaceAnswers;
    private LoadPropertyFootprintsIntoCompanyCarbonFootprintService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->activityRepository = ActivityRepositoryMother::inMemory();
        $this->repository = CompanyCarbonFootprintRepositoryMother::inMemory();
        $this->propertyFootprintRepository = PropertyCarbonFootprintRepositoryMother::inMemory();
        $this->propertyRepository = PropertyRepositoryMother::inMemory();

        $this->firstPropertyId = new PropertyId('property-1');
        $this->secondPropertyId = new PropertyId('property-2');
        $this->addCompanyProperty($this->firstPropertyId, 'Hotel A');
        $this->addCompanyProperty($this->secondPropertyId, 'Hotel B');

        $this->combustible = CombustibleMother::from('Gas natural', 'combustible-1', Unit::CubicMeter);
        $this->combustible->setEmissionFactor($this->factor('2.0', Unit::CubicMeter));
        $combustibleRepository = CombustibleRepositoryMother::inMemory();
        $combustibleRepository->add($this->combustible);

        $this->transport = TransportMother::freightTrainInTonneKilometre('Tren', (string) TransportId::generate());
        $this->transport->setEmissionFactor($this->factor('0.5', Unit::TonneKilometre));
        $transportRepository = TransportRepositoryMother::inMemory();
        $transportRepository->add($this->transport);

        $this->refrigerantGas = RefrigerantGasMother::fromName('R-410A');
        $this->refrigerantGas->setEmissionFactor($this->factor('2088', Unit::Kilogram));
        $refrigerantGasRepository = RefrigerantGasRepositoryMother::inMemory();
        $refrigerantGasRepository->add($this->refrigerantGas);

        $this->electricityMarketer = ElectricityMarketerMother::fromName('Marketer');
        $this->electricityMarketer->setEmissionFactor($this->factor('0.25', Unit::KWh));
        $electricityMarketerRepository = ElectricityMarketerRepositoryMother::inMemory();
        $electricityMarketerRepository->add($this->electricityMarketer);

        $this->gdOFactor = GdOFactorMother::fromName('GdO');
        $this->gdOFactor->setEmissionFactor($this->factor('0', Unit::KWh));
        $gdOFactorRepository = GdOFactorRepositoryMother::inMemory();
        $gdOFactorRepository->add($this->gdOFactor);

        $authorizer = new ActivityAuthorizer();
        $calculator = EmissionsCalculatorMother::create();
        $combustibleSelector = new CombustibleEmissionFactorSelector($combustibleRepository);
        $transportSelector = new TransportEmissionFactorSelector($transportRepository);
        $refrigerantGasSelector = new RefrigerantGasEmissionFactorSelector($refrigerantGasRepository);
        $electricitySelector = new ElectricityEmissionFactorSelector($electricityMarketerRepository, $gdOFactorRepository);
        $mixResolver = new ElectricityMixEmissionsResolver(new InMemoryElectricityMixRepository(), $calculator);

        $this->handlers = new ActivityAnswerHandlerRegistry([
            new CombustibleAnswerHandler(
                new RegisterCombustibleActivityService($this->activityRepository, $authorizer, $combustibleSelector, $calculator),
                new UpdateCombustibleActivityService($this->activityRepository, $authorizer, $combustibleSelector, $calculator),
            ),
            new TransportAnswerHandler(
                new RegisterTransportActivityService($this->activityRepository, $authorizer, $transportSelector, $calculator),
                new UpdateTransportActivityService($this->activityRepository, $authorizer, $transportSelector, $calculator),
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

        $footprintAuthorizer = new CompanyCarbonFootprintAuthorizer();
        $companyProperties = new CompanyProperties($this->propertyRepository);

        $this->replaceAnswers = new ReplaceCompanyCarbonFootprintScopeAnswersService(
            $this->repository,
            $companyProperties,
            $footprintAuthorizer,
            new StageAnswersProcessor(),
            new StageAnswersSynchronizer($this->handlers, new CarbonFootprintCountryResolver(EventRepositoryMother::inMemory(), $this->propertyRepository)),
            new InMemoryTransactionalSession(),
        );

        $this->sut = new LoadPropertyFootprintsIntoCompanyCarbonFootprintService(
            $this->repository,
            new CompletedPropertyFootprintFinder($this->propertyFootprintRepository),
            $companyProperties,
            $footprintAuthorizer,
            new PropertyActivityAnswerBuilder($this->activityRepository),
            $this->replaceAnswers,
            $this->activityRepository,
        );
    }

    public function testCopiesTheActivitiesOfACompletedPropertyFootprintIntoItsBlock(): void
    {
        $footprint = $this->registeredFootprint();
        $propertyFootprint = $this->completedPropertyFootprint($this->firstPropertyId, [
            [Source::FixedFacilities, new CombustibleAnswer($this->combustible->id(), new Quantity(10.0, Unit::CubicMeter), source: 'Factura 1')],
            [Source::FixedFacilities, new CombustibleAnswer($this->combustible->id(), new Quantity(20.0, Unit::CubicMeter))],
            [Source::RefrigerantLeaks, new RefrigerantGasAnswer($this->refrigerantGas->id(), new Quantity(1.5, Unit::Kilogram), source: 'Mantenimiento')],
        ]);
        $originalIds = $this->allScope1ActivityIds($propertyFootprint);

        $response = ($this->sut)(new LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertTrue($response->isResolved());
        $this->assertSame(2, $response->totalProperties);
        $this->assertSame(1, $response->copiedProperties);
        $this->assertSame(3, $response->activities);
        $this->assertSame([], $response->differences);

        $combustibleIds = $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: $this->firstPropertyId));
        $refrigerantIds = $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::RefrigerantLeaks, propertyId: $this->firstPropertyId));
        $this->assertCount(2, $combustibleIds);
        $this->assertCount(1, $refrigerantIds);

        $combustibles = $this->activityRepository->findByIds($combustibleIds);
        $quantities = array_map(static fn(CombustibleActivity $activity): float => $activity->quantity()->value(), array_values(array_filter($combustibles, static fn($activity): bool => $activity instanceof CombustibleActivity)));
        sort($quantities);
        $this->assertSame([10.0, 20.0], $quantities);
        $sources = array_map(static fn($activity): ?string => $activity instanceof CombustibleActivity ? $activity->source() : 'unexpected', $combustibles);
        $this->assertContains('Factura 1', $sources);
        $this->assertContains(null, $sources);
        foreach ($combustibles as $activity) {
            $this->assertInstanceOf(CombustibleActivity::class, $activity);
            $this->assertTrue($activity->combustibleId()->equals($this->combustible->id()));
        }

        $copiedIds = array_map('strval', [...$combustibleIds, ...$refrigerantIds]);
        $this->assertSame([], array_intersect($copiedIds, array_map('strval', $originalIds)));

        $this->assertSame(array_map('strval', $originalIds), array_map('strval', $this->allScope1ActivityIds($propertyFootprint)));
        $this->assertSame(OrganizationalCarbonFootprintStatus::Started, $footprint->status());
    }

    public function testCopiesTransportWeightAndDistance(): void
    {
        $footprint = $this->registeredFootprint();
        $this->completedPropertyFootprint($this->firstPropertyId, [
            [Source::OwnVehicles, TransportAnswer::fromWeightAndDistance($this->transport->id(), new Quantity(2500.0, Unit::Kilogram), 350.0)],
        ]);

        ($this->sut)(new LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $ids = $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::OwnVehicles, propertyId: $this->firstPropertyId));
        $this->assertCount(1, $ids);
        $activity = $this->activityRepository->findById($ids[0]);
        $this->assertInstanceOf(TransportActivity::class, $activity);
        $weight = $activity->weight();
        $this->assertNotNull($weight);
        $this->assertSame(2500.0, $weight->value());
        $this->assertSame(Unit::Kilogram, $weight->unit());
        $this->assertSame(350.0, $activity->distanceKm());
        $this->assertSame(875.0, $activity->quantity()->value());
    }

    public function testCopiesElectricityMarketerAndGdOFactor(): void
    {
        $footprint = $this->registeredFootprint();
        $this->completedPropertyFootprint($this->firstPropertyId, [
            [Source::BuildingElectricity, new ElectricityAnswer($this->electricityMarketer->id(), $this->gdOFactor->id(), new Quantity(1000.0, Unit::KWh), source: 'Contrato')],
        ], Scope::Scope2);

        $response = ($this->sut)(new LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest($footprint->id(), Scope::Scope2), $this->ownerUser());

        $this->assertSame(1, $response->activities);
        $ids = $footprint->activityIds(new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity, propertyId: $this->firstPropertyId));
        $this->assertCount(1, $ids);
        $activity = $this->activityRepository->findById($ids[0]);
        $this->assertInstanceOf(ElectricityActivity::class, $activity);
        $this->assertNotNull($activity->electricityMarketerId());
        $this->assertTrue($this->electricityMarketer->id()->equals($activity->electricityMarketerId()));
        $this->assertNotNull($activity->gdOFactorId());
        $this->assertTrue($this->gdOFactor->id()->equals($activity->gdOFactorId()));
        $this->assertSame('Contrato', $activity->source());
        $this->assertSame(1000.0, $activity->quantity()->value());
    }

    public function testOnlyCopiedPropertyBlocksAreReplaced(): void
    {
        $footprint = $this->footprintWithActivitiesInEveryBlock();
        $this->completedPropertyFootprint($this->firstPropertyId, [
            [Source::RefrigerantLeaks, new RefrigerantGasAnswer($this->refrigerantGas->id(), new Quantity(3.0, Unit::Kilogram))],
        ]);

        $response = ($this->sut)(new LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertSame(1, $response->copiedProperties);
        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: $this->firstPropertyId)));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::RefrigerantLeaks, propertyId: $this->firstPropertyId)));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: $this->secondPropertyId)));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities)));
    }

    public function testANotCompletedPropertyFootprintIsSkipped(): void
    {
        $footprint = $this->registeredFootprint();
        $this->propertyFootprintRepository->add(PropertyCarbonFootprintMother::withScope2Answered((string) $this->firstPropertyId, (string) PropertyCarbonFootprintId::generate(), self::ACCOUNT_ID, self::YEAR));

        $response = ($this->sut)(new LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertSkipped($response->skipped, $this->firstPropertyId, 'Hotel A', PropertyFootprintSkipReason::NoCompletedFootprint);
    }

    public function testAPropertyWithoutFootprintIsSkipped(): void
    {
        $footprint = $this->registeredFootprint();

        $response = ($this->sut)(new LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertCount(2, $response->skipped);
        $this->assertSkipped($response->skipped, $this->secondPropertyId, 'Hotel B', PropertyFootprintSkipReason::NoCompletedFootprint);
    }

    public function testAPropertyFootprintOfAnotherAccountIsSkipped(): void
    {
        $footprint = $this->registeredFootprint();
        $this->completedPropertyFootprint($this->firstPropertyId, [
            [Source::FixedFacilities, new CombustibleAnswer($this->combustible->id(), new Quantity(10.0, Unit::CubicMeter))],
        ], accountId: 'other-account-id');

        $response = ($this->sut)(new LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertFalse($response->isResolved());
        $this->assertSkipped($response->skipped, $this->firstPropertyId, 'Hotel A', PropertyFootprintSkipReason::NoCompletedFootprint);
        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: $this->firstPropertyId)));
    }

    public function testAPrecalculatedPropertyFootprintIsSkipped(): void
    {
        $footprint = $this->registeredFootprint();
        $propertyFootprint = PropertyCarbonFootprintMother::withEmployeesAnswered((string) $this->firstPropertyId, (string) PropertyCarbonFootprintId::generate(), self::ACCOUNT_ID, self::YEAR);
        $propertyFootprint->markAsPrecalculated();
        $propertyFootprint->complete(new Emissions());
        $this->propertyFootprintRepository->add($propertyFootprint);

        $response = ($this->sut)(new LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertSkipped($response->skipped, $this->firstPropertyId, 'Hotel A', PropertyFootprintSkipReason::Precalculated);
    }

    public function testAPropertyFootprintWithoutActivitiesInTheScopeIsSkippedAndItsBlockKept(): void
    {
        $footprint = $this->footprintWithActivitiesInEveryBlock();
        $this->completedPropertyFootprint($this->firstPropertyId, [
            [Source::BuildingElectricity, new ElectricityAnswer($this->electricityMarketer->id(), null, new Quantity(1000.0, Unit::KWh))],
        ], Scope::Scope2);

        $response = ($this->sut)(new LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertFalse($response->isResolved());
        $this->assertSkipped($response->skipped, $this->firstPropertyId, 'Hotel A', PropertyFootprintSkipReason::NoActivities);
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: $this->firstPropertyId)));
    }

    public function testAFranchisedPropertyIsNeitherCopiedNorSkipped(): void
    {
        $footprint = $this->registeredFootprint();
        $franchisedPropertyId = new PropertyId('property-3');
        $this->propertyRepository->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Hotel Franquiciado', new CompanyId(self::COMPANY_ID), $franchisedPropertyId, OperatingRegime::Franchised));
        $this->completedPropertyFootprint($franchisedPropertyId, [
            [Source::FixedFacilities, new CombustibleAnswer($this->combustible->id(), new Quantity(10.0, Unit::CubicMeter))],
        ]);

        $response = ($this->sut)(new LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertSame(2, $response->totalProperties);
        $this->assertSame(0, $response->copiedProperties);
        $this->assertNotContains('property-3', array_map(static fn($skipped): string => (string) $skipped->propertyId, $response->skipped));
        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: $franchisedPropertyId)));
    }

    public function testNothingChangesWhenNoPropertyIsCopied(): void
    {
        $footprint = $this->footprintWithActivitiesInEveryBlock();
        DomainEventPublisher::instance()->reset();
        $activityIdsBefore = $this->allScope1CompanyActivityIds($footprint);

        $response = ($this->sut)(new LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertFalse($response->isResolved());
        $this->assertSame(0, $response->activities);
        $this->assertSame([], $response->includedSources);
        $this->assertSame($activityIdsBefore, $this->allScope1CompanyActivityIds($footprint));
    }

    public function testACompanyWithoutPropertiesReturnsZero(): void
    {
        $footprint = CompanyCarbonFootprintMother::from('company-without-properties', (string) CompanyCarbonFootprintId::generate(), self::ACCOUNT_ID, self::YEAR);
        $this->repository->add($footprint);

        $response = ($this->sut)(new LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertSame(0, $response->totalProperties);
        $this->assertFalse($response->isResolved());
        $this->assertSame([], $response->skipped);
    }

    public function testReportsADifferenceWhenTheEmissionFactorChanged(): void
    {
        $footprint = $this->registeredFootprint();
        $this->completedPropertyFootprint($this->firstPropertyId, [
            [Source::FixedFacilities, new CombustibleAnswer($this->combustible->id(), new Quantity(10.0, Unit::CubicMeter))],
        ]);
        $this->combustible->setEmissionFactor($this->factor('3.0', Unit::CubicMeter));

        $response = ($this->sut)(new LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertCount(1, $response->differences);
        $difference = $response->differences[0];
        $this->assertTrue($difference->propertyId->equals($this->firstPropertyId));
        $this->assertSame('Hotel A', $difference->propertyName);
        $this->assertEqualsWithDelta(20.0, (float) $difference->propertyFootprintCo2eq, 0.0001);
        $this->assertEqualsWithDelta(30.0, (float) $difference->recalculatedCo2eq, 0.0001);
    }

    public function testAMissingEmissionFactorLeavesTheCompanyFootprintUntouched(): void
    {
        $footprint = $this->footprintWithActivitiesInEveryBlock();
        $activityIdsBefore = $this->allScope1CompanyActivityIds($footprint);
        $this->completedPropertyFootprint($this->firstPropertyId, [
            [Source::FixedFacilities, new CombustibleAnswer($this->combustible->id(), new Quantity(10.0, Unit::CubicMeter))],
        ]);
        $this->combustible->removeEmissionFactor(self::YEAR, EmissionFactorGroupMother::defaultId());

        try {
            ($this->sut)(new LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest($footprint->id(), Scope::Scope1), $this->ownerUser());
            $this->fail('Expected InvalidArgumentException');
        } catch (\InvalidArgumentException) {
        }

        $this->assertSame($activityIdsBefore, $this->allScope1CompanyActivityIds($footprint));
    }

    public function testNoDifferenceIsReportedWhenEmissionsMatch(): void
    {
        $footprint = $this->registeredFootprint();
        $this->completedPropertyFootprint($this->firstPropertyId, [
            [Source::FixedFacilities, new CombustibleAnswer($this->combustible->id(), new Quantity(10.0, Unit::CubicMeter))],
            [Source::OwnVehicles, TransportAnswer::fromWeightAndDistance($this->transport->id(), new Quantity(2500.0, Unit::Kilogram), 350.0)],
        ]);

        $response = ($this->sut)(new LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertTrue($response->isResolved());
        $this->assertSame([], $response->differences);
    }

    public function testAnExcludedCompanySourceIsIncludedAgain(): void
    {
        $footprint = $this->registeredFootprint();
        $footprint->excludeSource(Scope::Scope1, Source::RefrigerantLeaks, ExclusionReason::NoDataAvailable);
        $this->completedPropertyFootprint($this->firstPropertyId, [
            [Source::RefrigerantLeaks, new RefrigerantGasAnswer($this->refrigerantGas->id(), new Quantity(3.0, Unit::Kilogram))],
        ]);

        $response = ($this->sut)(new LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest($footprint->id(), Scope::Scope1), $this->ownerUser());

        $this->assertSame([Source::RefrigerantLeaks], $response->includedSources);
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::RefrigerantLeaks, propertyId: $this->firstPropertyId)));
    }

    public function testAPrecalculatedCompanyFootprintIsRejected(): void
    {
        $footprint = $this->registeredFootprint();
        $footprint->markAsPrecalculated();
        $this->completedPropertyFootprint($this->firstPropertyId, [
            [Source::FixedFacilities, new CombustibleAnswer($this->combustible->id(), new Quantity(10.0, Unit::CubicMeter))],
        ]);

        try {
            ($this->sut)(new LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest($footprint->id(), Scope::Scope1), $this->ownerUser());
            $this->fail('Expected InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('No se pueden cargar huellas de propiedad en una huella ya calculada', $e->getMessage());
        }

        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: $this->firstPropertyId)));
    }

    public function testScope3IsRejected(): void
    {
        $footprint = $this->registeredFootprint();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Solo se pueden cargar los alcances 1 y 2');

        ($this->sut)(new LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest($footprint->id(), Scope::Scope3), $this->ownerUser());
    }

    public function testACompletedCompanyFootprintIsRejected(): void
    {
        $footprint = CompanyCarbonFootprintMother::withEmployeesAnswered(self::COMPANY_ID, (string) CompanyCarbonFootprintId::generate(), self::ACCOUNT_ID, self::YEAR);
        $footprint->complete(new Emissions());
        $this->repository->add($footprint);
        $this->completedPropertyFootprint($this->firstPropertyId, [
            [Source::FixedFacilities, new CombustibleAnswer($this->combustible->id(), new Quantity(10.0, Unit::CubicMeter))],
        ]);

        try {
            ($this->sut)(new LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest($footprint->id(), Scope::Scope1), $this->ownerUser());
            $this->fail('Expected InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('No se pueden modificar los alcances de una huella completada', $e->getMessage());
        }

        $this->assertTrue($footprint->isCompleted());
        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: $this->firstPropertyId)));
    }

    public function testAnOwnerOfAnotherAccountIsDenied(): void
    {
        $footprint = $this->registeredFootprint('other-account-id');

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest($footprint->id(), Scope::Scope1), $this->ownerUser());
    }

    public function testIsHandled(): void
    {
        $this->assertTrue($this->sut->isHandled(new LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest(CompanyCarbonFootprintId::generate(), Scope::Scope1)));
    }

    /** @param list<array{Source, ActivityAnswer}> $answers */
    private function completedPropertyFootprint(PropertyId $propertyId, array $answers, Scope $scope = Scope::Scope1, string $accountId = self::ACCOUNT_ID): PropertyCarbonFootprint
    {
        $propertyFootprint = PropertyCarbonFootprintMother::withEmployeesAnswered((string) $propertyId, (string) PropertyCarbonFootprintId::generate(), $accountId, self::YEAR, EmissionFactorGroupMother::defaultId());

        foreach ($answers as [$source, $answer]) {
            $activityId = $this->handlers->forActivityType($answer->type())->register(
                $answer,
                self::YEAR,
                EmissionFactorGroupMother::defaultId(),
                new AccountId($accountId),
                $this->ownerUser($accountId),
            );
            $propertyFootprint->addActivity($activityId, new ActivityPlacement($scope, $source));
        }

        $propertyFootprint->complete(new Emissions());
        $this->propertyFootprintRepository->add($propertyFootprint);

        return $propertyFootprint;
    }

    /** @return array<ActivityId> */
    private function allScope1ActivityIds(PropertyCarbonFootprint $propertyFootprint): array
    {
        $ids = [];
        foreach (Scope::Scope1->sections() as $section) {
            $ids = [...$ids, ...$propertyFootprint->activityIds(new ActivityPlacement(Scope::Scope1, $section->source))];
        }

        return $ids;
    }

    /** @return list<string> */
    private function allScope1CompanyActivityIds(CompanyCarbonFootprint $footprint): array
    {
        $ids = [];
        foreach ([null, $this->firstPropertyId, $this->secondPropertyId] as $propertyId) {
            foreach (Scope::Scope1->sections() as $section) {
                foreach ($footprint->activityIds(new ActivityPlacement(Scope::Scope1, $section->source, propertyId: $propertyId)) as $id) {
                    $ids[] = (string) $id;
                }
            }
        }

        return $ids;
    }

    /** @param list<SkippedPropertyFootprint> $skipped */
    private function assertSkipped(array $skipped, PropertyId $propertyId, string $name, PropertyFootprintSkipReason $reason): void
    {
        foreach ($skipped as $item) {
            if ($item->propertyId->equals($propertyId)) {
                $this->assertSame($name, $item->propertyName);
                $this->assertSame($reason, $item->reason);

                return;
            }
        }

        $this->fail(sprintf('Property "%s" was not skipped', $propertyId));
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
            [new CombustibleAnswer($this->combustible->id(), new Quantity(5.0, Unit::CubicMeter))],
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

    private function ownerUser(string $accountId = self::ACCOUNT_ID): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId($accountId));
    }
}
