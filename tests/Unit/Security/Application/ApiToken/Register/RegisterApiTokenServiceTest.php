<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\ApiToken\Register;

use App\Security\Application\ApiToken\ApiTokenAuthorizer;
use App\Security\Application\ApiToken\ApiTokenPolicy;
use App\Security\Application\ApiToken\Register\RegisterApiTokenRequest;
use App\Security\Application\ApiToken\Register\RegisterApiTokenService;
use App\Security\Domain\Model\ApiToken\ApiToken;
use App\Security\Domain\Model\ApiToken\ApiTokenLimitReachedException;
use App\Security\Domain\Model\ApiToken\ApiTokenRegistered;
use App\Security\Domain\Model\ApiToken\ApiTokenSecret;
use App\Security\Domain\Model\ApiToken\ApiTokensNotAllowedException;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryApiTokenRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\ApiToken\ApiTokenMother;
use App\Tests\Unit\Security\Domain\Model\ApiToken\ApiTokenRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterApiTokenServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRegistersTokenAndReturnsPlainValue(): void
    {
        $repository = ApiTokenRepositoryMother::inMemory();
        $user = UserMother::adminUser();

        $result = $this->createSUT($repository)(new RegisterApiTokenRequest(name: 'Integration'), $user);

        $persisted = $repository->find($result->apiTokenId);

        $this->assertNotNull($persisted);
        $this->assertStringStartsWith('hyd_', $result->plainValue);
        $this->assertSame(ApiTokenSecret::hashFor($result->plainValue), $persisted->tokenHash());
        $this->assertSame('Integration', $result->name);
        $this->assertTrue($persisted->belongsTo($user->id()));
        $this->assertNull($result->expiresAt);
        $this->assertInstanceOf(ApiTokenRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testExpirationIsSetAtTheEndOfTheDay(): void
    {
        $repository = ApiTokenRepositoryMother::inMemory();
        $expiresAt = new \DateTimeImmutable('tomorrow 10:00');

        $result = $this->createSUT($repository)(new RegisterApiTokenRequest(name: 'Integration', expiresAt: $expiresAt), UserMother::adminUser());

        $this->assertNotNull($result->expiresAt);
        $this->assertSame($expiresAt->format('Y-m-d') . ' 23:59:59', $result->expiresAt->format('Y-m-d H:i:s'));
    }

    public function testAccountWithoutPermissionThrows(): void
    {
        $this->expectException(ApiTokensNotAllowedException::class);

        $account = AccountMother::fromNameAndId('Denied', 'denied-account');
        $user = UserMother::fromUsernameAndRoleAndAccount('user@example.com', Role::User, $account->id(), $account);

        $this->createSUT(ApiTokenRepositoryMother::inMemory())(new RegisterApiTokenRequest(name: 'Integration'), $user);
    }

    public function testAccountWithPermissionRegisters(): void
    {
        $account = AccountMother::withApiTokensEnabled('Allowed', 'allowed-account');
        $user = UserMother::fromUsernameAndRoleAndAccount('user@example.com', Role::User, $account->id(), $account);
        $repository = ApiTokenRepositoryMother::inMemory();

        $result = $this->createSUT($repository)(new RegisterApiTokenRequest(name: 'Integration'), $user);

        $persisted = $repository->find($result->apiTokenId);

        $this->assertNotNull($persisted);
        $this->assertEquals($account->id(), $persisted->accountId());
    }

    public function testEleventhActiveTokenThrows(): void
    {
        $this->expectException(ApiTokenLimitReachedException::class);

        $repository = ApiTokenRepositoryMother::inMemory();
        $user = UserMother::adminUser();

        for ($i = 0; $i < ApiToken::MAX_ACTIVE_PER_USER; ++$i) {
            $repository->add(ApiTokenMother::active($user->id()));
        }

        $this->createSUT($repository)(new RegisterApiTokenRequest(name: 'Integration'), $user);
    }

    public function testWithARevokedTokenAnotherCanBeRegistered(): void
    {
        $repository = ApiTokenRepositoryMother::inMemory();
        $user = UserMother::adminUser();

        for ($i = 0; $i < ApiToken::MAX_ACTIVE_PER_USER - 1; ++$i) {
            $repository->add(ApiTokenMother::active($user->id()));
        }
        $repository->add(ApiTokenMother::revoked($user->id()));

        $result = $this->createSUT($repository)(new RegisterApiTokenRequest(name: 'Integration'), $user);

        $this->assertNotNull($repository->find($result->apiTokenId));
    }

    public function testIsHandle(): void
    {
        $this->assertTrue($this->createSUT(ApiTokenRepositoryMother::inMemory())->isHandled(new RegisterApiTokenRequest()));
    }

    private function createSUT(InMemoryApiTokenRepository $repository): RegisterApiTokenService
    {
        return new RegisterApiTokenService(
            repository: $repository,
            authorizer: new ApiTokenAuthorizer(UserRepositoryMother::inMemory()),
            policy: new ApiTokenPolicy(AccountRepositoryMother::inMemory()),
        );
    }
}
