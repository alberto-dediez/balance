<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\ActivityNotFoundException;
use App\CarbonFootprint\Application\Activity\PurchaseServiceEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Update\UpdatePurchaseServiceActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdatePurchaseServiceActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\PurchaseServiceActivityUpdated;
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
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\PurchaseServiceActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdatePurchaseServiceActivityServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRecalculatesEmissions(): void
    {
        $activity = PurchaseServiceActivityMother::registered();
        $activityRepository = ActivityRepositoryMother::inMemory();
        $activityRepository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $purchaseService = PurchaseServiceMother::registeredPurchaseService();
        $purchaseService->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Euro));
        $purchaseServiceRepository = PurchaseServiceRepositoryMother::inMemory();
        $purchaseServiceRepository->add($purchaseService);

        $sut = $this->createSUT($activityRepository, $purchaseServiceRepository);

        $sut(
            $this->request($activity->id(), $purchaseService->id(), 50.0),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        );

        $this->assertInstanceOf(PurchaseServiceActivityUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertTrue($purchaseService->id()->equals($activity->purchaseServiceId()));
        $this->assertEqualsWithDelta(75.0, (float) $activity->emissions()->co2eq(), 1e-9);
    }

    public function testThrowsWhenActivityNotFound(): void
    {
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), PurchaseServiceRepositoryMother::inMemory());

        $this->expectException(ActivityNotFoundException::class);

        $sut($this->request(ActivityId::generate(), PurchaseServiceId::generate(), 50.0), UserMother::adminUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $activity = PurchaseServiceActivityMother::registered();
        $activityRepository = ActivityRepositoryMother::inMemory();
        $activityRepository->save($activity);

        $sut = $this->createSUT($activityRepository, PurchaseServiceRepositoryMother::inMemory());

        $this->expectException(UnauthorizedActionException::class);

        $sut($this->request($activity->id(), PurchaseServiceId::generate(), 50.0), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), PurchaseServiceRepositoryMother::inMemory());

        $this->assertTrue($sut->isHandled($this->request(ActivityId::generate(), PurchaseServiceId::generate(), 50.0)));
    }

    private function request(ActivityId $activityId, PurchaseServiceId $purchaseServiceId, float $quantity): UpdatePurchaseServiceActivityRequest
    {
        return new UpdatePurchaseServiceActivityRequest(
            activityId: $activityId,
            purchaseServiceId: $purchaseServiceId,
            quantity: ActivityMother::quantity(Unit::Euro, $quantity),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        );
    }

    private function createSUT(InMemoryActivityRepository $activityRepository, PurchaseServiceRepository $purchaseServiceRepository): UpdatePurchaseServiceActivityService
    {
        return new UpdatePurchaseServiceActivityService(
            $activityRepository,
            new ActivityAuthorizer(),
            new PurchaseServiceEmissionFactorSelector($purchaseServiceRepository),
            EmissionsCalculatorMother::create(),
        );
    }
}
