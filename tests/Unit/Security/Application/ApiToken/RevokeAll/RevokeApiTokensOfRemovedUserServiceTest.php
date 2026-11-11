<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\ApiToken\RevokeAll;

use App\Security\Application\ApiToken\RevokeAll\RevokeApiTokensOfRemovedUserRequest;
use App\Security\Application\ApiToken\RevokeAll\RevokeApiTokensOfRemovedUserService;
use App\Security\Domain\Model\ApiToken\ApiTokenRevoked;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\ApiToken\ApiTokenMother;
use App\Tests\Unit\Security\Domain\Model\ApiToken\ApiTokenRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RevokeApiTokensOfRemovedUserServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRevokesOnlyActiveTokensAndReturnsTheNumber(): void
    {
        $repository = ApiTokenRepositoryMother::inMemory();
        $user = UserMother::registeredUser();
        $performBy = UserMother::systemUser();

        $first = ApiTokenMother::active($user->id());
        $second = ApiTokenMother::active($user->id());
        $expired = ApiTokenMother::expired($user->id());
        $revoked = ApiTokenMother::revoked($user->id());
        $foreign = ApiTokenMother::active(UserMother::fromUsername('other@example.com')->id());
        foreach ([$first, $second, $expired, $revoked, $foreign] as $apiToken) {
            $repository->add($apiToken);
        }

        $revokedAtBefore = $revoked->revokedAt();

        $result = (new RevokeApiTokensOfRemovedUserService($repository))(new RevokeApiTokensOfRemovedUserRequest($user->id()), $performBy);

        $this->assertSame(2, $result->revoked);
        $this->assertTrue($first->isRevoked());
        $this->assertTrue($second->isRevoked());
        $this->assertEquals($performBy->id(), $first->revokedBy());
        $this->assertFalse($expired->isRevoked());
        $this->assertSame($revokedAtBefore, $revoked->revokedAt());
        $this->assertFalse($foreign->isRevoked());
        $this->assertInstanceOf(ApiTokenRevoked::class, $this->spySubscriber->lastEvent());
    }

    public function testDoesNotDependOnTheRolesOfThePerformer(): void
    {
        $repository = ApiTokenRepositoryMother::inMemory();
        $superAdmin = UserMother::superAdminUser();
        $apiToken = ApiTokenMother::active($superAdmin->id());
        $repository->add($apiToken);

        $result = (new RevokeApiTokensOfRemovedUserService($repository))(
            new RevokeApiTokensOfRemovedUserRequest($superAdmin->id()),
            UserMother::fromUsername('other@example.com'),
        );

        $this->assertSame(1, $result->revoked);
        $this->assertTrue($apiToken->isRevoked());
    }
}
