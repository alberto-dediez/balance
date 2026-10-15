<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PropertyCarbonFootprint\ReplaceScopeAnswers;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Answer\ActivityAnswerHandlerRegistry;
use App\CarbonFootprint\Application\Activity\Answer\CombustibleAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\RefrigerantGasAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\RoadTransportAnswerHandler;
use App\CarbonFootprint\Application\Activity\CombustibleEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\RefrigerantGasEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterCombustibleActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterRefrigerantGasActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterRoadTransportActivityService;
use App\CarbonFootprint\Application\Activity\RoadTransportEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Update\UpdateCombustibleActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateRefrigerantGasActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateRoadTransportActivityService;
use App\CarbonFootprint\Application\CarbonFootprint\CarbonFootprintCountryResolver;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersProcessor;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersSynchronizer;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\ReplaceScopeAnswers\ReplacePropertyCarbonFootprintScopeAnswersRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\ReplaceScopeAnswers\ReplacePropertyCarbonFootprintScopeAnswersService;
use App\CarbonFootprint\Domain\Model\Activity\Answer\CombustibleAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\RefrigerantGasAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\RoadTransportAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Combustible\Combustible;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGas;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransport;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Property\PropertyId;
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
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
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

class ReplacePropertyCarbonFootprintScopeAnswersServiceTest extends TestCase
{
    private const YEAR = 2025;

    private InMemoryActivityRepository $activityRepository;
    private InMemoryPropertyCarbonFootprintRepository $repository;
    private InMemoryPropertyRepository $propertyRepository;
    private Combustible $combustible;
    private RoadTransport $roadTransport;
    private RefrigerantGas $refrigerantGas;
    private ReplacePropertyCarbonFootprintScopeAnswersService $sut;

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

        $this->roadTransport = RoadTransportMother::fromName('Gasolina');
        $this->roadTransport->setEmissionFactor($this->factor('2.3', Unit::Kilogram));
        $roadTransportRepository = RoadTransportRepositoryMother::inMemory();
        $roadTransportRepository->add($this->roadTransport);

        $this->refrigerantGas = RefrigerantGasMother::fromName('R-410A');
        $this->refrigerantGas->setEmissionFactor($this->factor('2088', Unit::Kilogram));
        $refrigerantGasRepository = RefrigerantGasRepositoryMother::inMemory();
        $refrigerantGasRepository->add($this->refrigerantGas);

        $authorizer = new ActivityAuthorizer();
        $calculator = EmissionsCalculatorMother::create();

