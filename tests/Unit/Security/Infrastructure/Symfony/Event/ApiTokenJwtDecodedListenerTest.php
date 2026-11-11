<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Symfony\Event;

use App\Security\Application\ApiToken\ApiTokenPolicy;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\ApiToken\ApiToken;
use App\Security\Domain\Model\ApiToken\ApiTokenId;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryApiTokenRepository;
use App\Security\Infrastructure\Symfony\Event\ApiTokenJwtDecodedListener;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\ApiToken\ApiTokenMother;
use App\Tests\Unit\Security\Domain\Model\ApiToken\ApiTokenRepositoryMother;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use PHPUnit\Framework\TestCase;

class ApiTokenJwtDecodedListenerTest extends TestCase
{
    public function testPayloadWithoutClaimIsNotInvalidated(): void
    {
        $event = new JWTDecodedEvent(['username' => 'user@example.com']);

        $this->createSUT(ApiTokenRepositoryMother::inMemory())($event);

        $this->assertTrue($event->isValid());
    }

    public function testClaimOfActiveTokenIsNotInvalidated(): void
    {
        $this->assertValidity(true, ApiTokenMother::active(UserId::generate()));
    }

    public function testClaimOfRevokedTokenIsInvalidated(): void
    {
        $this->assertValidity(false, ApiTokenMother::revoked(UserId::generate()));
    }

    public function testClaimOfExpiredTokenIsInvalidated(): void
    {
        $this->assertValidity(false, ApiTokenMother::expired(UserId::generate()));
    }

    public function testClaimOfUnknownTokenIsInvalidated(): void
    {
        $event = new JWTDecodedEvent(['username' => 'user@example.com', 'api_token_id' => (string) ApiTokenId::generate()]);

        $this->createSUT(ApiTokenRepositoryMother::inMemory())($event);

        $this->assertFalse($event->isValid());
    }

    public function testClaimOfTokenOfAccountWithoutPermissionIsInvalidated(): void
    {
        $accountRepository = AccountRepositoryMother::inMemory();
        $account = AccountMother::fromNameAndId('Denied', 'denied-account');
        $accountRepository->add($account);

        $this->assertValidity(false, ApiTokenMother::active(UserId::generate(), $account->id()), $accountRepository);
    }

    private function assertValidity(bool $expected, ApiToken $apiToken, ?AccountRepository $accountRepository = null): void
    {
        $repository = ApiTokenRepositoryMother::inMemory();
        $repository->add($apiToken);

        $event = new JWTDecodedEvent(['username' => 'user@example.com', 'api_token_id' => (string) $apiToken->id()]);

        $this->createSUT($repository, $accountRepository)($event);

        $this->assertSame($expected, $event->isValid());
    }

    private function createSUT(InMemoryApiTokenRepository $repository, ?AccountRepository $accountRepository = null): ApiTokenJwtDecodedListener
    {
        return new ApiTokenJwtDecodedListener(
            $repository,
            new ApiTokenPolicy($accountRepository ?? AccountRepositoryMother::inMemory()),
        );
    }
}
