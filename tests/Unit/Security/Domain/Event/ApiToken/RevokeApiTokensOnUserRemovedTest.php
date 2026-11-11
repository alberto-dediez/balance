<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Event\ApiToken;

use App\Security\Application\ApiToken\RevokeAll\RevokeApiTokensOfRemovedUserService;
use App\Security\Domain\Event\ApiToken\RevokeApiTokensOnUserRemoved;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRemoved;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRemoved;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryApiTokenRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\ApiToken\ApiTokenMother;
use App\Tests\Unit\Security\Domain\Model\ApiToken\ApiTokenRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\TestCase;

class RevokeApiTokensOnUserRemovedTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testUserRemovedRevokesActiveTokensOfThatUserOnly(): void
    {
        $repository = ApiTokenRepositoryMother::inMemory();
        $admin = UserMother::adminUser();

        $user = UserMother::fromUsername('removed@example.com');
        $other = UserMother::fromUsername('other@example.com');

        $first = ApiTokenMother::active($user->id());
        $second = ApiTokenMother::active($user->id());
        $foreign = ApiTokenMother::active($other->id());
        foreach ([$first, $second, $foreign] as $apiToken) {
            $repository->add($apiToken);
        }

        $this->createSUT($repository, $admin)->handle(new UserRemoved($user->id()));

        $this->assertTrue($first->isRevoked());
        $this->assertTrue($second->isRevoked());
        $this->assertFalse($foreign->isRevoked());
        $this->assertEquals($admin->id(), $first->revokedBy());
    }

    public function testWithoutLoggedUserTheSystemUserRevokesTheTokens(): void
    {
        $repository = ApiTokenRepositoryMother::inMemory();
        $user = UserMother::fromUsername('removed@example.com');
        $apiToken = ApiTokenMother::active($user->id());
        $repository->add($apiToken);

        $this->createSUT($repository, null)->handle(new UserRemoved($user->id()));

        $this->assertTrue($apiToken->isRevoked());
        $this->assertEquals(UserMother::systemUser()->id(), $apiToken->revokedBy());
    }

    public function testAnAdminRemovingASuperAdminRevokesItsTokens(): void
    {
        $repository = ApiTokenRepositoryMother::inMemory();
        $superAdmin = UserMother::superAdminUser();
        $apiToken = ApiTokenMother::active($superAdmin->id());
        $repository->add($apiToken);

        $this->createSUT($repository, UserMother::adminUser())->handle(new UserRemoved($superAdmin->id()));

        $this->assertTrue($apiToken->isRevoked());
    }

    public function testIsSubscribedOnlyToUserRemoved(): void
    {
        $sut = $this->createSUT(ApiTokenRepositoryMother::inMemory(), UserMother::adminUser());

        $this->assertTrue($sut->isSubscribedTo(new UserRemoved(UserMother::registeredUser()->id())));
        $this->assertFalse($sut->isSubscribedTo(new AccountRemoved(AccountId::generate())));
    }

    private function createSUT(InMemoryApiTokenRepository $repository, ?User $loggedUser): RevokeApiTokensOnUserRemoved
    {
        return new RevokeApiTokensOnUserRemoved(
            new RevokeApiTokensOfRemovedUserService($repository),
            new FakeLoggedUser($loggedUser),
        );
    }
}