        $combustibleSelector = new CombustibleEmissionFactorSelector($combustibleRepository);
        $roadTransportSelector = new RoadTransportEmissionFactorSelector($roadTransportRepository);
        $refrigerantGasSelector = new RefrigerantGasEmissionFactorSelector($refrigerantGasRepository);

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
        ]);

        $this->sut = new ReplacePropertyCarbonFootprintScopeAnswersService(
            $this->repository,
            new PropertyCarbonFootprintAuthorizer(),
            new StageAnswersProcessor(),
            new StageAnswersSynchronizer($handlers, new CarbonFootprintCountryResolver(EventRepositoryMother::inMemory(), $this->propertyRepository)),
        );
    }

    public function testReplacesEverySourceOfTheScopeEvenWhenOnlyOneIsSent(): void
    {
        $footprint = $this->footprintWithManualScope1Activities();

        $response = ($this->sut)(new ReplacePropertyCarbonFootprintScopeAnswersRequest(
            $footprint->id(),
            Scope::Scope1,
            [$this->combustibleSection(2)],
        ), $this->ownerUser());

        $this->assertSame(2, $response->activities);
        $this->assertSame([], $response->includedSources);
        $this->assertCount(2, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities)));
        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::OwnVehicles)));
        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::RefrigerantLeaks)));
    }

    public function testEmptyAnswersEmptyTheScope(): void
    {
        $footprint = $this->footprintWithManualScope1Activities();

        $response = ($this->sut)(new ReplacePropertyCarbonFootprintScopeAnswersRequest($footprint->id(), Scope::Scope1, []), $this->ownerUser());

        $this->assertSame(0, $response->activities);
        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities)));
        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::OwnVehicles)));
        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::RefrigerantLeaks)));
    }

    public function testIncludesAnExcludedSourceThatReceivesAnswers(): void
    {
        $footprint = $this->registeredFootprint();
        $footprint->excludeSource(Scope::Scope1, Source::RefrigerantLeaks, ExclusionReason::NoDataAvailable);

        $response = ($this->sut)(new ReplacePropertyCarbonFootprintScopeAnswersRequest(
            $footprint->id(),
            Scope::Scope1,
            [$this->refrigerantGasSection()],
        ), $this->ownerUser());

        $this->assertSame([Source::RefrigerantLeaks], $response->includedSources);
        $this->assertFalse($footprint->isSourceExcluded(Scope::Scope1, Source::RefrigerantLeaks));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::RefrigerantLeaks)));
    }

    public function testKeepsAnExclusionWithoutAnswers(): void
    {
        $footprint = $this->registeredFootprint();
        $footprint->excludeSource(Scope::Scope1, Source::RefrigerantLeaks, ExclusionReason::NoDataAvailable);

        $response = ($this->sut)(new ReplacePropertyCarbonFootprintScopeAnswersRequest(
            $footprint->id(),
            Scope::Scope1,
            [$this->combustibleSection(1)],
        ), $this->ownerUser());

        $this->assertSame([], $response->includedSources);
        $this->assertTrue($footprint->isSourceExcluded(Scope::Scope1, Source::RefrigerantLeaks));
    }

    public function testDoesNotChangeTheStatus(): void
    {
        $footprint = $this->registeredFootprint();

        ($this->sut)(new ReplacePropertyCarbonFootprintScopeAnswersRequest($footprint->id(), Scope::Scope1, [$this->combustibleSection(1)]), $this->ownerUser());

        $this->assertSame(OrganizationalCarbonFootprintStatus::Started, $footprint->status());
    }

    public function testASectionOfAnotherScopeIsRejected(): void
    {
        $footprint = $this->registeredFootprint();

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new ReplacePropertyCarbonFootprintScopeAnswersRequest($footprint->id(), Scope::Scope2, [$this->combustibleSection(1)]), $this->ownerUser());
    }

    public function testOtherAccountIsDenied(): void
    {
        $footprint = $this->registeredFootprint('other-account-id');

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new ReplacePropertyCarbonFootprintScopeAnswersRequest($footprint->id(), Scope::Scope1, []), $this->ownerUser());
    }

    public function testIsHandled(): void
    {
        $this->assertTrue($this->sut->isHandled(new ReplacePropertyCarbonFootprintScopeAnswersRequest(PropertyCarbonFootprintId::generate(), Scope::Scope1)));
    }

    private function footprintWithManualScope1Activities(): PropertyCarbonFootprint
    {
        $footprint = $this->registeredFootprint();

        ($this->sut)(new ReplacePropertyCarbonFootprintScopeAnswersRequest(
            $footprint->id(),
            Scope::Scope1,
            [$this->combustibleSection(1), $this->roadTransportSection(), $this->refrigerantGasSection()],
        ), $this->ownerUser());

        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::FixedFacilities)));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::OwnVehicles)));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope1, Source::RefrigerantLeaks)));

        return $footprint;
    }

    private function combustibleSection(int $rows): SectionAnswer
    {
        $answers = [];

        for ($i = 0; $i < $rows; ++$i) {
            $answers[] = new CombustibleAnswer($this->combustible->id(), new Quantity(1000.0, Unit::KWh), source: 'Bioscore');
        }

        return new SectionAnswer(Scope::Scope1->sections()[0], $answers);
    }

    private function roadTransportSection(): SectionAnswer
    {
        return new SectionAnswer(
            Scope::Scope1->sections()[1],
            [new RoadTransportAnswer($this->roadTransport->id(), new Quantity(200.0, Unit::Kilogram))],
        );
    }

    private function refrigerantGasSection(): SectionAnswer
    {
        return new SectionAnswer(
            Scope::Scope1->sections()[2],
            [new RefrigerantGasAnswer($this->refrigerantGas->id(), new Quantity(3.0, Unit::Kilogram))],
        );
    }

    private function registeredFootprint(string $accountId = 'account-id-test'): PropertyCarbonFootprint
    {
        $property = PropertyMother::from(propertyId: PropertyId::generate(), accountId: new AccountId($accountId));
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
