<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\WasteManager\Remove;

use App\FoodLoss\Application\WasteManager\Remove\RemoveWasteManagerRequest;
use App\FoodLoss\Application\WasteManager\Remove\RemoveWasteManagerService;
use App\FoodLoss\Application\WasteManager\WasteManagerAuthorizer;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerRemoved;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\WasteManager\WasteManagerMother;
use App\Tests\Unit\FoodLoss\Domain\Model\WasteManager\WasteManagerRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveWasteManagerServiceTest extends TestCase
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
        $repository = WasteManagerRepositoryMother::inMemory();

        $wasteManager = WasteManagerMother::from(name: 'To remove', id: 'test-id', accountId: 'account-id-test');
        $repository->add($wasteManager);

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner@test.com', Role::Owner, $accountId);

        $result = $sut(new RemoveWasteManagerRequest($wasteManager->id()), $user);

        $this->assertEquals($wasteManager->id(), $result->wasteManagerId);
        $this->assertNull($repository->find($wasteManager->id()));

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof WasteManagerRemoved);
    }

    private function createSUT(WasteManagerRepository $repository): RemoveWasteManagerService
    {
        return new RemoveWasteManagerService(
            repository: $repository,
            authorizer: new WasteManagerAuthorizer(),
        );
    }
}
