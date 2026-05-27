<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Surplus\Register;

use App\FoodLoss\Application\Surplus\Register\RegisterSurplusRequest;
use App\FoodLoss\Application\Surplus\Register\RegisterSurplusService;
use App\FoodLoss\Application\Surplus\SurplusAuthorizer;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\FoodLoss\Domain\Model\DeliveryLog\FoodType;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\Surplus\SurplusRegistered;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\Surplus\SurplusRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterSurplusServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $accountId = new AccountId('account-id-test');
        $repository = SurplusRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $request = new RegisterSurplusRequest(
            date: new \DateTimeImmutable('2026-05-21'),
            serviceCategoryId: new ServiceCategoryId('00000000-0000-0000-0000-000000000001'),
            criticalPointId: new CriticalPointId('00000000-0000-0000-0000-000000000001'),
            surplusCauseId: new SurplusCauseId('00000000-0000-0000-0000-000000000001'),
            foodType: FoodType::Vegetables,
            quantity: 5.5,
            unit: DeliveryLogUnit::Kg,
            accountId: $accountId,
            registeredBy: new UserId('user-id-test'),
        );

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', \App\Security\Domain\Model\User\Role::Owner, $accountId);
        $result = $sut($request, $user);

        $this->assertNotEmpty((string) $result->surplusId);
        $this->assertEquals(36, strlen((string) $result->surplusId));
        $this->assertEquals(FoodType::Vegetables, $result->foodType);
        $this->assertEquals(5.5, $result->quantity);

        $surplusPersisted = $repository->find($result->surplusId);

        $this->assertNotNull($surplusPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof SurplusRegistered);
        $this->assertEquals(FoodType::Vegetables, $lastEvent->foodType());
        $this->assertTrue($surplusPersisted->id()->equals($lastEvent->surplusId()));
    }

    public function testIsHandle(): void
    {
        $repository = SurplusRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RegisterSurplusRequest(
            accountId: new AccountId('account-id-test'),
            registeredBy: new UserId('user-id-test'),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(SurplusRepository $repository): RegisterSurplusService
    {
        return new RegisterSurplusService(
            surplusRepository: $repository,
            authorizer: new SurplusAuthorizer(),
        );
    }
}
