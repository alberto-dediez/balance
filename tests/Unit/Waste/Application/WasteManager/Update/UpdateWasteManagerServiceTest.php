<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\WasteManager\Update;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Waste\Domain\Model\WasteManager\WasteManagerMother;
use App\Tests\Unit\Waste\Domain\Model\WasteManager\WasteManagerRepositoryMother;
use App\Waste\Application\WasteManager\Update\UpdateWasteManagerRequest;
use App\Waste\Application\WasteManager\Update\UpdateWasteManagerResponse;
use App\Waste\Application\WasteManager\Update\UpdateWasteManagerService;
use App\Waste\Application\WasteManager\WasteManagerAuthorizer;
use App\Waste\Domain\Model\WasteManager\WasteManager;
use App\Waste\Domain\Model\WasteManager\WasteManagerUpdated;
use App\Waste\Infrastructure\Persistence\InMemory\Repository\InMemoryWasteManagerRepository;
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
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, AccountId::generate());

        $repository = WasteManagerRepositoryMother::inMemory();

        $wasteManager = WasteManagerMother::fromAccount($user->accountId());

        $repository->add($wasteManager);

        $sut = $this->createSUT($repository);

        $request = new UpdateWasteManagerRequest(
            wasteManagerId: $wasteManager->id(),
            name: 'Hotel',
            isPublic: true,
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof UpdateWasteManagerResponse);

        $wasteManagerPersisted = $repository->find($result->wasteManagerId);

        $this->assertNotNull($wasteManagerPersisted);
        $this->assertTrue($wasteManagerPersisted instanceof WasteManager);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof WasteManagerUpdated);
        $this->assertEquals('Hotel', $lastEvent->name());
        $this->assertEquals(true, $lastEvent->isPublic());
        $this->assertTrue($wasteManagerPersisted->id()->equals($lastEvent->wasteManagerId()));
    }

    public function testIsHandle(): void
    {
        $repository = WasteManagerRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $wasteManager = WasteManagerMother::random();

        $result = $sut->isHandled(new UpdateWasteManagerRequest(
            wasteManagerId: $wasteManager->id(),
            name: 'Hotel',
            isPublic: true,
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryWasteManagerRepository $repository): UpdateWasteManagerService
    {
        return new UpdateWasteManagerService($repository, new WasteManagerAuthorizer());
    }
}
