<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Protocol\Access;

use App\FoodLoss\Application\Protocol\Access\AccessProtocolRequest;
use App\FoodLoss\Application\Protocol\Access\AccessProtocolService;
use App\FoodLoss\Application\Protocol\ProtocolAuthorizer;
use App\FoodLoss\Domain\Model\Protocol\ProtocolAccessed;
use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\FoodLoss\Domain\Model\Protocol\ProtocolRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\Protocol\ProtocolMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Protocol\ProtocolRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class AccessProtocolServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testOwnerAccessingProtocolEmitsEvent(): void
    {
        $repository = ProtocolRepositoryMother::inMemory();
        $protocol = ProtocolMother::forAccount('account-test');
        $repository->add($protocol);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-test'));
        $sut = $this->createSUT($repository);

        $response = $sut(
            new AccessProtocolRequest(protocolId: $protocol->id(), accountId: new AccountId('account-test')),
            $user,
        );

        $this->assertEquals($protocol->path(), $response->path);

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(ProtocolAccessed::class, $lastEvent);
        $this->assertTrue($protocol->id()->equals($lastEvent->protocolId()));
        $this->assertTrue($user->id()->equals($lastEvent->userId()));
    }

    public function testUserAccessingProtocolEmitsEvent(): void
    {
        $repository = ProtocolRepositoryMother::inMemory();
        $protocol = ProtocolMother::forAccount('account-test');
        $repository->add($protocol);

        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-test'));
        $sut = $this->createSUT($repository);

        $response = $sut(
            new AccessProtocolRequest(protocolId: $protocol->id(), accountId: new AccountId('account-test')),
            $user,
        );

        $this->assertEquals($protocol->path(), $response->path);
        $this->assertInstanceOf(ProtocolAccessed::class, $this->spySubscriber->lastEvent());
    }

    public function testAccessProtocolNotFoundThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $sut = $this->createSUT(ProtocolRepositoryMother::inMemory());
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-test'));

        $sut(
            new AccessProtocolRequest(protocolId: ProtocolId::generate(), accountId: new AccountId('account-test')),
            $user,
        );
    }

    public function testUserFromOtherAccountCannotAccessProtocol(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = ProtocolRepositoryMother::inMemory();
        $protocol = ProtocolMother::forAccount('account-test');
        $repository->add($protocol);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('other-account'));
        $sut = $this->createSUT($repository);

        $sut(
            new AccessProtocolRequest(protocolId: $protocol->id(), accountId: new AccountId('other-account')),
            $user,
        );
    }

    private function createSUT(ProtocolRepository $repository): AccessProtocolService
    {
        return new AccessProtocolService(
            protocolRepository: $repository,
            authorizer: new ProtocolAuthorizer(),
        );
    }
}
