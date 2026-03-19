<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\WasteManager\Register;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Waste\Domain\Model\WasteManager\WasteManagerRepositoryMother;
use App\Waste\Application\WasteManager\Register\RegisterWasteManagerRequest;
use App\Waste\Application\WasteManager\Register\RegisterWasteManagerResponse;
use App\Waste\Application\WasteManager\Register\RegisterWasteManagerService;
use App\Waste\Application\WasteManager\WasteManagerAuthorizer;
use App\Waste\Domain\Model\WasteManager\WasteManager;
use App\Waste\Domain\Model\WasteManager\WasteManagerRegistered;
use App\Waste\Infrastructure\Persistence\InMemory\Repository\InMemoryWasteManagerRepository;
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

        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, AccountId::generate());

        $sut = $this->createSUT($repository);

        $request = new RegisterWasteManagerRequest(
            name: 'Hotel Abaco',
            accountId: $user->accountId(),
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RegisterWasteManagerResponse);
        $this->assertNotEmpty((string) $result->wasteManagerId);
        $this->assertEquals(36, strlen((string) $result->wasteManagerId));

        $wasteManagerPersisted = $repository->find($result->wasteManagerId);

        $this->assertNotNull($wasteManagerPersisted);
        $this->assertTrue($wasteManagerPersisted instanceof WasteManager);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof WasteManagerRegistered);
        $this->assertEquals('Hotel Abaco', $lastEvent->name());
        $this->assertTrue($wasteManagerPersisted->id()->equals($lastEvent->wasteManagerId()));
    }

    public function testIsHandle(): void
    {
        $repository = WasteManagerRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $user = UserMother::random();

        $result = $sut->isHandled(new RegisterWasteManagerRequest(
            name: 'Hotel Abaco',
            accountId: $user->accountId(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryWasteManagerRepository $repository): RegisterWasteManagerService
    {
        return new RegisterWasteManagerService($repository, new WasteManagerAuthorizer());
    }
}
