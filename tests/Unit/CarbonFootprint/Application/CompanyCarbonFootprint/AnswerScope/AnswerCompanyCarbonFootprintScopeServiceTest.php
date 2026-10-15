<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerScope;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Answer\ActivityAnswerHandlerRegistry;
use App\CarbonFootprint\Application\Activity\Answer\CombustibleAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\ElectricityAnswerHandler;
use App\CarbonFootprint\Application\Activity\CombustibleEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\ElectricityEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\ElectricityMixEmissionsResolver;
use App\CarbonFootprint\Application\Activity\Register\RegisterCombustibleActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterElectricityActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateCombustibleActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateElectricityActivityService;
use App\CarbonFootprint\Application\CarbonFootprint\CarbonFootprintCountryResolver;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersProcessor;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersSynchronizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerScope\AnswerCompanyCarbonFootprintScopeRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerScope\AnswerCompanyCarbonFootprintScopeService;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerScope\PropertySectionAnswers;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\CombustibleAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ElectricityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivity;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepositoryMother;
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
    private AnswerCompanyCarbonFootprintScopeService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->activityRepository = ActivityRepositoryMother::inMemory();
        $this->repository = CompanyCarbonFootprintRepositoryMother::inMemory();
        $this->propertyRepository = PropertyRepositoryMother::inMemory();

        $this->firstPropertyId = new PropertyId('property-1');
        $this->secondPropertyId = new PropertyId('property-2');

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

        $authorizer = new ActivityAuthorizer();
        $calculator = EmissionsCalculatorMother::create();
        $combustibleSelector = new CombustibleEmissionFactorSelector($combustibleRepository);
        $electricitySelector = new ElectricityEmissionFactorSelector($electricityMarketerRepository, GdOFactorRepositoryMother::inMemory());
        $mixResolver = new ElectricityMixEmissionsResolver($electricityMixRepository, $calculator);

        $handlers = new ActivityAnswerHandlerRegistry([
            new CombustibleAnswerHandler(
                new RegisterCombustibleActivityService($this->activityRepository, $authorizer, $combustibleSelector, $calculator),
                new UpdateCombustibleActivityService($this->activityRepository, $authorizer, $combustibleSelector, $calculator),
            ),
            new ElectricityAnswerHandler(
                new RegisterElectricityActivityService($this->activityRepository, $authorizer, $electricitySelector, $calculator, $mixResolver),
                new UpdateElectricityActivityService($this->activityRepository, $authorizer, $electricitySelector, $calculator, $mixResolver),
            ),
        ]);

        $this->sut = new AnswerCompanyCarbonFootprintScopeService(
            $this->repository,
            $this->propertyRepository,
            new CompanyCarbonFootprintAuthorizer(),
            new StageAnswersProcessor(),
            new StageAnswersSynchronizer($handlers, new CarbonFootprintCountryResolver(EventRepositoryMother::inMemory(), $this->propertyRepository)),
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
