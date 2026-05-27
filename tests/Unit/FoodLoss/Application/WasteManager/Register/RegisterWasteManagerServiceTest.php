<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\WasteManager\Register;

use App\FoodLoss\Application\WasteManager\Register\RegisterWasteManagerRequest;
use App\FoodLoss\Application\WasteManager\Register\RegisterWasteManagerService;
use App\FoodLoss\Application\WasteManager\WasteManagerAuthorizer;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerRegistered;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\WasteManager\WasteManagerRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterWasteManagerServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = WasteManagerRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $accountId = new AccountId('test-account-id');
        $request = new RegisterWasteManagerRequest(
            name: 'Gestor Test',
            animalFeedAuthorized: true,
            compostAuthorized: false,
            accountId: $accountId,
        );

        $user = UserMother::fromUsernameAndRoleAndAccount('owner@test.com', Role::Owner, $accountId);

        $result = $sut($request, $user);

        $this->assertNotEmpty((string) $result->wasteManagerId);
        $this->assertEquals(36, strlen((string) $result->wasteManagerId));
        $this->assertEquals('Gestor Test', $result->name);

        $persisted = $repository->find($result->wasteManagerId);

        $this->assertNotNull($persisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof WasteManagerRegistered);
        $this->assertEquals('Gestor Test', $lastEvent->name());
        $this->assertTrue($lastEvent->animalFeedAuthorized());
        $this->assertFalse($lastEvent->compostAuthorized());
        $this->assertTrue($persisted->id()->equals($lastEvent->wasteManagerId()));
    }

    public function testIsHandle(): void
    {
        $sut = $this->createSUT(WasteManagerRepositoryMother::inMemory());

        $result = $sut->isHandled(new RegisterWasteManagerRequest(name: 'test'));

        $this->assertTrue($result);
    }

    private function createSUT(WasteManagerRepository $repository): RegisterWasteManagerService
    {
        return new RegisterWasteManagerService(
            wasteManagerRepository: $repository,
            authorizer: new WasteManagerAuthorizer(),
        );
    }
}
