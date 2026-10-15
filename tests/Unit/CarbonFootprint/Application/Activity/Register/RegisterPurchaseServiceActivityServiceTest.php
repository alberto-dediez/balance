<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\PurchaseServiceEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterPurchaseServiceActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterPurchaseServiceActivityService;
use App\CarbonFootprint\Domain\Model\Activity\PurchaseServiceActivity;
use App\CarbonFootprint\Domain\Model\Activity\PurchaseServiceActivityRegistered;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseService;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterPurchaseServiceActivityServiceTest extends TestCase
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
        $purchaseService = $this->purchaseServiceWithFactor();
        $sut = $this->createSUT($activityRepository, $this->repositoryWith($purchaseService));

        $result = $sut($this->request($purchaseService->id(), Unit::Euro, 100.0), UserMother::adminUser());

        $activity = $activityRepository->findById($result->activityId);

        $this->assertInstanceOf(PurchaseServiceActivity::class, $activity);
        $this->assertTrue($purchaseService->id()->equals($activity->purchaseServiceId()));
        $this->assertEqualsWithDelta(150.0, (float) $activity->emissions()->co2eq(), 1e-9);
        $this->assertInstanceOf(PurchaseServiceActivityRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testConvertsForeignCurrencyQuantity(): void
    {
        $exchangeRates = ExchangeRateRepositoryMother::inMemory();
        $exchangeRates->add(ExchangeRateMother::registered(currency: Unit::UsDollar, rate: '1.25'));

        $activityRepository = ActivityRepositoryMother::inMemory();
        $purchaseService = $this->purchaseServiceWithFactor();
        $sut = new RegisterPurchaseServiceActivityService(
            $activityRepository,
            new ActivityAuthorizer(),
            new PurchaseServiceEmissionFactorSelector($this->repositoryWith($purchaseService)),
            EmissionsCalculatorMother::create($exchangeRates),
        );

        $result = $sut($this->request($purchaseService->id(), Unit::UsDollar, 250.0), UserMother::adminUser());

        $activity = $activityRepository->findById($result->activityId);

        $this->assertInstanceOf(PurchaseServiceActivity::class, $activity);
        $this->assertEqualsWithDelta(300.0, (float) $activity->emissions()->co2eq(), 1e-9);
        $this->assertSame(Unit::UsDollar, $activity->quantity()->unit());
    }

    public function testThrowsWhenNoFactorForYear(): void
    {
        $purchaseService = PurchaseServiceMother::registeredPurchaseService();
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), $this->repositoryWith($purchaseService));

        $this->expectException(\InvalidArgumentException::class);

        $sut($this->request($purchaseService->id(), Unit::Euro, 100.0), UserMother::adminUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), PurchaseServiceRepositoryMother::inMemory());

        $this->expectException(UnauthorizedActionException::class);

        $sut($this->request(PurchaseServiceId::generate(), Unit::Euro, 100.0), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), PurchaseServiceRepositoryMother::inMemory());

        $this->assertTrue($sut->isHandled($this->request(PurchaseServiceId::generate(), Unit::Euro, 100.0)));
    }

    private function purchaseServiceWithFactor(): PurchaseService
    {
        $purchaseService = PurchaseServiceMother::registeredPurchaseService();
        $purchaseService->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Euro));

        return $purchaseService;
    }

    private function repositoryWith(PurchaseService $purchaseService): PurchaseServiceRepository
    {
        $repository = PurchaseServiceRepositoryMother::inMemory();
        $repository->add($purchaseService);

        return $repository;
    }

    private function request(PurchaseServiceId $purchaseServiceId, Unit $unit, float $quantity): RegisterPurchaseServiceActivityRequest
    {
        return new RegisterPurchaseServiceActivityRequest(
            accountId: new AccountId('account-id'),
            purchaseServiceId: $purchaseServiceId,
            quantity: ActivityMother::quantity($unit, $quantity),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        );
    }

    private function createSUT(InMemoryActivityRepository $activityRepository, PurchaseServiceRepository $purchaseServiceRepository): RegisterPurchaseServiceActivityService
    {
        return new RegisterPurchaseServiceActivityService(
            $activityRepository,
            new ActivityAuthorizer(),
            new PurchaseServiceEmissionFactorSelector($purchaseServiceRepository),
            EmissionsCalculatorMother::create(),
        );
    }
}
