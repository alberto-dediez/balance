<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\User\DisableTwoFactor;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Application\User\DisableTwoFactor\DisableTwoFactorRequest;
use App\Security\Application\User\DisableTwoFactor\DisableTwoFactorService;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\TwoFactorDisabled;
use App\Security\Domain\Model\User\TwoFactorMethod;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\User\UserAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class DisableTwoFactorServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spy;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spy = new SpyDomainEventSubscriber();
    }

    public function testDisablesTheOwnTwoFactor(): void
    {
        $repository = new InMemoryUserRepository();
        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Totp);
        $repository->add($user);

        $response = $this->createSUT($repository)(new DisableTwoFactorRequest($user->id()), $user);

        $this->assertTrue($user->id()->equals($response->userId));
        $this->assertNull($user->twoFactorMethod());
        $this->assertNull($user->totpSecret());
        $this->assertInstanceOf(TwoFactorDisabled::class, $this->spy->lastEvent());
    }

    public function testAdminResetsAnotherUser(): void
    {
        $repository = new InMemoryUserRepository();
        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Email);
        $repository->add($user);

        $this->createSUT($repository)(new DisableTwoFactorRequest($user->id()), UserMother::adminUser());

        $this->assertNull($user->twoFactorMethod());
    }

    public function testOwnerResetsAUserOfTheCurrentAccount(): void
    {
        $accountId = AccountId::generate();
        $repository = new InMemoryUserRepository();
        $user = UserMother::from(userId: UserId::generate(), username: 'user@dediez.software', accountId: $accountId, twoFactorMethod: TwoFactorMethod::Email);
        $repository->add($user);

        $owner = UserMother::from(userId: UserId::generate(), username: 'owner@dediez.software', role: Role::Owner, accountId: AccountId::generate());

        $this->createSUT($repository, UserAuthorizerMother::withCurrentAccount($accountId))(new DisableTwoFactorRequest($user->id()), $owner);

        $this->assertNull($user->twoFactorMethod());
    }

    public function testOwnerCannotResetAUserOfAnotherAccount(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryUserRepository();
        $user = UserMother::from(userId: UserId::generate(), username: 'user@dediez.software', accountId: AccountId::generate(), twoFactorMethod: TwoFactorMethod::Email);
        $repository->add($user);

        $owner = UserMother::from(userId: UserId::generate(), username: 'owner@dediez.software', role: Role::Owner, accountId: AccountId::generate());

        $this->createSUT($repository, UserAuthorizerMother::withCurrentAccount(AccountId::generate()))(new DisableTwoFactorRequest($user->id()), $owner);
    }

    public function testUserCannotResetAnotherUser(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryUserRepository();
        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Email);
        $repository->add($user);

        $this->createSUT($repository)(new DisableTwoFactorRequest($user->id()), UserMother::fromUsername('other@dediez.software'));
    }

    private function createSUT(InMemoryUserRepository $repository, ?UserAuthorizer $authorizer = null): DisableTwoFactorService
    {
        return new DisableTwoFactorService(
            repository: $repository,
            authorizer: $authorizer ?? UserAuthorizerMother::create(),
        );
    }
}
