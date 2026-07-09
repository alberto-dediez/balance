<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventOperations\Application\Provider\LinkUser;

use App\EventOperations\Application\Provider\LinkUser\LinkProviderUserRequest;
use App\EventOperations\Application\Provider\LinkUser\LinkProviderUserService;
use App\EventOperations\Application\Provider\ProviderAuthorizer;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class LinkProviderUserServiceTest extends TestCase
{
    public function testLinksTheUserToTheProvider(): void
    {
        DomainEventPublisher::instance()->reset();

        $repository = ProviderRepositoryMother::inMemory();
        $provider = ProviderMother::fromNameAndAccountAndId('Acme', 'account-id', (string) ProviderId::generate());
        $repository->add($provider);

        $sut = new LinkProviderUserService($repository, new ProviderAuthorizer());

        $userId = UserId::generate();
        $sut(new LinkProviderUserRequest($provider->id(), $userId), $this->ownerUser());

        $persisted = $repository->find($provider->id());
        $this->assertNotNull($persisted);
        $linkedUserId = $persisted->userId();
        $this->assertNotNull($linkedUserId);
        $this->assertTrue($linkedUserId->equals($userId));
    }

    public function testProviderNotFound(): void
    {
        DomainEventPublisher::instance()->reset();

        $repository = ProviderRepositoryMother::inMemory();
        $sut = new LinkProviderUserService($repository, new ProviderAuthorizer());

        $this->expectException(\InvalidArgumentException::class);
        $sut(new LinkProviderUserRequest(ProviderId::generate(), UserId::generate()), $this->ownerUser());
    }

    public function testUnauthorizedAccess(): void
    {
        DomainEventPublisher::instance()->reset();

        $repository = ProviderRepositoryMother::inMemory();
        $provider = ProviderMother::fromNameAndAccountAndId('Acme', 'account-id', (string) ProviderId::generate());
        $repository->add($provider);

        $sut = new LinkProviderUserService($repository, new ProviderAuthorizer());

        $this->expectException(UnauthorizedActionException::class);
        $sut(new LinkProviderUserRequest($provider->id(), UserId::generate()), UserMother::fromUsernameAndRole('user', Role::User));
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
