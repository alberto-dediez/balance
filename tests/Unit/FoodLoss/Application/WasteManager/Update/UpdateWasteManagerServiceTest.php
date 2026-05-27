<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\WasteManager\Update;

use App\FoodLoss\Application\WasteManager\Update\UpdateWasteManagerRequest;
use App\FoodLoss\Application\WasteManager\Update\UpdateWasteManagerService;
use App\FoodLoss\Application\WasteManager\WasteManagerAuthorizer;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerRepository;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerUpdated;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\WasteManager\WasteManagerMother;
use App\Tests\Unit\FoodLoss\Domain\Model\WasteManager\WasteManagerRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateWasteManagerServiceTest extends TestCase
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

        $wasteManager = WasteManagerMother::from(name: 'Original name', id: 'test-id', accountId: 'account-id-test');
        $repository->add($wasteManager);

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner@test.com', Role::Owner, $accountId);

        $request = new UpdateWasteManagerRequest(
            wasteManagerId: $wasteManager->id(),
            name: 'Updated name',
            animalFeedAuthorized: true,
            compostAuthorized: true,
        );

        $result = $sut($request, $user);

        $this->assertEquals('Updated name', $result->name);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof WasteManagerUpdated);
        $this->assertEquals('Updated name', $lastEvent->name());
    }

    private function createSUT(WasteManagerRepository $repository): UpdateWasteManagerService
    {
        return new UpdateWasteManagerService(
            wasteManagerRepository: $repository,
            authorizer: new WasteManagerAuthorizer(),
        );
    }
}
