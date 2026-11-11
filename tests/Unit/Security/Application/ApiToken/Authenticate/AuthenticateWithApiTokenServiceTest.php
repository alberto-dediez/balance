<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\ApiToken\Authenticate;

use App\Security\Application\ApiToken\ApiTokenPolicy;
use App\Security\Application\ApiToken\Authenticate\AuthenticateWithApiTokenRequest;
use App\Security\Application\ApiToken\Authenticate\AuthenticateWithApiTokenService;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\ApiToken\ApiTokenSecret;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryApiTokenRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\ApiToken\ApiTokenMother;
use App\Tests\Unit\Security\Domain\Model\ApiToken\ApiTokenRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class AuthenticateWithApiTokenServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testValidTokenReturnsIdWithoutMarkingItAsUsed(): void
    {
        $repository = ApiTokenRepositoryMother::inMemory();
        $user = UserMother::adminUser();
        $secret = ApiTokenSecret::generate();
        $apiToken = ApiTokenMother::withSecret($secret, $user->id());
        $repository->add($apiToken);

        $this->spySubscriber = new SpyDomainEventSubscriber();

        $result = $this->createSUT($repository)(new AuthenticateWithApiTokenRequest($secret->plainValue()), $user);

        $this->assertNotNull($result->apiTokenId);
        $this->assertTrue($apiToken->id()->equals($result->apiTokenId));
        $this->assertNull($apiToken->lastUsedAt());
        $this->assertEmpty($this->spySubscriber->events());
    }

    public function testUnknownTokenReturnsNull(): void
    {
        $this->assertRejected(ApiTokenRepositoryMother::inMemory(), 'hyd_unknown', UserMother::adminUser());
    }

    public function testTokenOfAnotherUserReturnsNull(): void
    {
        $repository = ApiTokenRepositoryMother::inMemory();
        $secret = ApiTokenSecret::generate();
        $repository->add(ApiTokenMother::withSecret($secret, UserMother::fromUsername('other@example.com')->id()));

        $this->assertRejected($repository, $secret->plainValue(), UserMother::adminUser());
    }

    public function testExpiredTokenReturnsNull(): void
    {
        $repository = ApiTokenRepositoryMother::inMemory();
        $user = UserMother::adminUser();
        $secret = ApiTokenSecret::generate();
        $repository->add(ApiTokenMother::expired($user->id(), null, 'token', $secret));

        $this->assertRejected($repository, $secret->plainValue(), $user);
    }

    public function testRevokedTokenReturnsNull(): void
    {
        $repository = ApiTokenRepositoryMother::inMemory();
        $user = UserMother::adminUser();
        $secret = ApiTokenSecret::generate();
        $repository->add(ApiTokenMother::revoked($user->id(), null, 'token', $secret));

        $this->assertRejected($repository, $secret->plainValue(), $user);
    }

    public function testTokenOfAccountWithoutPermissionReturnsNull(): void
    {
        $accountRepository = AccountRepositoryMother::inMemory();
        $account = AccountMother::fromNameAndId('Denied', 'denied-account');
        $accountRepository->add($account);

        $repository = ApiTokenRepositoryMother::inMemory();
        $user = UserMother::fromUsernameAndRoleAndAccount('user@example.com', Role::User, $account->id(), $account);
        $secret = ApiTokenSecret::generate();
        $repository->add(ApiTokenMother::withSecret($secret, $user->id(), $account->id()));

        $this->assertRejected($repository, $secret->plainValue(), $user, $accountRepository);
    }

    private function assertRejected(
        InMemoryApiTokenRepository $repository,
        string $plainValue,
        User $user,
        ?AccountRepository $accountRepository = null,
    ): void {
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $result = $this->createSUT($repository, $accountRepository)(new AuthenticateWithApiTokenRequest($plainValue), $user);

        $this->assertNull($result->apiTokenId);
        $this->assertEmpty($this->spySubscriber->events());
    }

    private function createSUT(InMemoryApiTokenRepository $repository, ?AccountRepository $accountRepository = null): AuthenticateWithApiTokenService
    {
        return new AuthenticateWithApiTokenService(
            repository: $repository,
            policy: new ApiTokenPolicy($accountRepository ?? AccountRepositoryMother::inMemory()),
        );
    }
}
