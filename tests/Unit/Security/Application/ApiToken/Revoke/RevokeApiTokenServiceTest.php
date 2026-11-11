<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\ApiToken\Revoke;

use App\Security\Application\ApiToken\ApiTokenAuthorizer;
use App\Security\Application\ApiToken\Revoke\RevokeApiTokenRequest;
use App\Security\Application\ApiToken\Revoke\RevokeApiTokenService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\ApiToken\ApiTokenId;
use App\Security\Domain\Model\ApiToken\ApiTokenRevoked;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryApiTokenRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\ApiToken\ApiTokenMother;
use App\Tests\Unit\Security\Domain\Model\ApiToken\ApiTokenRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RevokeApiTokenServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testOwnerOfTheTokenRevokesIt(): void
    {
        $repository = ApiTokenRepositoryMother::inMemory();
        $user = UserMother::registeredUser();
        $apiToken = ApiTokenMother::active($user->id());
        $repository->add($apiToken);

        $this->createSUT($repository)(new RevokeApiTokenRequest($apiToken->id()), $user);

        $this->assertTrue($apiToken->isRevoked());
        $this->assertInstanceOf(ApiTokenRevoked::class, $this->spySubscriber->lastEvent());
    }

    public function testAnotherUserCannotRevokeIt(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = ApiTokenRepositoryMother::inMemory();
        $apiToken = ApiTokenMother::active(UserMother::registeredUser()->id());
        $repository->add($apiToken);

        $this->createSUT($repository)(new RevokeApiTokenRequest($apiToken->id()), UserMother::fromUsername('other@example.com'));
    }

    public function testOwnerOfTheAccountRevokesIt(): void
    {
        $repository = ApiTokenRepositoryMother::inMemory();
        $accountId = AccountId::generate();
        $apiToken = ApiTokenMother::active(UserMother::registeredUser()->id(), $accountId);
        $repository->add($apiToken);

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner@example.com', Role::Owner, $accountId);

        $this->createSUT($repository)(new RevokeApiTokenRequest($apiToken->id()), $owner);

        $this->assertTrue($apiToken->isRevoked());
        $this->assertEquals($owner->id(), $apiToken->revokedBy());
    }

    public function testAnAdminCannotRevokeTheTokenOfASuperAdmin(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = ApiTokenRepositoryMother::inMemory();
        $userRepository = UserRepositoryMother::inMemory();
        $superAdmin = UserMother::superAdminUser();
        $userRepository->add($superAdmin);
        $apiToken = ApiTokenMother::active($superAdmin->id());
        $repository->add($apiToken);

        $this->createSUT($repository, $userRepository)(new RevokeApiTokenRequest($apiToken->id()), UserMother::adminUser());
    }

    public function testUnknownTokenThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT(ApiTokenRepositoryMother::inMemory())(new RevokeApiTokenRequest(ApiTokenId::generate()), UserMother::adminUser());
    }

    public function testIsHandle(): void
    {
        $this->assertTrue($this->createSUT(ApiTokenRepositoryMother::inMemory())->isHandled(new RevokeApiTokenRequest(ApiTokenId::generate())));
    }

    private function createSUT(InMemoryApiTokenRepository $repository, ?UserRepository $userRepository = null): RevokeApiTokenService
    {
        return new RevokeApiTokenService(
            repository: $repository,
            authorizer: new ApiTokenAuthorizer($userRepository ?? UserRepositoryMother::inMemory()),
        );
    }
}
