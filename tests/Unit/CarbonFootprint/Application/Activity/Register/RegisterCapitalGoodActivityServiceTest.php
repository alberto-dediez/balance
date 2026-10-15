<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\CapitalGoodEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterCapitalGoodActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterCapitalGoodActivityService;
use App\CarbonFootprint\Domain\Model\Activity\CapitalGoodActivity;
use App\CarbonFootprint\Domain\Model\Activity\CapitalGoodActivityRegistered;
use App\CarbonFootprint\Domain\Model\Activity\InvalidAcquisitionYearException;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGood;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepository;
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
use App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterCapitalGoodActivityServiceTest extends TestCase
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
        $capitalGood = $this->capitalGoodWithFactor();
        $sut = $this->createSUT($activityRepository, $this->repositoryWith($capitalGood));

        $result = $sut($this->request($capitalGood->id(), Unit::Euro, 100.0, 2023), UserMother::adminUser());

        $activity = $activityRepository->findById($result->activityId);

        $this->assertInstanceOf(CapitalGoodActivity::class, $activity);
        $this->assertTrue($capitalGood->id()->equals($activity->capitalGoodId()));
        $this->assertEqualsWithDelta(150.0, (float) $activity->emissions()->co2eq(), 1e-9);
        $this->assertSame(2023, $activity->acquisitionYear());
        $this->assertInstanceOf(CapitalGoodActivityRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testConvertsForeignCurrencyQuantity(): void
    {
        $exchangeRates = ExchangeRateRepositoryMother::inMemory();
        $exchangeRates->add(ExchangeRateMother::registered(currency: Unit::UsDollar, rate: '1.25'));

        $activityRepository = ActivityRepositoryMother::inMemory();
        $capitalGood = $this->capitalGoodWithFactor();
        $sut = new RegisterCapitalGoodActivityService(
            $activityRepository,
            new ActivityAuthorizer(),
            new CapitalGoodEmissionFactorSelector($this->repositoryWith($capitalGood)),
            EmissionsCalculatorMother::create($exchangeRates),
        );

        $result = $sut($this->request($capitalGood->id(), Unit::UsDollar, 250.0), UserMother::adminUser());

        $activity = $activityRepository->findById($result->activityId);

        $this->assertInstanceOf(CapitalGoodActivity::class, $activity);
        $this->assertEqualsWithDelta(300.0, (float) $activity->emissions()->co2eq(), 1e-9);
        $this->assertSame(Unit::UsDollar, $activity->quantity()->unit());
    }

    public function testThrowsWhenQuantityMagnitudeDiffersFromFactor(): void
    {
        $capitalGood = CapitalGoodMother::registeredCapitalGood();
        $capitalGood->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::SquareMeter));
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), $this->repositoryWith($capitalGood));

        $this->expectException(IncompatibleUnitsException::class);

        $sut($this->request($capitalGood->id(), Unit::Kilogram, 100.0), UserMother::adminUser());
    }

    public function testThrowsWhenNoFactorForYear(): void
    {
        $capitalGood = CapitalGoodMother::registeredCapitalGood();
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), $this->repositoryWith($capitalGood));

        $this->expectException(\InvalidArgumentException::class);

        $sut($this->request($capitalGood->id(), Unit::Euro, 100.0), UserMother::adminUser());
    }

    public function testThrowsWhenAcquisitionYearIsOutOfRange(): void
    {
        $capitalGood = $this->capitalGoodWithFactor();
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), $this->repositoryWith($capitalGood));

        $this->expectException(InvalidAcquisitionYearException::class);

        $sut($this->request($capitalGood->id(), Unit::Euro, 100.0, 2201), UserMother::adminUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), CapitalGoodRepositoryMother::inMemory());

        $this->expectException(UnauthorizedActionException::class);

        $sut($this->request(CapitalGoodId::generate(), Unit::Euro, 100.0), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), CapitalGoodRepositoryMother::inMemory());

        $this->assertTrue($sut->isHandled($this->request(CapitalGoodId::generate(), Unit::Euro, 100.0)));
    }

    private function capitalGoodWithFactor(): CapitalGood
    {
        $capitalGood = CapitalGoodMother::registeredCapitalGood();
        $capitalGood->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Euro));

        return $capitalGood;
    }

    private function repositoryWith(CapitalGood $capitalGood): CapitalGoodRepository
    {
        $repository = CapitalGoodRepositoryMother::inMemory();
        $repository->add($capitalGood);

        return $repository;
    }

    private function request(CapitalGoodId $capitalGoodId, Unit $unit, float $quantity, ?int $acquisitionYear = null): RegisterCapitalGoodActivityRequest
    {
        return new RegisterCapitalGoodActivityRequest(
            accountId: new AccountId('account-id'),
            capitalGoodId: $capitalGoodId,
            quantity: ActivityMother::quantity($unit, $quantity),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
            acquisitionYear: $acquisitionYear,
        );
    }

    private function createSUT(InMemoryActivityRepository $activityRepository, CapitalGoodRepository $capitalGoodRepository): RegisterCapitalGoodActivityService
    {
        return new RegisterCapitalGoodActivityService(
            $activityRepository,
            new ActivityAuthorizer(),
            new CapitalGoodEmissionFactorSelector($capitalGoodRepository),
            EmissionsCalculatorMother::create(),
        );
    }
}
