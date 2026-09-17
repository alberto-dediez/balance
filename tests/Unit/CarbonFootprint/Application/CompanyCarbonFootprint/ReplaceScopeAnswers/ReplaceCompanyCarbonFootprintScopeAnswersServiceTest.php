<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CompanyCarbonFootprint\ReplaceScopeAnswers;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Answer\ActivityAnswerHandlerRegistry;
use App\CarbonFootprint\Application\Activity\Answer\CombustibleAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\RefrigerantGasAnswerHandler;
use App\CarbonFootprint\Application\Activity\CombustibleEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
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
use App\CarbonFootprint\Domain\Model\Activity\Answer\RefrigerantGasAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Combustible\Combustible;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGas;
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
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepositoryMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ReplaceCompanyCarbonFootprintScopeAnswersServiceTest extends TestCase
{
    private const YEAR = 2025;
    private const COMPANY_ID = 'company-1';

    private InMemoryActivityRepository $activityRepository;
    private InMemoryCompanyCarbonFootprintRepository $repository;
    private InMemoryPropertyRepository $propertyRepository;
    private Combustible $combustible;
    private RefrigerantGas $refrigerantGas;
    private PropertyId $firstPropertyId;
    private PropertyId $secondPropertyId;
    private ReplaceCompanyCarbonFootprintScopeAnswersService $sut;

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

        $this->refrigerantGas = RefrigerantGasMother::fromName('R-410A');
        $this->refrigerantGas->setEmissionFactor($this->factor('2088', Unit::Kilogram));
        $refrigerantGasRepository = RefrigerantGasRepositoryMother::inMemory();
        $refrigerantGasRepository->add($this->refrigerantGas);

        $authorizer = new ActivityAuthorizer();
        $calculator = new EmissionsCalculator();
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

        $this->sut = new ReplaceCompanyCarbonFootprintScopeAnswersService(
            $this->repository,
            $this->propertyRepository,
            new CompanyCarbonFootprintAuthorizer(),
            new StageAnswersProcessor(),
            new StageAnswersSynchronizer($handlers, new CarbonFootprintCountryResolver(EventRepositoryMother::inMemory(), $this->propertyRepository)),
        );
    }

    public function testOnlyTheReceivedBlocksAreReplaced(): void
    {
        $footprint = $this->footprintWithActivitiesInEveryBlock();

        $response = ($this->sut)(new ReplaceCompanyCarbonFootprintScopeAnswersRequest(
            $footprint->id(),
            Scope::Scope1,
            [new PropertySectionAnswers($this->firstPropertyId, [$this->refrigerantGasSection()])],
        ), $this->ownerUser());

        $this->assertSame(1, $response->activities);
        $this->assertSame(1, $response->blocks);
        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: $this->firstPropertyId)));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::RefrigerantLeaks, propertyId: $this->firstPropertyId)));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: $this->secondPropertyId)));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities)));
        $this->assertSame(OrganizationalCarbonFootprintStatus::Started, $footprint->status());
    }

    public function testTheUnassignedBlockIsReplacedWhenSent(): void
    {
        $footprint = $this->footprintWithActivitiesInEveryBlock();

        ($this->sut)(new ReplaceCompanyCarbonFootprintScopeAnswersRequest(
            $footprint->id(),
            Scope::Scope1,
            [new PropertySectionAnswers(null, [])],
        ), $this->ownerUser());

        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities)));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: $this->firstPropertyId)));
    }

    public function testAPropertyOutsideTheCompanyIsRejected(): void
    {
        $footprint = $this->registeredFootprint();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Property does not belong to company');

        ($this->sut)(new ReplaceCompanyCarbonFootprintScopeAnswersRequest(
            $footprint->id(),
            Scope::Scope1,
            [new PropertySectionAnswers(new PropertyId('property-9'), [$this->combustibleSection()])],
        ), $this->ownerUser());
    }

    public function testADuplicatedBlockIsRejected(): void
    {
        $footprint = $this->registeredFootprint();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicated property block');

        ($this->sut)(new ReplaceCompanyCarbonFootprintScopeAnswersRequest(
            $footprint->id(),
            Scope::Scope1,
            [
                new PropertySectionAnswers($this->firstPropertyId, [$this->combustibleSection()]),
                new PropertySectionAnswers($this->firstPropertyId, [$this->combustibleSection()]),
            ],
        ), $this->ownerUser());
    }

    public function testAnExcludedSourceIsIncludedOnceEvenWhenSeveralBlocksBringAnswers(): void
    {
        $footprint = $this->registeredFootprint();
        $footprint->excludeSource(Scope::Scope1, Source::RefrigerantLeaks, ExclusionReason::NoDataAvailable);

        $response = ($this->sut)(new ReplaceCompanyCarbonFootprintScopeAnswersRequest(
            $footprint->id(),
            Scope::Scope1,
            [
                new PropertySectionAnswers($this->firstPropertyId, [$this->refrigerantGasSection()]),
                new PropertySectionAnswers($this->secondPropertyId, [$this->refrigerantGasSection()]),
            ],
        ), $this->ownerUser());

        $this->assertSame([Source::RefrigerantLeaks], $response->includedSources);
        $this->assertSame(2, $response->activities);
        $this->assertFalse($footprint->isSourceExcluded(Scope::Scope1, Source::RefrigerantLeaks));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::RefrigerantLeaks, propertyId: $this->firstPropertyId)));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::RefrigerantLeaks, propertyId: $this->secondPropertyId)));
    }

    public function testOtherAccountIsDenied(): void
    {
        $footprint = $this->registeredFootprint('other-account-id');

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new ReplaceCompanyCarbonFootprintScopeAnswersRequest($footprint->id(), Scope::Scope1, []), $this->ownerUser());
    }

    public function testIsHandled(): void
    {
        $this->assertTrue($this->sut->isHandled(new ReplaceCompanyCarbonFootprintScopeAnswersRequest(CompanyCarbonFootprintId::generate(), Scope::Scope1)));
    }

    private function footprintWithActivitiesInEveryBlock(): CompanyCarbonFootprint
    {
        $footprint = $this->registeredFootprint();

        ($this->sut)(new ReplaceCompanyCarbonFootprintScopeAnswersRequest(
            $footprint->id(),
            Scope::Scope1,
            [
                new PropertySectionAnswers(null, [$this->combustibleSection()]),
                new PropertySectionAnswers($this->firstPropertyId, [$this->combustibleSection()]),
                new PropertySectionAnswers($this->secondPropertyId, [$this->combustibleSection()]),
            ],
        ), $this->ownerUser());

        return $footprint;
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
            [new RefrigerantGasAnswer($this->refrigerantGas->id(), new Quantity(3.0, Unit::Kilogram))],
        );
    }

    private function registeredFootprint(string $accountId = 'account-id-test'): CompanyCarbonFootprint
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
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }
}
