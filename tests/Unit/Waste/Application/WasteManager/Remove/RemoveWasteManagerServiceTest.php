<?php

namespace App\Tests\Unit\Waste\Application\WasteManager\Remove;

use App\Waste\Infrastructure\Persistence\InMemory\Repository\InMemoryWasteManagerRepository;
use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Waste\Domain\Model\WasteManager\WasteManagerRemoved;
use App\Waste\Application\WasteManager\WasteManagerAuthorizer;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Waste\Domain\Model\WasteManager\WasteManagerMother;
use App\Waste\Application\WasteManager\Remove\RemoveWasteManagerRequest;
use App\Waste\Application\WasteManager\Remove\RemoveWasteManagerService;
use App\Waste\Application\WasteManager\Remove\RemoveWasteManagerResponse;
use App\Tests\Unit\Waste\Domain\Model\WasteManager\WasteManagerRepositoryMother;

class RemoveWasteManagerServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = WasteManagerRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, AccountId::generate());

        $sut = $this->createSUT($repository);

        $wasteManager = WasteManagerMother::fromAccount($user->accountId());

        $repository->add($wasteManager);

        $request = new RemoveWasteManagerRequest(
            wasteManagerId: $wasteManager->id()
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RemoveWasteManagerResponse);
        $this->assertTrue($wasteManager->id()->equals($result->wasteManagerId));
        
        $wasteManagerPersisted = $repository->find($result->wasteManagerId);

        $this->assertNull($wasteManagerPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof WasteManagerRemoved);
    }

    public function testIsHandle(): void
    {
        $repository = WasteManagerRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $wasteManager = WasteManagerMother::random();

        $result = $sut->isHandled(new RemoveWasteManagerRequest(
            wasteManagerId: $wasteManager->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryWasteManagerRepository $repository): RemoveWasteManagerService
    {
        return new RemoveWasteManagerService($repository, new WasteManagerAuthorizer());
    }
}