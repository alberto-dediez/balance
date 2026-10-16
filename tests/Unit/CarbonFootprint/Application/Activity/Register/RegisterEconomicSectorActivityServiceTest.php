<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\EconomicSectorEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterEconomicSectorActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterEconomicSectorActivityService;
use App\CarbonFootprint\Domain\Model\Activity\EconomicSectorActivity;
use App\CarbonFootprint\Domain\Model\Activity\EconomicSectorActivityRegistered;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSector;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorId;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepository;
use App\CarbonFootprint\Domain\Model\IncompatibleUnitsException;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterEconomicSectorActivityServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $activityRepository = ActivityRepositoryMother::inMemory();
        $economicSector = $this->economicSectorWithFactor();
        $sut = $this->createSUT($activityRepository, $this->repositoryWith($economicSector));

        $result = $sut($this->request($economicSector->id(), Unit::Euro, 100.0), UserMother::adminUser());

        $activity = $activityRepository->findById($result->activityId);

        $this->assertInstanceOf(EconomicSectorActivity::class, $activity);
        $this->assertTrue($economicSector->id()->equals($activity->economicSectorId()));
        $this->assertEqualsWithDelta(150.0, (float) $activity->emissions()->co2eq(), 1e-9);
        $this->assertInstanceOf(EconomicSectorActivityRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testConvertsForeignCurrencyQuantity(): void
    {
        $exchangeRates = ExchangeRateRepositoryMother::inMemory();
        $exchangeRates->add(ExchangeRateMother::registered(currency: Unit::UsDollar, rate: '1.25'));

        $activityRepository = ActivityRepositoryMother::inMemory();
        $economicSector = $this->economicSectorWithFactor();
        $sut = new RegisterEconomicSectorActivityService(
            $activityRepository,
            new ActivityAuthorizer(),
            new EconomicSectorEmissionFactorSelector($this->repositoryWith($economicSector)),
            EmissionsCalculatorMother::create($exchangeRates),
        );

        $result = $sut($this->request($economicSector->id(), Unit::UsDollar, 250.0), UserMother::adminUser());

        $activity = $activityRepository->findById($result->activityId);

        $this->assertInstanceOf(EconomicSectorActivity::class, $activity);
        $this->assertEqualsWithDelta(300.0, (float) $activity->emissions()->co2eq(), 1e-9);
        $this->assertSame(Unit::UsDollar, $activity->quantity()->unit());
    }

    public function testThrowsWhenQuantityMagnitudeDiffersFromFactor(): void
    {
        $economicSector = $this->economicSectorWithFactor();
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), $this->repositoryWith($economicSector));

        $this->expectException(IncompatibleUnitsException::class);

        $sut($this->request($economicSector->id(), Unit::Kilogram, 100.0), UserMother::adminUser());
    }

    public function testThrowsWhenNoFactorForYear(): void
    {
        $economicSector = EconomicSectorMother::registeredEconomicSector();
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), $this->repositoryWith($economicSector));

        $this->expectException(\InvalidArgumentException::class);

        $sut($this->request($economicSector->id(), Unit::Euro, 100.0), UserMother::adminUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), EconomicSectorRepositoryMother::inMemory());

        $this->expectException(UnauthorizedActionException::class);

        $sut($this->request(EconomicSectorId::generate(), Unit::Euro, 100.0), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), EconomicSectorRepositoryMother::inMemory());

        $this->assertTrue($sut->isHandled($this->request(EconomicSectorId::generate(), Unit::Euro, 100.0)));
    }

    private function economicSectorWithFactor(): EconomicSector
    {
        $economicSector = EconomicSectorMother::registeredEconomicSector();
        $economicSector->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Euro));

        return $economicSector;
    }

    private function repositoryWith(EconomicSector $economicSector): EconomicSectorRepository
    {
        $repository = EconomicSectorRepositoryMother::inMemory();
        $repository->add($economicSector);

        return $repository;
    }

    private function request(EconomicSectorId $economicSectorId, Unit $unit, float $quantity): RegisterEconomicSectorActivityRequest
    {
        return new RegisterEconomicSectorActivityRequest(
            accountId: new AccountId('account-id'),
            economicSectorId: $economicSectorId,
            quantity: ActivityMother::quantity($unit, $quantity),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        );
    }

    private function createSUT(InMemoryActivityRepository $activityRepository, EconomicSectorRepository $economicSectorRepository): RegisterEconomicSectorActivityService
    {
        return new RegisterEconomicSectorActivityService(
            $activityRepository,
            new ActivityAuthorizer(),
            new EconomicSectorEmissionFactorSelector($economicSectorRepository),
            EmissionsCalculatorMother::create(),
        );
    }
}
