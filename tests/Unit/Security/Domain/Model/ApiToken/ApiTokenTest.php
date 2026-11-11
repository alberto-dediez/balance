<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\ApiToken;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\ApiToken\ApiToken;
use App\Security\Domain\Model\ApiToken\ApiTokenExpirationInvalidException;
use App\Security\Domain\Model\ApiToken\ApiTokenId;
use App\Security\Domain\Model\ApiToken\ApiTokenRegistered;
use App\Security\Domain\Model\ApiToken\ApiTokenRevoked;
use App\Security\Domain\Model\ApiToken\ApiTokenSecret;
use App\Security\Domain\Model\ApiToken\ApiTokenStatus;
use App\Security\Domain\Model\ApiToken\ApiTokenUsed;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ApiTokenTest extends TestCase
{
    private SpyDomainEventSubscriber $spy;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spy = new SpyDomainEventSubscriber();
    }

    public function testRegisterPublishesEventWithoutHashInPayload(): void
    {
        $secret = ApiTokenSecret::generate();
        $userId = UserId::generate();
        $accountId = AccountId::generate();

        $apiToken = ApiTokenMother::withSecret($secret, $userId, $accountId, 'Integration');

        $event = $this->spy->lastEvent();
        $this->assertInstanceOf(ApiTokenRegistered::class, $event);

        $payload = $event->payload();
        $this->assertArrayNotHasKey('tokenHash', $payload);
        $this->assertSame($secret->prefix(), $payload['tokenPrefix']);
        $this->assertSame('Integration', $payload['name']);
        $this->assertSame((string) $userId, $payload['userId']);
        $this->assertSame((string) $accountId, $payload['accountId']);
        $this->assertNotContains($secret->plainValue(), $payload);
        $this->assertNotContains($secret->hash(), $payload);

        $this->assertSame($secret->hash(), $apiToken->tokenHash());
        $this->assertSame($secret->prefix(), $apiToken->tokenPrefix());
        $this->assertTrue($apiToken->belongsTo($userId));
    }

    public function testRegisterWithPastExpirationThrows(): void
    {
        $this->expectException(ApiTokenExpirationInvalidException::class);

        $now = new \DateTimeImmutable('NOW');

        (new ApiToken(ApiTokenId::generate()))->register(
            userId: UserId::generate(),
            accountId: null,
            name: 'token',
            secret: ApiTokenSecret::generate(),
            expiresAt: $now->modify('-1 day'),
            now: $now,
        );
    }

    public function testRegisterWithExpirationEqualToNowThrows(): void
    {
        $this->expectException(ApiTokenExpirationInvalidException::class);

        $now = new \DateTimeImmutable('NOW');

        (new ApiToken(ApiTokenId::generate()))->register(
            userId: UserId::generate(),
            accountId: null,
            name: 'token',
            secret: ApiTokenSecret::generate(),
            expiresAt: $now,
            now: $now,
        );
    }

    public function testRegisterWithEmptyNameThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ApiTokenMother::active(UserId::generate(), null, '   ');
    }

    public function testRegisterWithTooLongNameThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ApiTokenMother::active(UserId::generate(), null, str_repeat('a', 101));
    }

    public function testStatusIsActiveWithoutExpiration(): void
    {
        $apiToken = ApiTokenMother::active(UserId::generate());

        $this->assertSame(ApiTokenStatus::Active, $apiToken->status(new \DateTimeImmutable('+10 years')));
        $this->assertTrue($apiToken->isActive(new \DateTimeImmutable('NOW')));
    }

    public function testStatusIsExpiredAfterExpiration(): void
    {
        $apiToken = ApiTokenMother::expired(UserId::generate());

        $this->assertSame(ApiTokenStatus::Expired, $apiToken->status(new \DateTimeImmutable('NOW')));
        $this->assertFalse($apiToken->isActive(new \DateTimeImmutable('NOW')));
    }

    public function testStatusIsActiveBeforeExpiration(): void
    {
        $apiToken = ApiTokenMother::withSecret(ApiTokenSecret::generate(), UserId::generate(), null, 'token', new \DateTimeImmutable('+2 days'));

        $this->assertSame(ApiTokenStatus::Active, $apiToken->status(new \DateTimeImmutable('+1 day')));
        $this->assertSame(ApiTokenStatus::Expired, $apiToken->status(new \DateTimeImmutable('+3 days')));
    }

    public function testRevokedWinsOverExpired(): void
    {
        $userId = UserId::generate();
        $apiToken = ApiTokenMother::expired($userId);
        $apiToken->revoke($userId, new \DateTimeImmutable('NOW'));

        $this->assertSame(ApiTokenStatus::Revoked, $apiToken->status(new \DateTimeImmutable('NOW')));
    }

    public function testRevokePublishesOnceAndIsIdempotent(): void
    {
        $userId = UserId::generate();
        $revokedBy = UserId::generate();
        $apiToken = ApiTokenMother::active($userId);
        $now = new \DateTimeImmutable('NOW');

        $apiToken->revoke($revokedBy, $now);
        $apiToken->revoke($userId, $now->modify('+1 hour'));

        $revokedEvents = array_filter($this->spy->events(), static fn($event): bool => $event instanceof ApiTokenRevoked);

        $this->assertCount(1, $revokedEvents);
        $this->assertTrue($apiToken->isRevoked());
        $this->assertSame(ApiTokenStatus::Revoked, $apiToken->currentStatus());
        $this->assertEquals($revokedBy, $apiToken->revokedBy());
        $this->assertEquals($now, $apiToken->revokedAt());
    }

    public function testMarkAsUsedUpdatesLastUsedAt(): void
    {
        $apiToken = ApiTokenMother::active(UserId::generate());
        $now = new \DateTimeImmutable('NOW');

        $this->assertNull($apiToken->lastUsedAt());

        $apiToken->markAsUsed($now);

        $this->assertEquals($now, $apiToken->lastUsedAt());
        $this->assertInstanceOf(ApiTokenUsed::class, $this->spy->lastEvent());
    }

    public function testBelongsTo(): void
    {
        $userId = UserId::generate();
        $apiToken = ApiTokenMother::active($userId);

        $this->assertTrue($apiToken->belongsTo($userId));
        $this->assertFalse($apiToken->belongsTo(UserId::generate()));
    }
}
