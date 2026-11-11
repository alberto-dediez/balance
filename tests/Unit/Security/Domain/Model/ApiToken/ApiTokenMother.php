<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\ApiToken;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\ApiToken\ApiToken;
use App\Security\Domain\Model\ApiToken\ApiTokenId;
use App\Security\Domain\Model\ApiToken\ApiTokenSecret;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;

class ApiTokenMother
{
    public static function active(UserId $userId, ?AccountId $accountId = null, string $name = 'token'): ApiToken
    {
        return self::withSecret(ApiTokenSecret::generate(), $userId, $accountId, $name);
    }

    public static function expired(UserId $userId, ?AccountId $accountId = null, string $name = 'token', ?ApiTokenSecret $secret = null): ApiToken
    {
        return self::from(
            id: ApiTokenId::generate(),
            userId: $userId,
            accountId: $accountId,
            name: $name,
            secret: $secret ?? ApiTokenSecret::generate(),
            expiresAt: new \DateTimeImmutable('-1 day'),
            now: new \DateTimeImmutable('-10 days'),
        );
    }

    public static function revoked(UserId $userId, ?AccountId $accountId = null, string $name = 'token', ?ApiTokenSecret $secret = null): ApiToken
    {
        $apiToken = self::withSecret($secret ?? ApiTokenSecret::generate(), $userId, $accountId, $name);
        $apiToken->revoke($userId, new \DateTimeImmutable('NOW'));

        return $apiToken;
    }

    public static function withSecret(ApiTokenSecret $secret, UserId $userId, ?AccountId $accountId = null, string $name = 'token', ?\DateTimeImmutable $expiresAt = null): ApiToken
    {
        return self::from(
            id: ApiTokenId::generate(),
            userId: $userId,
            accountId: $accountId,
            name: $name,
            secret: $secret,
            expiresAt: $expiresAt,
        );
    }

    public static function fromIdAndUser(string $id, User $user, string $name): ApiToken
    {
        return self::from(
            id: new ApiTokenId($id),
            userId: $user->id(),
            accountId: $user->accountId(),
            name: $name,
            secret: ApiTokenSecret::generate(),
        );
    }

    public static function from(
        ApiTokenId $id,
        UserId $userId,
        ?AccountId $accountId,
        string $name,
        ApiTokenSecret $secret,
        ?\DateTimeImmutable $expiresAt = null,
        ?\DateTimeImmutable $now = null,
    ): ApiToken {
        $apiToken = new ApiToken($id);
        $apiToken->register(
            userId: $userId,
            accountId: $accountId,
            name: $name,
            secret: $secret,
            expiresAt: $expiresAt,
            now: $now ?? new \DateTimeImmutable('NOW'),
        );

        return $apiToken;
    }
}
